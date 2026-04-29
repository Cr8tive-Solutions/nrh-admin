<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\ScreeningRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function generate(Request $request, ScreeningRequest $screeningRequest)
    {
        $screeningRequest->load([
            'customer',
            'customerUser',
            'candidates.identityType',
            'candidates.scopeTypes',
        ]);

        // Embed the logo as a data URI so dompdf doesn't need to fetch over HTTP.
        $logoPath = public_path('images/nrh-logo.png');
        $logoSrc = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : '';

        $data = [
            'request'           => $screeningRequest,
            'reference'         => $screeningRequest->reference,
            'customer'          => $screeningRequest->customer,
            'candidates'        => $screeningRequest->candidates,
            'logoSrc'           => $logoSrc,
            'completionBasic'   => null,
            'completionPrelim'  => null,
            'completionFull'    => $screeningRequest->status === 'complete'
                ? $screeningRequest->updated_at->format('d F Y')
                : null,
        ];

        $pdf = Pdf::loadView('reports.screening', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'         => true,
                'defaultFont'          => 'DejaVu Sans',
            ]);

        // Register a per-page footer with proper "Page X of Y" text.
        // We do this AFTER load/render so dompdf knows the total page count.
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'bold');
        $canvas->page_text(
            $canvas->get_width() - 115,
            $canvas->get_height() - 38,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            9,
            [0, 0, 0]
        );

        AdminAuditLog::record('report.generated', null, [
            'request_id' => $screeningRequest->id,
            'reference'  => $screeningRequest->reference,
            'mode'       => $request->boolean('inline') ? 'inline' : 'download',
        ]);

        $filename = 'NRH-Report-'.$screeningRequest->reference.'.pdf';

        $output = $dompdf->output();

        return response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => ($request->boolean('inline') ? 'inline' : 'attachment').'; filename="'.$filename.'"',
        ]);
    }
}
