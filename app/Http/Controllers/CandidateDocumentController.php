<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\ScreeningRequest;
use Illuminate\Support\Facades\Storage;

class CandidateDocumentController extends Controller
{
    /**
     * Stream a customer-uploaded candidate document (NRIC, resume, consent…)
     * privately to admins. Files are written by the client portal onto its own
     * disk, so try the client_local mount first; the admin disk is a fallback
     * in case a file is ever stored locally.
     */
    public function download(ScreeningRequest $screeningRequest, int $candidateId, int $documentId)
    {
        $candidate = $screeningRequest->candidates()->findOrFail($candidateId);
        $document = $candidate->documents()->findOrFail($documentId);

        $disk = null;
        if ($document->file_path) {
            foreach (['client_local', 'local'] as $candidateDisk) {
                if (Storage::disk($candidateDisk)->exists($document->file_path)) {
                    $disk = $candidateDisk;
                    break;
                }
            }
        }

        abort_if($disk === null, 404, 'Document file is not reachable — check the client_local storage mount.');

        AdminAuditLog::record('pdpa.document_downloaded', null, [
            'document_id' => $document->id,
            'candidate_id' => $candidate->id,
            'request_id' => $screeningRequest->id,
            'type' => $document->type,
        ]);

        return Storage::disk($disk)->download(
            $document->file_path,
            $document->original_name ?: ($document->type.'-'.$document->id.'.'.pathinfo($document->file_path, PATHINFO_EXTENSION))
        );
    }
}
