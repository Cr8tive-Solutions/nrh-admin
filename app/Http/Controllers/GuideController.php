<?php

namespace App\Http\Controllers;

use App\Support\AdminGuide;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * The staff operating manual for this portal.
 *
 * Content lives in App\Support\AdminGuide; this controller only renders it,
 * on screen or as a PDF. Available to every authenticated admin — the guide
 * documents permission-gated pages but reading about them is not gated.
 */
class GuideController extends Controller
{
    public function show()
    {
        return view('docs.guide-web', [
            'sections' => AdminGuide::sections(),
        ]);
    }

    public function download()
    {
        $sections = AdminGuide::sections();

        // Same font/renderer setup as the screening report so the two documents
        // look like one family. Fonts come from storage/fonts — run
        // `php artisan fonts:register` on a new server or they fall back.
        $pdf = Pdf::loadView('docs.guide-pdf', [
            'sections' => $sections,
            'sectionCount' => count($sections),
            'logoSrc' => $this->logoSrc(),
            'generatedAt' => now()->format('d F Y'),
            'portalUrl' => config('app.url'),
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'Courier Prime',
                'fontDir' => storage_path('fonts'),
                'fontCache' => storage_path('fonts'),
            ]);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        // Page numbers, skipping the cover.
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('Oswald', 'normal');
        $canvas->page_text(
            $canvas->get_width() - 115,
            $canvas->get_height() - 24,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            8,
            [0.4, 0.4, 0.4]
        );

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="NRH-Admin-Portal-Guide.pdf"',
        ]);
    }

    /**
     * Base64 the logo — dompdf runs with remote assets disabled.
     */
    private function logoSrc(): string
    {
        $path = public_path('images/nrh-logo.png');

        return file_exists($path)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($path))
            : '';
    }
}
