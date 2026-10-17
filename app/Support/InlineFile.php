<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serve a customer-uploaded file for viewing in the browser tab instead of
 * forcing a download.
 *
 * Only types a browser renders safely (PDF and raster images) go inline; the
 * client portal's upload rules already restrict slips/receipts to these, but
 * anything else falls back to an attachment so an uploaded HTML/SVG file can
 * never execute on the admin origin. `nosniff` and the CSP come from the
 * global SecurityHeaders middleware (a `sandbox` CSP here would also break
 * Chrome's built-in PDF viewer).
 */
class InlineFile
{
    private const INLINE_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    public static function response(Filesystem $disk, string $path, string $filename): StreamedResponse
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, self::INLINE_EXTENSIONS, true)) {
            return $disk->download($path, $filename);
        }

        return $disk->response($path, $filename);
    }
}
