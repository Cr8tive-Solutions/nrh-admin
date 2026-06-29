<?php

namespace Database\Seeders;

use App\Models\ScopeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Populates per-scope document-upload requirements (and forces consent on
 * every scope). The client portal reads `required_documents` / `requires_signed_consent`
 * and blocks request submission until the listed documents are attached.
 *
 * Rules (applied to all countries that have scopes — currently 1-6):
 *   - Every scope                          → consent + nric
 *   - Social Media / Deep Web / Dark Web   → consent + nric + resume
 *   - Professional (academic / employment / referee) → consent + nric + resume + certificate
 */
class ScopeDocumentRequirementSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            ScopeType::query()->chunkById(100, function ($scopes) {
                foreach ($scopes as $scope) {
                    $hay = strtolower($scope->name.' '.($scope->category ?? ''));

                    $docs = ['consent', 'nric'];

                    if ($this->isProfessional($hay)) {
                        $docs = ['consent', 'nric', 'resume', 'certificate'];
                    } elseif ($this->isSocialOrDarkWeb($hay)) {
                        $docs = ['consent', 'nric', 'resume'];
                    }

                    $scope->update([
                        'requires_signed_consent' => true,
                        'required_documents' => $docs,
                    ]);
                }
            });
        });
    }

    /**
     * Academic / employment / referee scopes — mirrors the keyword detection in
     * RequestQueueController::scopeFindingsKind() (name OR category, "loan" excluded).
     */
    private function isProfessional(string $hay): bool
    {
        if (str_contains($hay, 'referee') || str_contains($hay, 'reference')) {
            return true;
        }
        if (str_contains($hay, 'employment') || str_contains($hay, 'work history')) {
            return true;
        }
        foreach (['academic', 'qualification', 'credential', 'education', 'degree', 'certificate', 'certification'] as $kw) {
            if (str_contains($hay, $kw) && ! str_contains($hay, 'loan')) {
                return true;
            }
        }

        return false;
    }

    private function isSocialOrDarkWeb(string $hay): bool
    {
        return str_contains($hay, 'social media')
            || str_contains($hay, 'deep web')
            || str_contains($hay, 'dark web');
    }
}
