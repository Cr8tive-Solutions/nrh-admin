<?php

namespace App\Support;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use RuntimeException;

/**
 * Transparent at-rest encryption for the private file disks (payment slips,
 * receipts, candidate documents, consent/DSAR evidence, report PDFs).
 *
 * Decorates a local adapter: every write is sealed with AES-256-GCM under a
 * key derived from the SHARED PII_KEY (Pii::fileKey()), every read is opened
 * again, so Storage::disk()->put/get/download/response keep working unchanged
 * and both portals can read each other's files through the mount disks.
 *
 * On-disk format: MAGIC (8) | nonce (12) | GCM tag (16) | ciphertext.
 * Files without the MAGIC prefix are legacy plaintext and are returned as-is,
 * so enabling encryption needs no big-bang migration
 * (`php artisan pii:encrypt-files` converts the backlog).
 *
 * With PII_KEY empty, writes stay plaintext (same no-op gating as Pii).
 * Keep this class identical in nrh-admin and nrh-intelligence.
 */
class EncryptedFilesystemAdapter implements FilesystemAdapter
{
    public const MAGIC = "NRHENC1\0";

    private const CIPHER = 'aes-256-gcm';

    private const NONCE_BYTES = 12;

    private const TAG_BYTES = 16;

    public const OVERHEAD = 8 + self::NONCE_BYTES + self::TAG_BYTES;

    public function __construct(private FilesystemAdapter $inner) {}

    public static function isEncrypted(string $bytes): bool
    {
        return str_starts_with($bytes, self::MAGIC);
    }

    /** Seal plaintext. Returns it unchanged when no PII key is configured. */
    public static function encrypt(string $plain): string
    {
        $key = Pii::fileKey();
        if ($key === null) {
            return $plain;
        }

        $nonce = random_bytes(self::NONCE_BYTES);
        $tag = '';
        $cipher = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $nonce, $tag, self::MAGIC, self::TAG_BYTES);

        if ($cipher === false) {
            throw new RuntimeException('File encryption failed.');
        }

        return self::MAGIC.$nonce.$tag.$cipher;
    }

    /** Open sealed bytes; legacy plaintext passes through untouched. */
    public static function decrypt(string $bytes): string
    {
        if (! self::isEncrypted($bytes)) {
            return $bytes;
        }

        $key = Pii::fileKey();
        if ($key === null) {
            throw new RuntimeException('File is encrypted but PII_KEY is not configured.');
        }

        $nonce = substr($bytes, 8, self::NONCE_BYTES);
        $tag = substr($bytes, 8 + self::NONCE_BYTES, self::TAG_BYTES);
        $plain = openssl_decrypt(substr($bytes, self::OVERHEAD), self::CIPHER, $key, OPENSSL_RAW_DATA, $nonce, $tag, self::MAGIC);

        if ($plain === false) {
            throw new RuntimeException('File decryption failed (wrong PII_KEY or tampered file).');
        }

        return $plain;
    }

    public function read(string $path): string
    {
        try {
            return self::decrypt($this->inner->read($path));
        } catch (RuntimeException $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $this->read($path));
        rewind($stream);

        return $stream;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->inner->write($path, self::encrypt($contents), $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, (string) stream_get_contents($contents), $config);
    }

    /** Plaintext size, so Content-Length on download responses stays correct. */
    public function fileSize(string $path): FileAttributes
    {
        $size = $this->inner->fileSize($path)->fileSize();

        $stream = $this->inner->readStream($path);
        $head = (string) fread($stream, strlen(self::MAGIC));
        fclose($stream);

        if (self::isEncrypted($head)) {
            $size -= self::OVERHEAD;
        }

        return new FileAttributes($path, $size);
    }

    /** By extension — content sniffing would only ever see ciphertext. */
    public function mimeType(string $path): FileAttributes
    {
        $mime = (new ExtensionMimeTypeDetector)->detectMimeTypeFromPath($path);

        if ($mime === null) {
            throw UnableToRetrieveMetadata::mimeType($path, 'Unknown file extension.');
        }

        return new FileAttributes($path, null, null, null, $mime);
    }

    public function fileExists(string $path): bool
    {
        return $this->inner->fileExists($path);
    }

    public function directoryExists(string $path): bool
    {
        return $this->inner->directoryExists($path);
    }

    public function delete(string $path): void
    {
        $this->inner->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->inner->deleteDirectory($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->inner->createDirectory($path, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->inner->setVisibility($path, $visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->inner->visibility($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->inner->lastModified($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return $this->inner->listContents($path, $deep);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->inner->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->inner->copy($source, $destination, $config);
    }
}
