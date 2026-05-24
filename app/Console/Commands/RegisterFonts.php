<?php

namespace App\Console\Commands;

use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Options;
use Illuminate\Console\Command;

class RegisterFonts extends Command
{
    protected $signature = 'fonts:register';
    protected $description = 'Register custom fonts with dompdf (run once after deployment or cloning)';

    /** Font family → [weight, style, filename] registrations */
    private const FONTS = [
        ['Courier Prime',               'normal', 'normal', 'CourierPrime-Regular.ttf'],
        ['Courier Prime',               'bold',   'normal', 'CourierPrime-Bold.ttf'],
        ['Courier Prime',               'normal', 'italic', 'CourierPrime-Italic.ttf'],
        ['Courier Prime',               'bold',   'italic', 'CourierPrime-BoldItalic.ttf'],
        ['Oswald',                      'normal', 'normal', 'Oswald-SemiBold.ttf'],
        ['Oswald',                      'bold',   'normal', 'Oswald-SemiBold.ttf'],
        ['Oswald',                      'normal', 'italic', 'Oswald-SemiBold.ttf'],
        ['Oswald',                      'bold',   'italic', 'Oswald-SemiBold.ttf'],
        ['IBM Plex Serif',              'normal', 'normal', 'IBMPlexSerif-Regular.ttf'],
        ['IBM Plex Serif',              'bold',   'normal', 'IBMPlexSerif-Bold.ttf'],
        ['IBM Plex Serif',              'normal', 'italic', 'IBMPlexSerif-Italic.ttf'],
        ['IBM Plex Serif',              'bold',   'italic', 'IBMPlexSerif-BoldItalic.ttf'],
        ['IBM Plex Sans Condensed Bold','normal', 'normal', 'IBMPlexSansCondensed-Bold.ttf'],
        ['IBM Plex Sans Condensed Bold','bold',   'normal', 'IBMPlexSansCondensed-Bold.ttf'],
        ['IBM Plex Sans Condensed Bold','normal', 'italic', 'IBMPlexSansCondensed-Bold.ttf'],
        ['IBM Plex Sans Condensed Bold','bold',   'italic', 'IBMPlexSansCondensed-Bold.ttf'],
        ['Arial Rounded MT Bold',       'normal', 'normal', 'ArialRoundedMTBold.ttf'],
        ['Arial Rounded MT Bold',       'bold',   'normal', 'ArialRoundedMTBold.ttf'],
        ['Arial Rounded MT Bold',       'normal', 'italic', 'ArialRoundedMTBold.ttf'],
        ['Arial Rounded MT Bold',       'bold',   'italic', 'ArialRoundedMTBold.ttf'],
    ];

    public function handle(): int
    {
        $fontDir = storage_path('fonts');

        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0755, true);
        }

        $options = new Options();
        $options->setFontDir($fontDir);
        $options->setFontCache($fontDir);
        $options->setChroot(base_path());

        $dompdf      = new Dompdf($options);
        $fontMetrics = new FontMetrics($dompdf->getCanvas(), $options);

        foreach (self::FONTS as [$family, $weight, $style, $file]) {
            $path = $fontDir . '/' . $file;

            if (! file_exists($path)) {
                $this->warn("  Missing: {$file} — skipping {$family} ({$weight}/{$style})");
                continue;
            }

            $fontMetrics->registerFont(
                ['family' => $family, 'weight' => $weight, 'style' => $style],
                $path
            );
        }

        $registered = array_keys(
            json_decode(file_get_contents($fontDir . '/installed-fonts.json'), true) ?? []
        );

        foreach ($registered as $name) {
            $this->line("  <info>✓</info> {$name}");
        }

        $this->info('Font registration complete.');
        return self::SUCCESS;
    }
}
