<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>NRH Compliance Screening Report — {{ $reference }}</title>
<style>
@page { margin: 82px 45px 52px 45px; }
* { box-sizing: border-box; }
body { font-family: 'Courier Prime', 'Courier', monospace; font-size: 8.5pt; color: #111; line-height: 1.55; }

/* ── Persistent header ── */
.ph { position: fixed; top: -68px; left: 0; right: 0; height: 58px; border-bottom: 2px solid #023527; }
.ph table { width: 100%; height: 55px; border-collapse: collapse; }
.ph td { border: none; vertical-align: bottom; padding: 0; }
.ph .ph-ref { font-size: 7.5pt; color: #555; font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; }
.ph .ph-logo { text-align: center; vertical-align: top; }
.ph .ph-logo img { height: 46px; }
.ph .ph-conf { text-align: right; font-size: 7pt; color: #888; font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; }

/* ── Persistent footer ── */
.pf { position: fixed; bottom: -38px; left: 0; right: 0; height: 22px;
      border-top: 1px solid #023527; padding-top: 3px;
      font-size: 7pt; color: #888; text-align: center;
      font-family: 'Oswald', sans-serif; letter-spacing: 0.03em; }

/* ── Section headers ── */
.sh  { background: #023527; color: #d4af37; padding: 7px 12px; font-weight: bold;
       font-size: 9.5pt; margin-top: 12px; margin-bottom: 0; letter-spacing: 0.1em;
       font-family: 'Oswald', sans-serif; }
.shs { background: #d4af37; color: #1a1a1a; padding: 5px 10px; font-weight: bold;
       font-size: 9pt; margin-top: 0; margin-bottom: 0;
       font-family: 'Oswald', sans-serif; letter-spacing: 0.05em; }
.shd { background: #1a3a2a; color: #d4af37; padding: 4px 10px; font-size: 8pt;
       font-weight: bold; letter-spacing: 0.14em; margin-top: 10px; margin-bottom: 0;
       font-family: 'Oswald', sans-serif; }

/* ── Tables ── */
table { border-collapse: collapse; }
table.rt { width: 100%; margin-bottom: 8px; page-break-inside: avoid; }
table.rt td, table.rt th { border: 1px solid #2a2a2a; padding: 5px 8px; vertical-align: top; }
table.rt th.lbl { background: #023527; color: #d4af37; font-weight: bold; text-align: left;
                  font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; font-size: 8.5pt; }
table.rt td.val { background: #fff; }
table.rt tr.div td { background: #d4af37; color: #1a1a1a; font-weight: bold; padding: 5px 10px;
                     font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; }

/* ── Risk indicators ── */
.ri-h { color: #c4453a; font-weight: bold; }
.ri-m { color: #d97706; font-weight: bold; }
.ri-l { color: #046c4e; font-weight: bold; }
.ri-n { color: #4a90d9; font-weight: bold; }

/* ── Result cells ── */
.res-clean  { background: #ecfdf5; color: #023527; font-weight: bold; }
.res-record { background: #fef2f2; color: #c4453a; font-weight: bold; }
.res-adverse{ background: #fff7ed; color: #c2410c; font-weight: bold; }
.res-nil    { background: #f0f4f8; color: #4a5568; font-style: italic; }
.res-prog   { background: #f5f5f5; color: #666; font-style: italic; }

/* ── Cover ── */
.cov-logo  { text-align: center; margin: 4px 0 8px; }
.cov-logo img { height: 84px; }
.cov-title { text-align: center; font-size: 17pt; font-weight: bold; color: #023527;
             letter-spacing: 0.08em; margin: 6px 0 2px;
             font-family: 'Oswald', sans-serif; }
.cov-sub   { text-align: center; font-size: 8pt; color: #888; letter-spacing: 0.2em;
             margin-bottom: 10px; text-transform: uppercase;
             font-family: 'Oswald', sans-serif; }

/* ── Info boxes ── */
.clause-box { border: 1px solid #023527; background: #f8fdf9; padding: 8px 12px;
              margin: 8px 0; font-size: 8pt; line-height: 1.6; }
.clause-box .ct { font-weight: bold; color: #023527; font-size: 8.5pt; text-transform: uppercase; margin-bottom: 3px; }
.disc-box   { border: 1px solid #ccc; background: #fafafa; padding: 8px 12px;
              margin: 8px 0; font-size: 8pt; line-height: 1.6; }
.disc-box .ct { font-weight: bold; color: #c2410c; font-size: 8.5pt; text-transform: uppercase; margin-bottom: 3px; }
.disc-box ul { padding-left: 16px; margin: 4px 0; }
.disc-box ul li { margin-bottom: 2px; }

/* ── Risk matrix summary table (per candidate) ── */
table.rmt { width: 100%; margin-bottom: 10px; }
table.rmt th { background: #023527; color: #d4af37; font-weight: bold; padding: 5px 8px;
               border: 1px solid #111; font-size: 8.5pt; text-align: left;
               font-family: 'Oswald', sans-serif; letter-spacing: 0.05em; }
table.rmt td { border: 1px solid #ccc; padding: 5px 8px; font-size: 8pt; vertical-align: middle; }

/* ── DATA REPORT record entries ── */
.rec-entry { border: 1px solid #ddd; margin: 6px 0; page-break-inside: avoid; }
.rec-head  { background: #c4453a; color: #fff; padding: 4px 10px; font-weight: bold; font-size: 8.5pt;
             font-family: 'Oswald', sans-serif; letter-spacing: 0.05em; }
.rec-body  { padding: 6px 10px; }
table.rf   { width: 100%; }
table.rf td { border: 1px solid #eee; padding: 4px 8px; font-size: 8pt; }
table.rf td.rfl { background: #f5f5f0; font-weight: bold; width: 32%; }

/* ── Helpers ── */
.pb   { page-break-after: always; }
.pi   { page-break-inside: avoid; }
.small{ font-size: 8pt; }
.muted{ color: #666; }
.bold { font-weight: bold; }
.ital { font-style: italic; }
.tc   { text-align: center; }
.mb8  { margin-bottom: 8px; }
.mt8  { margin-top: 8px; }
.eor  { text-align: center; font-weight: bold; color: #023527;
        margin: 30px 0 10px; font-size: 11pt; letter-spacing: 0.05em; }
ol.dl { padding-left: 18px; margin: 6px 0; }
ol.dl li { margin-bottom: 5px; font-size: 8.5pt; line-height: 1.55; }
</style>
</head>
<body>

@php
    /* ─── Inline helpers ─────────────────────────────── */
    $getResultType = function(string $st, array $f): string {
        if (!empty($f['result_type'])) return $f['result_type'];
        return match($st) {
            'complete'    => 'clean',
            'flagged'     => 'record_identified',
            'in_progress' => 'in_progress',
            default       => 'not_requested',
        };
    };
    $getRiskLevel = function(string $st, array $f): string {
        if (!empty($f['risk_level'])) return $f['risk_level'];
        return match($st) { 'complete' => 'low', 'flagged' => 'high', default => 'nil' };
    };
    $riskBadge = function(string $lv): string {
        return match($lv) {
            'high'   => '<span class="ri-h">&#9679; HIGH</span>',
            'medium' => '<span class="ri-m">&#9679; MEDIUM</span>',
            'low'    => '<span class="ri-l">&#9679; LOW</span>',
            default  => '<span class="ri-n">&#9679; NIL</span>',
        };
    };
    $resultLabel = function(string $t): string {
        return match($t) {
            'clean'            => 'CLEAN RESULT',
            'record_identified'=> 'RECORD IDENTIFIED',
            'adverse'          => 'ADVERSE RESULT',
            'not_requested'    => 'SCREENING NOT REQUESTED',
            'in_progress'      => 'IN PROGRESS',
            default            => 'PENDING',
        };
    };
    $resultCss = function(string $t): string {
        return match($t) {
            'clean'            => 'res-clean',
            'record_identified'=> 'res-record',
            'adverse'          => 'res-adverse',
            'not_requested'    => 'res-nil',
            default            => 'res-prog',
        };
    };
    $riskStatusText = function(string $lv, string $t, array $f): string {
        if (!empty($f['risk_status_text'])) return $f['risk_status_text'];
        return match(true) {
            $t === 'clean'         => 'Low Risk – Candidate cleared for compliance integrity.',
            $t === 'not_requested' => 'Nil – Screening not requested for this scope.',
            $t === 'in_progress'   => 'Investigation in progress. Findings will appear in the final report.',
            $lv === 'high'         => 'High – Adverse record identified. Enhanced due diligence required.',
            $lv === 'medium'       => 'Medium – Record identified. Further review recommended.',
            default                => 'Low Risk – No adverse findings identified.',
        };
    };
    $getImplication = function(string $t, array $f): string {
        if (!empty($f['implication'])) return $f['implication'];
        return match($t) {
            'clean'            => 'No Issues Found',
            'not_requested'    => 'Not Screened',
            'in_progress'      => 'Pending',
            'record_identified'=> 'Record Found',
            'adverse'          => 'Adverse Finding',
            default            => '—',
        };
    };

    /* Consent summary */
    $consents       = $candidates->map(fn ($c) => $c->latestConsent)->filter();
    $consentedCount = $consents->count();
    $totalCandidates= $candidates->count();
@endphp

{{-- ══ Persistent header & footer ══ --}}
<div class="ph">
    <table><tr>
        <td class="ph-ref" style="width:35%;">REP NO: {{ $reference }}</td>
        <td class="ph-logo" style="width:30%;"><img src="{{ $logoSrc }}" alt="NRH Intelligence"></td>
        <td class="ph-conf" style="width:35%;">PRIVATE &amp; CONFIDENTIAL</td>
    </tr></table>
</div>
<div class="pf">NRH Intelligence Sdn. Bhd. &nbsp;·&nbsp; Integrity With Intelligence &nbsp;·&nbsp; {{ $reference }}</div>

{{-- ══════════════════════════════════════════
     PAGE 1 — REPORT PROFILE
═══════════════════════════════════════════ --}}

<div class="cov-logo"><img src="{{ $logoSrc }}" alt="NRH Intelligence"></div>
<div class="cov-title">NRH INTELLIGENCE</div>
<div class="cov-sub">Compliance Screening Report &nbsp;—&nbsp; Private &amp; Confidential</div>

<div class="sh" style="margin-top:4px;">REPORT PROFILE</div>
<table class="rt">
    <tr><th class="lbl" style="width:38%;">REPORT REF NO</th>        <td class="val bold">{{ $reference }}</td></tr>
    <tr><th class="lbl">ENGAGING COMPANY</th>                          <td class="val">{{ $customer->name ?? '—' }}</td></tr>
    <tr><th class="lbl">REQUEST DATE</th>                              <td class="val">{{ $request->created_at->format('d F Y') }}</td></tr>
    <tr><th class="lbl">INFO TYPE</th>                                 <td class="val">{{ strtoupper($request->type ?? 'DATA & SKILL SCREENING') }}</td></tr>
    <tr><th class="lbl">AUTHORIZED REQUESTOR</th>                     <td class="val">{{ $customer->contact_name ?? '—' }}</td></tr>
    <tr><th class="lbl">REQUESTOR EMAIL</th>                           <td class="val">{{ $customer->contact_email ?? '—' }}</td></tr>
    <tr><th class="lbl">REQUESTOR CONTACT</th>                        <td class="val">{{ $customer->contact_phone ?? '—' }}</td></tr>
    <tr><th class="lbl">NEW ADD-ON (If Any)</th>                      <td class="val">{{ data_get($request->meta, 'addon_date') ?: 'NIL' }}</td></tr>
</table>

<div class="shs">NRH INTERNAL</div>
<table class="rt">
    <tr><th class="lbl" style="width:38%;">NRH RESEARCH OFFICER</th>  <td class="val">{{ data_get($request->meta, 'analyst') ?: '—' }}</td></tr>
    <tr><th class="lbl">COMPLIANCE EDITOR</th>                         <td class="val">{{ data_get($request->meta, 'editor') ?: '—' }}</td></tr>
    <tr><th class="lbl">INTERIM REP 1 DATE</th>                        <td class="val">{{ $completionBasic ?? '—' }}</td></tr>
    <tr><th class="lbl">INTERIM REP 2 DATE</th>                        <td class="val">{{ $completionPrelim ?? '—' }}</td></tr>
    <tr><th class="lbl">FULL REP DATE</th>                             <td class="val">{{ $completionFull ?? '—' }}</td></tr>
    <tr><th class="lbl">REVISED REP DATE</th>                          <td class="val">{{ data_get($request->meta, 'revised_date') ?: '—' }}</td></tr>
</table>

@if(! empty($reportType))
<div style="margin:4px 0 6px; padding:5px 12px; background:#f5ecd1; border-left:3px solid #d4af37; font-size:8.5pt;">
    <strong>{{ strtoupper($reportType) }} REPORT — VERSION {{ $reportVersion }}</strong>
    @if(! empty($reportHash))
    &nbsp;·&nbsp;<span style="font-family:'DejaVu Sans Mono',monospace; font-size:7.5pt; color:#666;">SHA: {{ $reportHash }}</span>
    @endif
</div>
@endif

<div class="clause-box">
    <div class="ct">COMPLIANCE CLAUSE</div>
    This report confirms that valid consent has been obtained from the data subject. All personal data has been
    collected, processed, and safeguarded in compliance with the Personal Data Protection Act 2010 (Act 709),
    as amended by the Personal Data Protection (Amendment) Act 2024 and international standards (ISO 27001 / ISO 31000).
    @if($consentedCount > 0)
    Consent records on file for {{ $consentedCount }} of {{ $totalCandidates }} candidate(s).
    @endif
</div>

<div class="disc-box">
    <div class="ct">LEGAL DISCLAIMER</div>
    <ul>
        <li><strong>Permitted Use:</strong> Legitimate business purposes only.</li>
        <li><strong>Prohibited Use:</strong> Fraud, stalking, identity theft, or illegal activity.</li>
        <li><strong>Consumer Report Limitation:</strong> NRH is not a consumer reporting agency.</li>
        <li><strong>Confidentiality:</strong> Information must not be disclosed to unauthorised parties.</li>
    </ul>
</div>

<div class="sh">GLOSSARY</div>
<table class="rt">
    <tr><th class="lbl" style="width:32%;">CLEAN RESULT</th>           <td class="val">No records or adverse findings identified.</td></tr>
    <tr><th class="lbl">ADVERSE RESULT</th>                             <td class="val">Negative findings detected.</td></tr>
    <tr><th class="lbl">RECORD IDENTIFIED</th>                          <td class="val">Record found in screening.</td></tr>
    <tr><th class="lbl">SCREENING NOT REQUESTED</th>                    <td class="val">This scope was not included in the screening order.</td></tr>
</table>

<div class="sh">RISK MATRIX INTERPRETATION</div>
<table class="rt" style="margin-bottom:0;">
    <tr>
        <td style="background:#023527; color:#fff; width:10%; text-align:center; font-size:14pt; padding:6px;">&#9679;</td>
        <td style="background:#c4453a; width:14%; text-align:center; font-size:10pt; color:#fff; font-weight:bold; padding:6px; border:1px solid #2a2a2a;">HIGH</td>
        <td style="padding:5px 10px; font-size:8.5pt; border:1px solid #2a2a2a;">Significant risk exposure. Immediate attention and enhanced due diligence required.</td>
    </tr>
    <tr>
        <td style="background:#023527; color:#fff; text-align:center; font-size:14pt; padding:6px; border:1px solid #2a2a2a;">&#9679;</td>
        <td style="background:#d97706; text-align:center; font-size:10pt; color:#fff; font-weight:bold; padding:6px; border:1px solid #2a2a2a;">MEDIUM</td>
        <td style="padding:5px 10px; font-size:8.5pt; border:1px solid #2a2a2a;">Moderate risk exposure. Further review and monitoring recommended.</td>
    </tr>
    <tr>
        <td style="background:#023527; color:#fff; text-align:center; font-size:14pt; padding:6px; border:1px solid #2a2a2a;">&#9679;</td>
        <td style="background:#046c4e; text-align:center; font-size:10pt; color:#fff; font-weight:bold; padding:6px; border:1px solid #2a2a2a;">LOW</td>
        <td style="padding:5px 10px; font-size:8.5pt; border:1px solid #2a2a2a;">No significant risk exposure identified. Candidate cleared for compliance integrity.</td>
    </tr>
</table>

<div class="pb"></div>

{{-- ══════════════════════════════════════════
     CANDIDATE SECTIONS
═══════════════════════════════════════════ --}}
@foreach($candidates as $candidateIndex => $candidate)

<div class="sh">CANDIDATE {{ $candidateIndex + 1 }} — {{ strtoupper($candidate->name) }}</div>

{{-- ── Candidate Info ── --}}
<div class="shs">CANDIDATE INFO</div>
<table class="rt">
    <tr>
        <th class="lbl" style="width:38%;">CANDIDATE NAME</th>
        <td class="val bold">{{ $candidate->name }}</td>
    </tr>
    <tr>
        <th class="lbl">{{ $candidate->identityType ? strtoupper($candidate->identityType->name) : 'ID' }} / PASSPORT NO</th>
        <td class="val">{{ $candidate->identity_number }}</td>
    </tr>
    @if($candidate->mobile)
    <tr><th class="lbl">CONTACT</th><td class="val">{{ $candidate->mobile }}</td></tr>
    @endif
    <tr>
        <th class="lbl">CANDIDATE STATUS</th>
        <td class="val bold">{{ strtoupper(str_replace('_', ' ', $candidate->status)) }}</td>
    </tr>
    @php $cn = $candidate->latestConsent; @endphp
    @if($cn)
    <tr>
        <th class="lbl">CONSENT ON FILE</th>
        <td class="val small">
            {{ $cn->consented_at->format('d M Y, H:i') }} &nbsp;·&nbsp;
            {{ \App\Models\ConsentRecord::evidenceTypes()[$cn->evidence_type] ?? $cn->evidence_type }}
            <span class="muted">&nbsp;·&nbsp; v{{ $cn->consent_version }}</span>
        </td>
    </tr>
    @endif
</table>

{{-- ── Risk Matrix — per candidate summary ── --}}
<div class="sh">RISK MATRIX — COMPLIANCE SCREENING REPORT</div>
<table class="rmt">
    <tr>
        <th style="width:36%;">SCOPE</th>
        <th style="width:22%;">RESULT</th>
        <th style="width:16%; text-align:center;">RISK LEVEL</th>
        <th style="width:26%;">IMPLICATION</th>
    </tr>
    @foreach($candidate->scopeTypes as $scope)
    @php
        $pSt = $scope->pivot->status ?? 'new';
        $pF  = $scope->pivot->findings ?? [];
        $pT  = $getResultType($pSt, $pF);
        $pLv = $getRiskLevel($pSt, $pF);
        $pIm = $getImplication($pT, $pF);
    @endphp
    <tr>
        <td>
            {{ $scope->name }}
            @if($scope->category)<br><span class="muted small">{{ $scope->category }}</span>@endif
        </td>
        <td class="{{ match($pT) { 'clean','not_requested' => 'ri-l', 'record_identified','adverse' => 'ri-h', default => 'muted' } }} bold">
            {{ $resultLabel($pT) }}
        </td>
        <td style="text-align:center;">{!! $riskBadge($pLv) !!}</td>
        <td class="small muted ital">{{ $pIm }}</td>
    </tr>
    @endforeach
</table>

<div class="pb"></div>

{{-- ── DATA REPORT blocks ── --}}
<div class="sh">COMPLIANCE SCREENING REPORT — {{ strtoupper($candidate->name) }}</div>

@foreach($candidate->scopeTypes as $scope)
@php
    $pSt      = $scope->pivot->status ?? 'new';
    $findings = $scope->pivot->findings ?? [];
    $rType    = $getResultType($pSt, $findings);
    $rLevel   = $getRiskLevel($pSt, $findings);
    $rLabel   = $resultLabel($rType);
    $rCss     = $resultCss($rType);
    $rStat    = $riskStatusText($rLevel, $rType, $findings);
    $comment  = $findings['comment'] ?? null;
    $records  = $findings['records'] ?? [];    // new structured format
    $legacyRec= $findings['record']  ?? [];    // old key-value format
    $verMethod= $findings['verification_method'] ?? ($scope->description ?? null);
    $scopeDesc= $findings['scope_description'] ?? ($scope->description ?? null);
    $tatHours = $scope->pivot->tatHours();
    $finished = in_array($pSt, ['complete', 'flagged']);
@endphp
<div class="pi mb8">
    <div class="shd">DATA REPORT</div>
    <table class="rt" style="margin-bottom:0;">
        <tr>
            <th class="lbl" style="width:22%;">SCOPE</th>
            <td class="val">
                <span class="bold">{{ strtoupper($scope->name) }}</span>
                @if($scope->category)<span class="muted small"> — {{ $scope->category }}</span>@endif
                @if($scopeDesc && $scopeDesc !== $scope->name)
                <br><span class="small" style="color:#444; display:block; margin-top:2px;">{{ $scopeDesc }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="lbl">RESULT</th>
            <td class="val {{ $rCss }}">{{ $rLabel }}</td>
        </tr>
        <tr>
            <th class="lbl" style="background:#1a3a2a;">RISK STATUS</th>
            <td class="val">{!! $riskBadge($rLevel) !!}&nbsp; {{ $rStat }}</td>
        </tr>
        @if($comment)
        <tr>
            <th class="lbl" style="background:#1a3a2a;">NOTES</th>
            <td class="val">{!! nl2br(e($comment)) !!}</td>
        </tr>
        @endif
    </table>

    {{-- New structured record entries --}}
    @if(!empty($records))
        @foreach($records as $ri => $rec)
        <div class="rec-entry">
            <div class="rec-head">
                RECORD {{ count($records) > 1 ? ($ri + 1).' IDENTIFIED' : 'IDENTIFIED' }}
                @if(!empty($rec['title'])) – {{ strtoupper($rec['title']) }}@endif
            </div>
            <div class="rec-body">
                @if(!empty($rec['act']))<div class="bold small" style="margin-bottom:3px;">{{ strtoupper($rec['act']) }}</div>@endif
                @if(!empty($rec['section']))<div class="small muted" style="margin-bottom:3px;">{{ $rec['section'] }}</div>@endif
                @if(!empty($rec['description']))<div class="small" style="margin-bottom:4px;"><strong>Offence:</strong> {{ $rec['description'] }}</div>@endif
                @if(!empty($rec['penalty']))<div class="small" style="margin-bottom:4px;"><strong>Penalty:</strong> {{ $rec['penalty'] }}</div>@endif
                @if(!empty($rec['fields']))
                <table class="rf" style="margin:4px 0;">
                    @foreach($rec['fields'] as $fk => $fv)
                    <tr><td class="rfl">{{ $fk }}</td><td>{{ $fv }}</td></tr>
                    @endforeach
                </table>
                @endif
                @if(!empty($rec['verdict']))
                <div class="bold small ri-h" style="margin-top:4px;">Verdict: {{ strtoupper($rec['verdict']) }}</div>
                @endif
                @if(!empty($rec['risk_text']))
                <div class="small" style="margin-top:3px;">{!! $riskBadge($rec['risk_level'] ?? $rLevel) !!} {{ $rec['risk_text'] }}</div>
                @endif
            </div>
        </div>
        @endforeach

    {{-- Legacy key-value record (backwards compatible) --}}
    @elseif(!empty($legacyRec))
        <div class="rec-entry">
            <div class="rec-head">RECORD IDENTIFIED</div>
            <div class="rec-body">
                <table class="rf">
                    @foreach($legacyRec as $rk => $rv)
                    <tr><td class="rfl">{{ $rk }}</td><td>{{ $rv }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>

    {{-- Auto-generated clean/not-requested description --}}
    @elseif($rType === 'clean')
        <div style="padding:5px 10px; background:#ecfdf5; border:1px solid #b7f2d4; margin:4px 0; font-size:8.5pt; color:#023527;">
            No risk match identified based on the identity details provided within this scope of screening.
        </div>
    @elseif($rType === 'not_requested')
        <div style="padding:5px 10px; background:#f0f4f8; border:1px solid #cdd9e5; margin:4px 0; font-size:8.5pt; color:#4a5568;">
            This scope was not requested. No screening was conducted.
        </div>
    @endif

    <table class="rt" style="margin-top:4px; margin-bottom:0;">
        <tr>
            <th class="lbl" style="width:22%; background:#1a3a2a;">VERIFICATION METHOD</th>
            <td class="val small">
                @if($verMethod){{ $verMethod }}@else
                Verification was conducted using the candidate's Name and ID against authoritative databases and declared information.
                @endif
                <br><span class="muted ital">Compliance aligned with PDPA 2010 (Act 709), as amended 2024, and ISO 27001 / ISO 31000 standards.</span>
            </td>
        </tr>
        @if($scope->turnaround_hours)
        <tr>
            <th class="lbl" style="background:#1a3a2a;">SLA / TAT</th>
            <td class="val small">
                Target: {{ $scope->turnaround_hours }}h.
                @if($scope->pivot->assigned_at)
                    Actual: <strong>{{ $tatHours }}h</strong>
                    @if($finished && $tatHours > $scope->turnaround_hours)
                        <span class="ri-h">({{ round($tatHours - $scope->turnaround_hours, 1) }}h over SLA)</span>
                    @elseif($finished)
                        <span class="ri-l">(within SLA)</span>
                    @endif
                @endif
            </td>
        </tr>
        @endif
    </table>
</div>
@endforeach

@if(!$loop->last)<div class="pb"></div>@endif
@endforeach

<div class="pb"></div>

{{-- ══════════════════════════════════════════
     COMPETENCY & PROFESSIONAL VERIFICATION
═══════════════════════════════════════════ --}}
<div class="sh">COMPETENCY &amp; PROFESSIONAL VERIFICATION REPORT</div>

@foreach($candidates as $candidate)
@php
    $profScopes = $candidate->scopeTypes->filter(fn($s) =>
        collect(['academic','qualification','credential','employment','reference','education'])
            ->contains(fn($kw) =>
                str_contains(strtolower($s->name), $kw) ||
                str_contains(strtolower($s->category ?? ''), $kw)
            )
    );
@endphp
@if($profScopes->count())
<div class="shs" style="margin-top:8px;">{{ strtoupper($candidate->name) }}</div>
<table class="rt">
    @foreach($profScopes as $ps)
    @php
        $psT = $getResultType($ps->pivot->status ?? 'new', $ps->pivot->findings ?? []);
        $psCm = ($ps->pivot->findings ?? [])['comment'] ?? null;
    @endphp
    <tr class="div"><td colspan="2">{{ strtoupper($ps->name) }}</td></tr>
    <tr>
        <th class="lbl" style="width:38%;">RESULT</th>
        <td class="val {{ $resultCss($psT) }}">{{ $resultLabel($psT) }}</td>
    </tr>
    @if($psCm)
    <tr><th class="lbl">DETAILS</th><td class="val">{!! nl2br(e($psCm)) !!}</td></tr>
    @endif
    @endforeach
</table>
@else
<div class="shs" style="margin-top:8px;">ACADEMIC CREDENTIAL VERIFICATION</div>
<table class="rt">
    <tr>
        <th class="lbl" style="width:38%;">PROVIDED INFORMATION</th>
        <td class="val muted ital small">Academic and professional credential verification details will be reported upon completion of verification with the relevant institutions.</td>
    </tr>
</table>
@endif
@endforeach

<div class="pb"></div>

{{-- ══════════════════════════════════════════
     END OF REPORT + FULL DISCLAIMER
═══════════════════════════════════════════ --}}
<div class="eor">— END OF REPORT —</div>

<div class="sh">NRH LEGAL DISCLAIMER</div>

<div style="margin-top:8px; font-size:8.5pt; font-weight:bold; color:#023527; text-transform:uppercase; margin-bottom:4px;">Permitted Use of Information</div>
<div style="font-size:8.5pt; line-height:1.6; margin-bottom:8px;">
    Information obtained from NRH is to be used solely for:
    <ul style="padding-left:16px; margin:4px 0;">
        <li>Legitimate business purposes involving a pre-existing or potential business relationship with the subject.</li>
        <li>Uses that will not cause emotional, physical, or financial harm to any person, organisation, or third party.</li>
        <li>Internal purposes only.</li>
    </ul>
</div>

<ol class="dl">
    <li><strong>Prohibited Use:</strong> Any use of NRH information to plan stalking, identity theft, fraud, or any illegal activity is strictly prohibited and will be reported to the authorities.</li>
    <li><strong>Consumer-Report Limitation:</strong> NRH is not a consumer-reporting agency. Data from NRH should not be used to determine eligibility for credit, insurance, or other purposes typically requiring a consumer report, except in connection with hiring decisions.</li>
    <li><strong>Verification Requirement:</strong> Any adverse action based on NRH data must be verified with another source. NRH data is to be used as lead information only.</li>
    <li><strong>Representation by Client:</strong> Clients must not misrepresent themselves, their company, or the purpose for accessing NRH services.</li>
    <li><strong>Data Sources &amp; Methodology:</strong> NRH develops information using standard investigative methods, including public records, third-party sources, and creditor networks.</li>
    <li><strong>No Guarantee:</strong> Searches are conducted on a best-effort basis. NRH makes no guarantees about results but will perform services professionally and without gross negligence.</li>
    <li><strong>Pricing &amp; Service Modifications:</strong> NRH reserves the right to amend pricing, services, or refuse specific client requests at any time. NRH will cooperate with law enforcement if misuse is suspected.</li>
    <li><strong>Legality of Searches:</strong> Clients warrant that searches comply with local laws. Orders are processed immediately and are non-cancellable upon receipt.</li>
    <li><strong>Prohibited Purposes:</strong> NRH services may not be used for entrapment, sting operations, or targeting NRH, its employees, vendors, clients, affiliates, or officers.</li>
    <li><strong>Reporting &amp; Consent:</strong> Any discrepancies in search results must be reported within 10 days. Clients agree to all NRH terms as amended from time to time.</li>
    <li><strong>Client Confidentiality:</strong> NRH will not use client names for advertising without written consent and will not disclose client identities. Clients are likewise prohibited from disclosing NRH information or searches conducted to the subject or any third party.</li>
</ol>

</body>
</html>
