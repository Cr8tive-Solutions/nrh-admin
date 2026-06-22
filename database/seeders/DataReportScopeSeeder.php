<?php

namespace Database\Seeders;

use App\Models\ScopeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Aligns scope_types with the official "NRH Data Report" structure.
 *
 * - Malaysia (country 1): 22 Data Report scopes + MyKAD (first) + 8 Professional Report scopes.
 *   Four groups are consolidated into single scopes (AML/CTF, Securities Commission,
 *   Bursa Malaysia, Global Sanctions), reducing 32 Data Report scopes to 22.
 * - Other countries (2-6): same naming convention + ordering; the 3 sanctions scopes
 *   (OFAC / UN / World Bank) are consolidated into one "Global Sanctions - Global Risk".
 *
 * sort_order drives the listing order everywhere (config, pricing, request, PDF report).
 * The Personal Data ID/MyKAD verification scope is always sort_order 1 within its country.
 */
class DataReportScopeSeeder extends Seeder
{
    public function run(): void
    {
        // [id => [name, sort_order, description|null]]  — description null = leave unchanged.
        $malaysia = [
            // ── Identity (always first) ──
            119 => ['Personal Data – MyKAD Verification', 1, null],
            // ── Data Report (22) ──
            120 => ['Crime Risk Integrity Screening', 2, 'This screening assesses criminal exposure and integrity risks based on identity verification and lawful record checks.'],
            122 => ['Corruption Record MACC', 3, 'Screening conducted against the Malaysian Anti-Corruption Commission (MACC) database to identify corruption-related offences or investigations.'],
            121 => ['INTERPOL Global Crime Data – Malaysia', 4, 'Screening conducted against INTERPOL databases to identify international crime notices, alerts, or investigations originating from Malaysia.'],
            123 => ['National Counter-Terrorism Record', 5, "Screening conducted against Malaysia's national counter-terrorism database to identify terrorism-related offences, alerts, or investigations."],
            124 => ['Anti-Money Laundering & Counter-Terrorism Financing', 6, 'Screening conducted against AML/CTF databases maintained by MACC, Bank Negara Malaysia (BNM), Securities Commission, and KDN listings to identify money laundering or terrorism financing offences or investigations.'],
            128 => ['Securities Commission Malaysia – Criminal/Civil Enforcement', 7, 'Screening conducted against Securities Commission Malaysia (SC) databases to identify capital market offences, financial fraud, breach of trust, insider trading, or securities trading violations.'],
            132 => ['Bursa Malaysia Berhad – Market Conduct & Listing Compliance', 8, "Screening conducted against Bursa Malaysia Berhad's enforcement and disciplinary databases to identify securities-related offences, insider trading, market manipulation, or listing violations."],
            135 => ['Global Sanctions - Global Risk', 9, 'Screening conducted against international sanctions databases including OFAC (SDN List), UN Security Council Consolidated Sanctions List, and World Bank Debarment List to identify global sanctions, blocked persons, or international restrictions.'],
            138 => ['Politically Exposed Persons (PEP)', 10, 'Screening conducted against global PEP databases to identify politically exposed persons, close associates, or family members of PEPs.'],
            139 => ['Civil Summons & Credit Default', 11, 'Screening conducted against civil court summons and judgment databases to identify civil summons or credit default records.'],
            140 => ['Bankruptcy / Insolvency', 12, 'Screening conducted against official bankruptcy and insolvency registries at Jabatan Insolvensi Malaysia (JIM) to identify records of bankruptcy adjudications, insolvency proceedings, or related restrictions.'],
            141 => ['BNM CCRIS Record', 13, 'Screening conducted against the Central Credit Reference Information System (CCRIS) maintained by Bank Negara Malaysia (BNM) to identify outstanding loans, repayment history, defaults, and legal actions.'],
            142 => ['Academic Loan Standing', 14, 'Screening conducted against official academic loan registries and financial aid databases to identify records of outstanding loans, defaults, or repayment irregularities.'],
            143 => ['Industrial Relations & Labor Court Record', 15, 'Screening conducted against official Industrial Relations Department and Labor Court databases to identify records of employment disputes, labor claims, or adjudications.'],
            144 => ['Civil Litigation Record', 16, 'Screening conducted against civil court litigation databases to identify records of lawsuits, disputes, or adjudications involving the candidate.'],
            145 => ['Driving & Motor Vehicle Offences', 17, 'Screening conducted against official Road Transport Department (JPJ) and Royal Malaysia Police (PDRM) traffic offence databases to identify records of driving violations, summons, or motor vehicle offences.'],
            146 => ['Driving License Verification', 18, 'Screening conducted against official licensing authorities and regulatory registries to confirm the validity of driving licenses held by the candidate.'],
            147 => ['Travel Eligibility & Immigration Record', 19, 'Screening conducted against official immigration authorities and travel eligibility registries to identify restrictions, bans, or adverse immigration records affecting international travel.'],
            148 => ['Directorship & Shareholding Risk', 20, 'Screening conducted against official corporate registries at the Companies Commission of Malaysia (SSM) to identify directorships, shareholding interests, and potential conflicts of interest.'],
            149 => ['Corporate Registry Record (SSM)', 21, 'Screening conducted against official corporate registries at the Companies Commission of Malaysia (SSM) to verify corporate registry records linked to the candidate.'],
            150 => ['Social Media & Deep Web Intelligence', 22, 'Screening conducted across publicly available social media platforms, open-source intelligence (OSINT), and deep web monitoring tools to identify reputational risks, adverse mentions, or suspicious online activity linked to the candidate.'],
            151 => ['Dark Web Risk Intelligence Report', 23, 'Screening conducted across dark web forums, leak archives, and breach intelligence databases to identify exposure of personal identifiers, credentials, or sensitive information linked to the candidate.'],
            // ── Professional Report (8) ──
            152 => ['One Academic Credential Verification (Malaysian Institution)', 24, null],
            153 => ['Two Academic Credential Verification (Malaysian Institution)', 25, null],
            154 => ['One Academic Credential Verification (Foreign Institution)', 26, null],
            155 => ['One Professional Body Membership Record', 27, null],
            156 => ['One Employment Verification', 28, null],
            157 => ['Two Employment Verification', 29, null],
            158 => ['One Reference Review', 30, null],
            159 => ['Two Reference Reviews', 31, null],
        ];

        // Malaysia consolidation: merged-away scopes to remove.
        $malaysiaDelete = [125, 126, 127, 129, 130, 131, 133, 134, 136, 137];

        // Foreign countries share an identical 19-scope template. Base ids per country:
        // Singapore 160, Indonesia 179, Thailand 198, Philippines 217, Vietnam 236.
        $foreignBases = [160, 179, 198, 217, 236];

        // Offset-from-base => [name, sort_order, deleteFlag]
        // Sanctions (OFAC kept, UN + World Bank removed → "Global Sanctions - Global Risk").
        $foreignTemplate = [
            0 => ['Personal Data – ID Verification', 1],                          // ID verification (first)
            1 => ['Crime Risk Integrity Screening', 2],
            3 => ['Corruption Record', 3],
            2 => ['INTERPOL Global Crime Data', 4],
            4 => ['National Counter-Terrorism Record', 5],
            5 => ['Anti-Money Laundering & Counter-Terrorism Financing', 6],
            6 => ['Global Sanctions - Global Risk', 7],                            // was OFAC
            9 => ['Politically Exposed Persons (PEP)', 8],
            10 => ['Civil Summons & Credit Default', 9],
            11 => ['Bankruptcy / Insolvency', 10],
            12 => ['Directorship & Shareholding Risk', 11],
            13 => ['Social Media & Deep Web Intelligence', 12],
            14 => ['Dark Web Risk Intelligence Report', 13],
            15 => ['One Academic Credential Verification', 14],
            16 => ['One Professional Body Membership Record', 15],
            17 => ['One Employment Verification', 16],
            18 => ['Two Reference Reviews', 17],
        ];
        $foreignDeleteOffsets = [7, 8]; // UN Security Council Sanction, World Bank Sanction

        DB::transaction(function () use ($malaysia, $malaysiaDelete, $foreignBases, $foreignTemplate, $foreignDeleteOffsets) {
            // 1. Remove surplus scopes + their references.
            $deleteIds = $malaysiaDelete;
            foreach ($foreignBases as $base) {
                foreach ($foreignDeleteOffsets as $off) {
                    $deleteIds[] = $base + $off;
                }
            }
            DB::table('candidate_scope_type')->whereIn('scope_type_id', $deleteIds)->delete();
            DB::table('customer_scope_prices')->whereIn('scope_type_id', $deleteIds)->delete();
            DB::table('package_scope_type')->whereIn('scope_type_id', $deleteIds)->delete();
            ScopeType::whereIn('id', $deleteIds)->delete();

            // 2. Rename + order Malaysia.
            foreach ($malaysia as $id => [$name, $order, $desc]) {
                $update = ['name' => mb_strtoupper($name), 'sort_order' => $order];
                if ($desc !== null) {
                    $update['description'] = $desc;
                }
                ScopeType::where('id', $id)->update($update);
            }

            // 3. Rename + order foreign countries.
            foreach ($foreignBases as $base) {
                foreach ($foreignTemplate as $offset => [$name, $order]) {
                    ScopeType::where('id', $base + $offset)->update([
                        'name' => mb_strtoupper($name),
                        'sort_order' => $order,
                    ]);
                }
            }
        });
    }
}
