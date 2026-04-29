<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Background Screening Report — {{ $reference }}</title>
<style>
    @page { margin: 90px 50px 60px 50px; }

    * { box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10pt;
        color: #111;
        line-height: 1.4;
    }

    /* ── Page header & footer (drawn by dompdf via fixed positioning) ── */
    .page-header {
        position: fixed; top: -75px; left: 0; right: 0;
        height: 65px;
        border-bottom: 1px solid #111;
        padding-bottom: 6px;
    }
    .page-header .ref-no {
        position: absolute; left: 0; top: 26px;
        font-size: 10pt; font-weight: bold;
    }
    .page-header .crest {
        position: absolute; left: 50%; top: 0;
        transform: translateX(-50%);
        text-align: center;
    }
    .page-header .crest img { height: 50px; }
    .page-header .crest .tagline {
        font-size: 6pt; letter-spacing: 0.18em;
        color: #2a2a2a; margin-top: 1px; text-transform: uppercase;
    }

    .page-footer {
        position: fixed; bottom: -45px; left: 0; right: 0;
        height: 30px;
        border-top: 1px solid #111;
    }

    /* ── Section header (orange/gold pill) ── */
    .sec-head {
        background: #d4af37;
        color: #1a1a1a;
        padding: 6px 12px;
        font-weight: bold;
        font-size: 10pt;
        margin-top: 10px; margin-bottom: 0;
        letter-spacing: 0.02em;
    }

    /* ── Tables ── */
    table.report-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0;
        margin-bottom: 12px;
        page-break-inside: avoid;
    }
    table.report-table td, table.report-table th {
        border: 1px solid #111;
        padding: 5px 8px;
        vertical-align: top;
    }
    table.report-table th.label {
        background: #023527;
        color: #fff;
        text-align: left;
        font-weight: bold;
        width: 35%;
    }
    table.report-table th.label-narrow { width: 28%; }
    table.report-table tr.section-divider td {
        background: #d4af37;
        color: #1a1a1a;
        font-weight: bold;
        padding: 6px 10px;
        border: 1px solid #111;
    }
    table.report-table td.value { background: #fff; }
    table.report-table .center { text-align: center; }
    table.report-table tr.legend-row td {
        background: #023527;
        color: #fff;
        text-align: center;
        font-weight: bold;
        width: 25%;
        padding: 8px;
        font-size: 13pt;
    }
    table.report-table tr.legend-row td.legend-text {
        background: #fff; color: #1a1a1a;
        text-align: left; font-size: 10pt;
        font-weight: bold;
    }

    /* ── Cover ── */
    .cover-title {
        text-align: center;
        font-size: 18pt;
        font-weight: bold;
        margin: 20px 0 14px;
    }
    .cover-notice {
        font-size: 8.5pt; line-height: 1.5;
        margin-bottom: 10px;
    }
    .cover-notice .heading {
        text-decoration: underline; font-weight: bold;
        font-style: italic; color: #023527;
    }
    .cover-notice .body {
        font-style: italic; color: #023527;
        padding-left: 18px;
    }
    .cover-notice .body::before {
        content: "•";
        margin-right: 6px;
    }

    /* ── Summary table — status icons ── */
    .summary-row td { padding: 4px 8px; }
    .icon-cell {
        text-align: center; font-weight: bold;
        width: 60px;
    }
    .icon-pass    { color: #046c4e; font-size: 14pt; }
    .icon-fail    { color: #c4453a; font-size: 14pt; }
    .icon-no-match{ color: #046c4e; font-size: 12pt; }
    .icon-match   { color: #c4453a; font-size: 12pt; }
    .icon-annot   { color: #b8860b; font-size: 14pt; }
    .icon-pending { color: #888;    font-size: 12pt; }

    /* ── Comprehensive section block ── */
    .check-block {
        page-break-inside: avoid;
        margin-bottom: 8px;
    }
    .summary-table { page-break-inside: auto; }
    .check-block .check-result-row td.result-pass {
        background: #ecfdf5;
        font-weight: bold;
        color: #023527;
    }
    .check-block .check-result-row td.result-fail {
        background: #fbeeec;
        font-weight: bold;
        color: #c4453a;
    }
    .check-block .check-result-row td.result-pending {
        background: #f5f5f5;
        font-style: italic;
        color: #555;
    }

    /* ── Static / disclaimer pages ── */
    .static-page-title {
        font-size: 16pt; font-weight: bold;
        margin-top: 20px; margin-bottom: 14px;
    }
    .static-block {
        margin-bottom: 14px; line-height: 1.55;
    }
    .static-block ol { padding-left: 20px; margin: 6px 0; }
    .static-block ol li { margin-bottom: 6px; }
    .end-of-report {
        text-align: center; font-weight: bold;
        margin-top: 40px; font-size: 11pt;
    }

    .pagebreak { page-break-after: always; }

    /* ── Performance scale ── */
    .perf-scale td { padding: 6px 10px; font-size: 9pt; }
    .perf-scale td.range {
        background: #023527; color: #fff;
        text-align: center; font-weight: bold;
        width: 70px;
    }

    /* ── Brand strip ── */
    .brand-line {
        border-top: 2px solid #d4af37;
        margin: 4px 0 14px;
    }

    /* Helpers */
    .nowrap { white-space: nowrap; }
    .small { font-size: 8.5pt; }
    .muted { color: #666; }
    .center { text-align: center; }
    .right  { text-align: right; }
    .bold   { font-weight: bold; }
</style>
</head>
<body>

{{-- ===================== Persistent header / footer ===================== --}}
<div class="page-header">
    <span class="ref-no">REP NO: {{ $reference }}</span>
    <div class="crest">
        <img src="{{ $logoSrc }}" alt="">
        <div class="tagline">NRH Intelligence</div>
    </div>
</div>
<div class="page-footer"></div>

{{-- ===================== PAGE 1 — Cover ===================== --}}
<h1 class="cover-title">NRH INTELLIGENCE<br>BACKGROUND SCREENING REPORT</h1>

<div class="cover-notice">
    <div class="heading">PRIVATE AND CONFIDENTIAL</div>
    <div class="body">
        NRH Intelligence Report is classified as Private and Confidential, strictly for the viewing of the client.
        Disseminating material evidence from this report or sharing pertinent information to any person is strictly
        prohibited, except as may be required by applicable law, rule, or regulation.
    </div>
</div>

<div class="cover-notice">
    <div class="heading">PRIVACY LAW</div>
    <div class="body">
        The PII of the data subject (candidate) was validly obtained, conforming to the 'consent' principle under the
        applicable privacy law.
    </div>
</div>

<div class="sec-head">REPORT</div>
<table class="report-table">
    <tr><th class="label">REPORT NUMBER</th><td class="value">{{ $reference }}</td></tr>
    <tr><th class="label">CLIENT</th><td class="value">{{ $customer->name ?? '—' }}</td></tr>
    <tr><th class="label">CONTACT PERSON</th><td class="value">{{ $customer->contact_name ?? '—' }}</td></tr>
    <tr><th class="label">CONTACT NUMBER</th><td class="value">{{ $customer->contact_phone ?? '—' }}</td></tr>
    <tr><th class="label">EMAIL ADDRESS</th><td class="value">{{ $customer->contact_email ?? '—' }}</td></tr>
    <tr><th class="label">BRANCH / SUBSIDIARY</th><td class="value">{{ $customer->industry ?? 'Nil' }}</td></tr>
</table>

<div class="sec-head">PIVOTAL</div>
<table class="report-table">
    <tr><th class="label">COMMENCEMENT DATE</th><td class="value">{{ $request->created_at->format('d F Y') }}</td></tr>
    <tr>
        <th class="label" rowspan="3" style="width:25%;">COMPLETION DATE</th>
        <td class="value">
            <table style="width:100%; border:none;">
                <tr><td style="border:none; width:35%; background:#023527; color:#fff; padding:4px 8px; font-weight:bold;">BASIC REPORT</td><td style="border:none; padding:4px 8px;">{{ $completionBasic ?? '—' }}</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="value">
            <table style="width:100%; border:none;">
                <tr><td style="border:none; width:35%; background:#023527; color:#fff; padding:4px 8px; font-weight:bold;">PRELIM REPORT</td><td style="border:none; padding:4px 8px;">{{ $completionPrelim ?? '—' }}</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="value">
            <table style="width:100%; border:none;">
                <tr><td style="border:none; width:35%; background:#023527; color:#fff; padding:4px 8px; font-weight:bold;">FULL REPORT</td><td style="border:none; padding:4px 8px;">{{ $completionFull ?? ($request->status === 'complete' ? $request->updated_at->format('d F Y') : '—') }}</td></tr>
            </table>
        </td>
    </tr>
    <tr><th class="label">HIRING CATEGORY</th><td class="value">{{ strtoupper($request->type ?? 'V-PRO') }}</td></tr>
    <tr><th class="label">PURCHASE ORDER NO</th><td class="value">{{ data_get($request->meta, 'po_number', 'Nil') }}</td></tr>
    <tr><th class="label">RESEARCH ANALYST</th><td class="value">{{ data_get($request->meta, 'analyst', '—') }}</td></tr>
    <tr><th class="label">EDITOR</th><td class="value">{{ data_get($request->meta, 'editor', '—') }}</td></tr>
</table>

<div class="sec-head">REPORT EARMARK &amp; LEGEND</div>
<table class="report-table">
    <tr class="legend-row"><td>✓</td><td class="legend-text">PASS, NO RECORD &amp; REVIEW OBTAINED</td></tr>
    <tr class="legend-row"><td>✗</td><td class="legend-text">FAIL, RECORD &amp; NO REVIEW OBTAINED</td></tr>
    <tr class="legend-row"><td>○</td><td class="legend-text">"NO MATCH" RECORD</td></tr>
    <tr class="legend-row"><td>●</td><td class="legend-text">"MATCH" RECORD</td></tr>
    <tr class="legend-row"><td>!</td><td class="legend-text">ANNOTATION</td></tr>
</table>

<div class="pagebreak"></div>

{{-- ===================== PAGE 2 — Summary ===================== --}}
<div class="sec-head">SUMMARY REPORT</div>
<table class="report-table">
    <tr>
        <th class="label" style="width: 75%;">CHECK</th>
        <th class="label icon-cell">EARMARK</th>
    </tr>
    @foreach($candidates as $candidate)
        <tr class="section-divider">
            <td colspan="2">CANDIDATE: {{ strtoupper($candidate->name) }} ({{ $candidate->identity_number }})</td>
        </tr>
        @foreach($candidate->scopeTypes as $scope)
        @php
            $st = $scope->pivot->status ?? 'new';
            $iconHtml = match ($st) {
                'complete' => '<span class="icon-pass">✓</span>',
                'flagged'  => '<span class="icon-fail">✗</span>',
                'in_progress' => '<span class="icon-pending">…</span>',
                default    => '<span class="icon-pending">·</span>',
            };
        @endphp
        <tr class="summary-row">
            <td>{{ $scope->name }}@if($scope->category)<span class="muted small"> · {{ $scope->category }}</span>@endif</td>
            <td class="icon-cell">{!! $iconHtml !!}</td>
        </tr>
        @endforeach
    @endforeach
</table>

<div class="pagebreak"></div>

{{-- ===================== PAGES 3+ — Comprehensive sections ===================== --}}
<h1 class="cover-title" style="font-size:14pt; margin-top:0;">COMPREHENSIVE REPORT</h1>

<div class="static-block small">
    <strong>Important Notice / Disclaimer:</strong>
    While every endeavour has been made to ensure that the incriminating history listed as Incriminating Record is accurate,
    and up-to-date, NRH Intelligence does not vouch for accuracy as the content might come from public domain that
    is subject to constant change. Howsoever, NRH Intelligence is not liable for the lapses in coverage or
    omission of records due to reliance on third-party data sources. NRH Intelligence accounts for and up-to-date
    information that is reported within the 30 days from the date of release of this report and we shall verify
    &amp; correct as soon as it is practicable to do so. Otherwise, the record is deemed accurate and up-to-date,
    requiring no further update.
</div>

@foreach($candidates as $candidateIndex => $candidate)
    <div class="sec-head">CANDIDATE {{ $candidateIndex + 1 }} — {{ strtoupper($candidate->name) }}</div>

    <table class="report-table">
        <tr><th class="label">FULL NAME</th><td class="value">{{ $candidate->name }}</td></tr>
        @if($candidate->identityType)
        <tr><th class="label">{{ strtoupper($candidate->identityType->name) }} NO</th><td class="value">{{ $candidate->identity_number }}</td></tr>
        @endif
        @if($candidate->mobile)
        <tr><th class="label">CONTACT NUMBER</th><td class="value">{{ $candidate->mobile }}</td></tr>
        @endif
        <tr><th class="label">CANDIDATE STATUS</th><td class="value">{{ strtoupper(str_replace('_', ' ', $candidate->status)) }}</td></tr>
    </table>

    @foreach($candidate->scopeTypes as $scope)
    @php
        $pivotStatus = $scope->pivot->status ?? 'new';
        $finished = in_array($pivotStatus, ['complete', 'flagged'], true);
        $resultLabel = match ($pivotStatus) {
            'complete' => 'PASS',
            'flagged'  => 'FAIL / MATCH RECORD',
            'in_progress' => 'IN PROGRESS',
            default    => 'PENDING',
        };
        $resultClass = match ($pivotStatus) {
            'complete' => 'result-pass',
            'flagged'  => 'result-fail',
            default    => 'result-pending',
        };
        $tatHours = $scope->pivot->tatHours();
    @endphp
    <div class="check-block">
        <div class="sec-head">{{ strtoupper($scope->name) }}@if($scope->category) — {{ strtoupper($scope->category) }}@endif</div>
        <table class="report-table">
            <tr class="check-result-row">
                <th class="label">RESULT</th>
                <td class="value {{ $resultClass }}">{{ $resultLabel }}</td>
            </tr>
            <tr>
                <th class="label">COMMENT</th>
                <td class="value">
                    @if($finished)
                        NRH Intelligence's search for <strong>{{ $scope->name }}</strong> has been
                        @if($pivotStatus === 'complete')
                            <strong>completed</strong> with no adverse findings against the candidate's name and identity number.
                        @else
                            <strong>completed with a record match</strong> against the candidate's name and identity number. See record details below.
                        @endif
                    @else
                        <em class="muted">Investigation is still in progress. Findings will appear in the final report.</em>
                    @endif
                </td>
            </tr>
            @if($scope->turnaround_hours)
            <tr>
                <th class="label">SLA / TAT</th>
                <td class="value">
                    Target: {{ $scope->turnaround_hours }} business hours.
                    @if($scope->pivot->assigned_at)
                        Actual: <strong>{{ $tatHours }}h</strong>
                        @if($finished && $tatHours > $scope->turnaround_hours)
                            (<span style="color:#c4453a;">{{ round($tatHours - $scope->turnaround_hours, 1) }}h over SLA</span>)
                        @elseif($finished)
                            (<span style="color:#046c4e;">within SLA</span>)
                        @endif
                    @endif
                </td>
            </tr>
            @endif
        </table>
    </div>
    @endforeach
@endforeach

<div class="pagebreak"></div>

{{-- ===================== Static — About / Accreditation ===================== --}}
<h1 class="static-page-title">ABOUT NRH INTELLIGENCE</h1>
<div class="static-block">
    <p>NRH Intelligence Sdn. Bhd. is a Malaysia-based corporate due-diligence and pre-employment background-screening
    firm. We deliver fact-based, defensible reports on candidates, partners, and counter-parties for clients in
    banking, capital markets, fintech, legal, professional services, and government.</p>

    <p>Our platform combines structured database queries, primary-source verification (institutions, employers, courts,
    regulators), and human review by trained research analysts. Every report is editor-reviewed before release.</p>
</div>

<h1 class="static-page-title">SERVICES</h1>
<div class="static-block">
    <table class="report-table">
        <tr>
            <th class="label center" style="text-align:center;">CORE SERVICES</th>
            <th class="label center" style="text-align:center;">SPECIALISED CHECKS</th>
        </tr>
        <tr>
            <td>
                Pre-Employment Background Screening<br>
                Corporate Due Diligence<br>
                Vendor &amp; Counter-party Due Diligence<br>
                KYC / KYB / KYS<br>
                Politically Exposed Persons (PEP) Screening<br>
                Sanctions &amp; Watchlist Screening<br>
                Adverse Media &amp; Reputation Risk
            </td>
            <td>
                Education &amp; Qualification Verification<br>
                Employment Reference Verification<br>
                Criminal &amp; Civil Records Search<br>
                Bankruptcy &amp; Litigation Search<br>
                Financial Standing &amp; Credit Report<br>
                Driving &amp; Licensing Records<br>
                Digital Presence &amp; Online Risk Audit
            </td>
        </tr>
    </table>
</div>

<h1 class="static-page-title">ACCREDITATION &amp; REGULATORY STANDING</h1>
<div class="static-block">
    <p>NRH Intelligence operates in compliance with applicable Malaysian privacy and consumer-protection law,
    including the Personal Data Protection Act 2010 (PDPA). Our research practices align with international
    background-screening industry standards and we maintain ongoing professional development for our analysts.</p>

    <p>Our reports are produced and editor-reviewed in accordance with the principles of accuracy, fairness,
    relevance, and verifiability. We retain full audit trails of every research action taken on every report.</p>
</div>

<div class="pagebreak"></div>

{{-- ===================== Static — Disclaimer / Terms ===================== --}}
<h1 class="static-page-title">DISCLAIMER &amp; TERMS</h1>
<div class="static-block">
    <ol>
        <li>This report has been prepared exclusively for the use of the named client and is classified as
        <strong>Private and Confidential</strong>. The client is the sole intended recipient.</li>

        <li>The information contained herein has been compiled from a combination of public-domain sources,
        primary-source verifications, and where applicable third-party data providers. NRH Intelligence has
        exercised reasonable care in compiling this report.</li>

        <li>This report represents NRH Intelligence's findings as at the date of release. NRH Intelligence does not
        warrant that the information remains accurate or up-to-date thereafter, given that public-domain content
        is subject to constant change.</li>

        <li>NRH Intelligence shall not be liable for any decisions made by the client on the basis of this report.
        The client is responsible for assessing the relevance, completeness, and applicability of the findings to
        its own decision-making.</li>

        <li>NRH Intelligence may update or correct any record reported herein within 30 days of release, upon
        substantiated request. After the 30-day window, the record is deemed accurate and up-to-date and requires
        no further action.</li>

        <li>The candidate (data subject) has provided informed consent for the personal data used in this report,
        in accordance with the PDPA 2010.</li>

        <li>The findings are based on the identifiers (name, identity document number, date of birth, etc.) supplied
        by the client. NRH Intelligence is not responsible for errors arising from incorrect identifiers.</li>

        <li>This report is not, and shall not be construed as, a recommendation or endorsement of the candidate
        for any purpose. The client retains sole responsibility for hiring, contracting, or business decisions.</li>

        <li>NRH Intelligence is not liable for omissions arising from records that are sealed, expunged, restricted,
        or otherwise unavailable through lawful access channels.</li>

        <li>The methodology, data sources, and analyst notes underlying this report are the proprietary
        intellectual property of NRH Intelligence and may not be reproduced, in whole or in part, without express
        written permission.</li>

        <li>Any dispute arising from this report shall be governed by the laws of Malaysia, with exclusive
        jurisdiction in the Malaysian courts.</li>

        <li>NRH Intelligence will not use the name of its client in any of its documents for advertising purposes
        unless written confirmation from an authorised representative of the client is obtained.</li>

        <li>NRH Intelligence will not reveal to any individual or organisation the client's name. Likewise, the
        client is <strong>strictly prohibited</strong> from revealing any information pertaining to NRH
        Intelligence and from disclosing the search conducted on the subject to the subject for any purpose
        whatsoever.</li>
    </ol>
</div>

<div class="end-of-report">— END OF REPORT —</div>

</body>
</html>
