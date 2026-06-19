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
.ph .ph-cand { vertical-align: top; font-family: 'Oswald', sans-serif; line-height: 1.25; }
.ph .ph-cand-name { font-size: 9pt; font-weight: bold; color: #023527; letter-spacing: 0.03em; }
.ph .ph-cand-id { font-size: 7.5pt; color: #555; letter-spacing: 0.04em; }
.ph .ph-logo { text-align: right; vertical-align: top; }
.ph .ph-logo img { height: 46px; }

/* ── Persistent footer ── */
.pf { position: fixed; bottom: -38px; left: 0; right: 0; height: 22px;
      border-top: 1px solid #023527; padding-top: 3px;
      font-size: 7pt; color: #888; text-align: center;
      font-family: 'Oswald', sans-serif; letter-spacing: 0.03em; }

/* ── Section headers (Word palette) ── */
/* .sh = black top-level section bar */
.sh  { background: #000; color: #fff; padding: 6px 12px; font-weight: bold;
       font-size: 9.5pt; margin-top: 12px; margin-bottom: 0; letter-spacing: 0.08em;
       font-family: 'Oswald', sans-serif; }
/* gold sub-title / divider bar (#C5A82D) */
.sh-gld { background: #C5A82D; color: #000; padding: 5px 12px; font-weight: bold;
          font-size: 9pt; margin-top: 8px; margin-bottom: 0; letter-spacing: 0.05em;
          font-family: 'Oswald', sans-serif; }
/* cream terminology / index header bar (#DDD9C3) */
.sh-crm { background: #DDD9C3; color: #000; padding: 5px 12px; font-weight: bold;
          font-size: 9pt; margin-top: 8px; margin-bottom: 0; letter-spacing: 0.05em;
          font-family: 'Oswald', sans-serif; }
/* olive header bar (#76923C) */
.sh-olv { background: #76923C; color: #fff; padding: 5px 12px; font-weight: bold;
          font-size: 9pt; margin-top: 8px; margin-bottom: 0; letter-spacing: 0.05em;
          font-family: 'Oswald', sans-serif; }
/* legacy alias — kept dark green for any unconverted usage */
.sh-erm { background: #053827; color: #fff; padding: 4px 12px 8px; font-weight: bold;
          font-size: 9pt; line-height: 1.2; margin-top: 8px; margin-bottom: 0; letter-spacing: 0.06em;
          font-family: 'Oswald', sans-serif; }
.shs { background: #C5A82D; color: #000; padding: 5px 10px; font-weight: bold;
       font-size: 9pt; margin-top: 0; margin-bottom: 0;
       font-family: 'Oswald', sans-serif; letter-spacing: 0.05em; }
.shd { background: #053827; color: #fff; padding: 4px 10px 7px; font-size: 8.5pt; line-height: 1.2;
       font-weight: bold; letter-spacing: 0.08em; margin-top: 10px; margin-bottom: 0;
       font-family: 'Oswald', sans-serif; }

/* ── Tables ── */
table { border-collapse: collapse; }
table.rt { width: 100%; margin-bottom: 8px; page-break-inside: avoid; }
table.rt td, table.rt th { border: 1px solid #2a2a2a; padding: 4px 8px 7px; vertical-align: middle; line-height: 1.2; }
table.rt th.lbl { background: #76923C; color: #fff; font-weight: bold; text-align: left;
                  font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; font-size: 8.5pt; }
table.rt td.val { background: #fff; }
table.rt tr.div td { background: #d4af37; color: #1a1a1a; font-weight: bold; padding: 5px 10px;
                     font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; }

/* ── Word-exact profile tables (Page 1: REPORT PROFILE / NRH INTERNAL) ── */
.sh-pt { background: #053827; color: #fff; padding: 5px 12px 8px; font-weight: bold;
         font-size: 9.5pt; line-height: 1.2; margin-top: 12px; margin-bottom: 0; letter-spacing: 0.08em;
         font-family: 'Oswald', sans-serif; text-align: center; }
table.pt { width: 100%; margin-bottom: 8px; page-break-inside: avoid; }
table.pt th, table.pt td { border: 1px solid #000; padding: 5px 8px 8px; vertical-align: middle;
                           text-align: left; font-size: 8.5pt; line-height: 1.2; }
table.pt th.pt-lbl { background: #fff; color: #000; font-weight: bold;
                     font-family: 'Oswald', sans-serif; letter-spacing: 0.03em; }
table.pt td.pt-val { background: #fff; color: #000; }

/* ── Word-exact black section bars + plain content (Page 2) ── */
table.sh-blk { width: 100%; margin: 12px 0 6px; border-collapse: collapse; page-break-inside: avoid; }
table.sh-blk td { background: #000; color: #fff; height: 28px; padding: 4px 12px 7px;
                  font-weight: bold; font-size: 9.5pt; line-height: 1.2; letter-spacing: 0.08em;
                  font-family: 'Oswald', sans-serif; text-align: left; vertical-align: middle; }
.body-p { font-size: 8.5pt; line-height: 1.6; margin: 0 0 10px; text-align: justify; }
ul.body-ul { padding-left: 18px; margin: 0 0 10px; font-size: 8.5pt; line-height: 1.6; }
ul.body-ul li { margin-bottom: 3px; }
table.lgt { width: 100%; margin: 0 0 10px; page-break-inside: avoid; }
table.lgt td { border: 1px solid #000; padding: 5px 8px 8px; vertical-align: middle; line-height: 1.2;
               font-size: 8.5pt; text-align: left; }
table.lgt td.lg-lbl { color: #0070C0; font-weight: bold; font-family: 'Oswald', sans-serif; width: 43%; }
table.lgt td.lg-dot { text-align: center; width: 14%; font-size: 12pt; line-height: 1; padding: 6px 8px; }
table.lgt td.lg-lvl { width: 43%; font-weight: bold; }

/* ── Risk indicators ── */
.ri-h { color: #c4453a; font-weight: bold; }
.ri-m { color: #d97706; font-weight: bold; }
.ri-l { color: #046c4e; font-weight: bold; }
.ri-n { color: #4a90d9; font-weight: bold; }
.ri-c { color: #7c3aed; font-weight: bold; }

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
             font-family: 'Courier Prime', 'Courier', monospace; }
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
table.rmt th { background: #C5A82D; color: #000; font-weight: bold; padding: 4px 8px 7px;
               border: 1px solid #000; font-size: 8.5pt; text-align: left; line-height: 1.2;
               font-family: 'Oswald', sans-serif; letter-spacing: 0.05em; }
table.rmt td { border: 1px solid #000; padding: 4px 8px 7px; font-size: 8pt; vertical-align: middle; line-height: 1.2; }
table.rmt td.rmt-scope { background: #76923C; color: #fff; font-weight: bold;
               font-family: 'Oswald', sans-serif; }

/* ── Compliance Risk Heatmap ── */
table.hmt { width: 100%; margin-bottom: 10px; }
table.hmt th { background: #C5A82D; color: #000; font-weight: bold; padding: 4px 8px 7px;
               border: 1px solid #000; font-size: 8pt; line-height: 1.2;
               font-family: 'Oswald', sans-serif; letter-spacing: 0.05em; }
table.hmt td { border: 1px solid #000; padding: 4px 8px 7px; font-size: 7.5pt; vertical-align: middle; line-height: 1.2; }
table.hmt td.hm-domain { font-weight: bold; font-family: 'Oswald', sans-serif;
                          font-size: 8pt; letter-spacing: 0.04em; background: #76923C; color: #fff; }

/* ── DATA REPORT block (Word-exact single table, olive 24% label col) ── */
table.drt { width: 100%; margin-bottom: 8px; }
table.drt td, table.drt th { border: 1px solid #000; padding: 4px 8px 7px; vertical-align: middle; font-size: 8.5pt; line-height: 1.2; }
table.drt th.dr-lbl { background: #76923C; color: #fff; font-weight: bold; text-align: left;
                      font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; width: 24%; }
table.drt th.dr-lbl-c { vertical-align: middle; }
table.drt td.dr-scope { background: #C5A82D; color: #000; }
table.drt td.dr-res   { background: #DDD9C3; color: #002060; font-weight: bold;
                        font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; }
.t-red  { color: #C00000; font-weight: bold; }
.t-blue { color: #0070C0; font-weight: bold; }
.t-risk { color: #CC0000; font-weight: bold; }

/* ── Credential / Employment validation matrix ── */
table.cmt { width: 100%; margin: 6px 0 10px; }
table.cmt th { background: #76923C; color: #fff; padding: 3px 8px 6px; border: 1px solid #000;
               font-size: 8pt; line-height: 1.2; font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; }
table.cmt td { border: 1px solid #000; padding: 3px 8px 6px; font-size: 8pt; vertical-align: middle; line-height: 1.2; }
table.cmt td.cm-aspect { font-weight: bold; background: #fff; color: #000; width: 20%; }
/* gold span-header row inside matrix tables */
table.cmt td.cm-gold, table.rct td.cm-gold, table.ref-t td.cm-gold {
    background: #C5A82D; color: #000; font-weight: bold;
    font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; }
/* cream institution / employer divider row */
table.cmt td.cm-crm, table.rct td.cm-crm, table.ref-t td.cm-crm {
    background: #DDD9C3; color: #000; font-weight: bold;
    font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; }
/* black terminology header row */
table.cmt td.cm-blk {
    background: #000; color: #fff; font-weight: bold;
    font-family: 'Oswald', sans-serif; letter-spacing: 0.06em; font-size: 8.5pt; }
.match-M  { background: #ecfdf5; color: #023527; font-weight: bold; }
.match-PM { background: #fffbeb; color: #92400e; font-weight: bold; }
.match-NR { background: #fff7ed; color: #c2410c; font-weight: bold; }
.match-D  { background: #fef2f2; color: #b91c1c; font-weight: bold; }
.risk-low      { color: #046c4e; font-weight: bold; }
.risk-moderate { color: #d97706; font-weight: bold; }
.risk-high     { color: #c4453a; font-weight: bold; }
.risk-critical { color: #7c3aed; font-weight: bold; }

/* ── Recognition matrix ── */
table.rct { width: 100%; margin: 6px 0 10px; }
table.rct th { background: #76923C; color: #fff; padding: 3px 8px 6px; border: 1px solid #000;
               font-size: 8pt; line-height: 1.2; font-family: 'Oswald', sans-serif; }
table.rct td { border: 1px solid #000; padding: 3px 8px 6px; font-size: 8pt; vertical-align: middle; line-height: 1.2; }

/* ── Overall ERM risk box ── */
.erm-overall { padding: 7px 12px; margin: 6px 0; border: 1px solid #2a2a2a; font-size: 8.5pt; }
.erm-overall.low      { background: #ecfdf5; border-color: #6ee7b7; }
.erm-overall.moderate { background: #fffbeb; border-color: #fcd34d; }
.erm-overall.high     { background: #fff7ed; border-color: #fb923c; }
.erm-overall.critical { background: #fef2f2; border-color: #fca5a5; }

/* ── Referee section ── */
table.ref-t { width: 100%; margin: 6px 0; }
table.ref-t th { background: #76923C; color: #fff; padding: 4px 8px; border: 1px solid #000;
                 font-size: 8pt; font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; }
table.ref-t td { border: 1px solid #000; padding: 4px 8px; font-size: 8pt; vertical-align: top; }
table.ref-t td.ref-lbl { background: #76923C; color: #fff; font-weight: bold; width: 32%;
                         font-family: 'Oswald', sans-serif; }
.stars { color: #C5A82D; font-size: 9.5pt; font-family: 'DejaVu Sans', sans-serif; letter-spacing: 0.5px; }
.qa-category { background: #76923C; color: #fff; padding: 3px 8px; font-weight: bold;
               font-size: 8pt; font-family: 'Oswald', sans-serif; margin-top: 6px; }
.qa-reply { padding: 5px 8px; border: 1px solid #ddd; font-size: 8pt; background: #fafafa;
            min-height: 20px; }
.erm-area-strong   { background: #ecfdf5; border-left: 3px solid #046c4e; padding: 5px 8px; margin-bottom: 4px; font-size: 8pt; }
.erm-area-moderate { background: #fffbeb; border-left: 3px solid #d97706; padding: 5px 8px; margin-bottom: 4px; font-size: 8pt; }
.erm-area-weak     { background: #fef2f2; border-left: 3px solid #c4453a; padding: 5px 8px; margin-bottom: 4px; font-size: 8pt; }

/* ── Master Index ── */
table.mi { width: 100%; margin-bottom: 10px; }
table.mi th { background: #DDD9C3; color: #000; padding: 5px 8px; border: 1px solid #000;
              font-size: 8pt; font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; }
table.mi td { border: 1px solid #000; padding: 5px 8px; font-size: 8pt; vertical-align: top; }
table.mi td.mi-lbl { background: #DDD9C3; color: #000; font-weight: bold; }

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
    $dot = fn(string $c): string => '<span style="font-family:\'DejaVu Sans\', sans-serif; color:'.$c.'; font-size:8pt;">&#9679;</span>';
    $riskBadge = function(string $lv) use ($dot): string {
        return match($lv) {
            'high'     => $dot('#CC0000').' <span class="bold">High</span>',
            'medium'   => $dot('#FFC000').' <span class="bold">Moderate</span>',
            'low'      => $dot('#00B050').' <span class="bold">Low</span>',
            'critical' => $dot('#7C3AED').' <span class="bold">Critical</span>',
            default    => $dot('#BFBFBF').' <span class="bold">Nil</span>',
        };
    };
    $resultLabel = function(string $t): string {
        return match($t) {
            'clean'            => 'CLEAN RESULT',
            'record_identified'=> 'RECORD IDENTIFIED',
            'adverse'          => 'ADVERSE RESULT',
            'not_requested'    => 'PENDING',
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
            $t === 'not_requested' => 'Nil – Screening not yet started for this scope.',
            $t === 'in_progress'   => 'Investigation in progress. Findings will appear in the final report.',
            $lv === 'high'         => 'High – Adverse record identified. Enhanced due diligence required.',
            $lv === 'medium'       => 'Moderate – Record identified. Further review recommended.',
            default                => 'Low Risk – No adverse findings identified.',
        };
    };
    $getImplication = function(string $t, array $f): string {
        if (!empty($f['implication'])) return $f['implication'];
        return match($t) {
            'clean'            => 'No Issues Found',
            'not_requested'    => 'Pending',
            'in_progress'      => 'Pending',
            'record_identified'=> 'Record Found',
            'adverse'          => 'Adverse Finding',
            default            => '—',
        };
    };
    $matchBadge = function(string $match): string {
        return match(strtolower($match)) {
            'match'      => '<span class="match-M">&#10003; MATCH</span>',
            'partial'    => '<span class="match-PM">&#8776; PARTIAL MATCH</span>',
            'no_record'  => '<span class="match-NR">&#8709; NO RECORD</span>',
            'discrepancy'=> '<span class="match-D">&#10007; DISCREPANCY</span>',
            default      => $match,
        };
    };
    $riskLabel = function(string $r): string {
        return match(strtolower($r)) {
            'low'      => '<span class="risk-low">Low</span>',
            'moderate' => '<span class="risk-moderate">Moderate</span>',
            'high'     => '<span class="risk-high">High</span>',
            'critical' => '<span class="risk-critical">Critical</span>',
            default    => $r,
        };
    };
    $stars = function(int $n): string {
        return '<span class="stars">' . str_repeat('&#9733;', $n) . str_repeat('&#9734;', max(0, 5 - $n)) . '</span>';
    };
    /* Spelled-out record numbers, Word style: RECORD ONE IDENTIFIED */
    $numWord = fn(int $n): string => [1=>'ONE','TWO','THREE','FOUR','FIVE','SIX','SEVEN','EIGHT','NINE','TEN'][$n] ?? (string) $n;
    /* COLOR & TERM cell: colour dot + match term (Word matrix style) */
    $matchCell = function(string $m) use ($dot): string {
        [$c, $label] = match(strtolower($m)) {
            'match'       => ['#00B050', 'Match'],
            'partial'     => ['#00B0F0', 'Partial Match'],
            'no_record'   => ['#FFC000', 'No Record'],
            'discrepancy' => ['#CC0000', 'Discrepancy'],
            default       => ['#BFBFBF', strtoupper($m)],
        };
        return $dot($c).' <span class="bold">'.$label.'</span>';
    };
    /* Overall ERM risk dot colour */
    $riskDot = fn(string $r): string => match(strtolower($r)) {
        'low'      => $dot('#00B050'),
        'moderate' => $dot('#00B0F0'),
        'high'     => $dot('#FFC000'),
        'critical' => $dot('#CC0000'),
        default    => $dot('#BFBFBF'),
    };
    /* Static recognition + accreditation scenarios (Word matrix rows) */
    $recScenarios = [
        ['REAL + ACCREDITED',             'Recognized by MOHE/MQA',             'Program accredited',     '#00B050', 'Low'],
        ['REAL + NOT ACCREDITED',         'Recognized institution',             'Program not accredited', '#FFC000', 'High'],
        ['FAKE VIRTUAL UNIVERSITY',       'Not recognized',                     'No accreditation',       '#CC0000', 'Critical'],
        ['RECOGNIZED ABROAD ONLY',        'Recognized overseas but not by MQA', 'Accreditation varies',   '#00B050', 'Moderate'],
        ['RECOGNIZED VIRTUAL UNIVERSITY', 'Listed by MOHE/MQA',                 'Program accredited',     '#00B050', 'Low'],
        ['RECOGNIZED VIRTUAL UNIVERSITY', 'Listed by MOHE/MQA',                 'Program not accredited', '#FFC000', 'High'],
    ];

    /* Domain → scope keyword map for heatmap */
    $domainKeywords = [
        'LEGAL RISK'         => ['crime', 'corruption', 'counter terrorism', 'counter-terrorism', 'macc'],
        'INTERNATIONAL RISK' => ['interpol', 'global sanction', 'global sanctions'],
        'FINANCIAL RISK'     => ['aml', 'ctf', 'anti-money', 'securities', 'bursa', 'civil summons', 'credit default', 'bankruptcy', 'insolvency', 'ccris'],
        'POLITICAL RISK'     => ['pep', 'politically exposed'],
        'REPUTATIONAL RISK'  => ['social media', 'deep web', 'dark web'],
        'REGULATORY RISK'    => ['directorship', 'shareholding', 'driving', 'travel', 'immigration', 'labor', 'labour', 'industrial relations', 'academic loan'],
    ];
    $getScopeDomain = function(string $name, string $cat) use ($domainKeywords): string {
        $haystack = strtolower($name . ' ' . $cat);
        foreach ($domainKeywords as $domain => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($haystack, $kw)) return $domain;
            }
        }
        return 'OTHER';
    };
    $aggregateRisk = function(array $levels): string {
        if (in_array('high', $levels))     return 'high';
        if (in_array('medium', $levels))   return 'medium';
        if (in_array('low', $levels))      return 'low';
        return 'nil';
    };
    $domainBg = fn(string $r) => match($r) {
        'high'   => '#fef2f2',
        'medium' => '#fffbeb',
        'low'    => '#ecfdf5',
        default  => '#f0f4f8',
    };

    /* Consent summary */
    $consents       = $candidates->map(fn ($c) => $c->latestConsent)->filter();
    $consentedCount = $consents->count();
    $totalCandidates= $candidates->count();
@endphp

{{-- ══ Persistent header & footer ══ --}}
@php $hdrCand = $candidates->first(); @endphp
<div class="ph">
    <table><tr>
        <td class="ph-cand" style="width:65%;">
            @if($hdrCand)
            <span class="ph-cand-name">{{ strtoupper($hdrCand->name) }}</span><br>
            <span class="ph-cand-id">{{ $hdrCand->identityType ? strtoupper($hdrCand->identityType->name).': ' : '' }}{{ $hdrCand->identity_number }}</span>
            @endif
        </td>
        <td class="ph-logo" style="width:35%;"><img src="{{ $logoSrc }}" alt="NRH Intelligence"></td>
    </tr></table>
</div>
<div class="pf">REP NO: {{ $reference }} &nbsp;·&nbsp; PRIVATE &amp; CONFIDENTIAL &nbsp;·&nbsp; NRH Intelligence Sdn. Bhd.</div>

{{-- ══════════════════════════════════════════
     PAGE 1 — REPORT PROFILE
═══════════════════════════════════════════ --}}

<div class="cov-logo"><img src="{{ $logoSrc }}" alt="NRH Intelligence"></div>
<div class="cov-title">NRH INTELLIGENCE</div>
<div class="cov-sub">Compliance Screening Report —&nbsp;Private &amp; Confidential</div>

<div class="sh-pt" style="margin-top:4px;">REPORT PROFILE</div>
<table class="pt">
    <tr><th class="pt-lbl" style="width:48%;">REPORT REF NO</th>      <td class="pt-val bold">{{ $reference }}</td></tr>
    <tr><th class="pt-lbl">ENGAGING COMPANY</th>                       <td class="pt-val">{{ $customer->name ?? '—' }}</td></tr>
    <tr><th class="pt-lbl">REQUEST DATE</th>                           <td class="pt-val">{{ $request->created_at->format('d F Y') }}</td></tr>
    <tr><th class="pt-lbl">INFO TYPE</th>                              <td class="pt-val">{{ strtoupper(str_replace('_', ' ', $request->type ?? 'DATA & SKILL SCREENING')) }}</td></tr>
    <tr><th class="pt-lbl">AUTHORIZED REQUESTOR</th>                  <td class="pt-val">{{ $customer->contact_name ?? '—' }}</td></tr>
    <tr><th class="pt-lbl">REQUESTOR EMAIL</th>                        <td class="pt-val">{{ $customer->contact_email ?? '—' }}</td></tr>
    <tr><th class="pt-lbl">REQUESTOR CONTACT</th>                     <td class="pt-val">{{ $customer->contact_phone ?? '—' }}</td></tr>
    <tr><th class="pt-lbl">NEW ADD-ON (If Any) DATE</th>              <td class="pt-val">{{ data_get($request->meta, 'addon_date') ?: 'NIL' }}</td></tr>
</table>

<div class="sh-pt">NRH INTERNAL</div>
<table class="pt">
    <tr><th class="pt-lbl" style="width:48%;">NRH RESEARCH OFFICER</th> <td class="pt-val">{{ data_get($request->meta, 'analyst') ?: '—' }}</td></tr>
    <tr><th class="pt-lbl">COMPLIANCE EDITOR</th>                      <td class="pt-val">{{ data_get($request->meta, 'editor') ?: '—' }}</td></tr>
    <tr><th class="pt-lbl">INTERIM REP 1 DATE: (If Any)</th>          <td class="pt-val">{{ $completionBasic ?? '—' }}</td></tr>
    <tr><th class="pt-lbl">INTERIM REP 2 DATE: (If Any)</th>          <td class="pt-val">{{ $completionPrelim ?? '—' }}</td></tr>
    <tr><th class="pt-lbl">FULL REP DATE</th>                          <td class="pt-val">{{ $completionFull ?? '—' }}</td></tr>
    <tr><th class="pt-lbl">REVISED REP DATE</th>                       <td class="pt-val">{{ data_get($request->meta, 'revised_date') ?: '—' }}</td></tr>
</table>

@if(! empty($reportType))
<div style="margin:4px 0 6px; padding:5px 12px; background:#f5ecd1; border-left:3px solid #d4af37; font-size:8.5pt;">
    <strong>{{ strtoupper($reportType) }} REPORT — VERSION {{ $reportVersion }}</strong>
    @if(! empty($reportHash))
    &nbsp;·&nbsp;<span style="font-family:'DejaVu Sans Mono',monospace; font-size:7.5pt; color:#666;">SHA: {{ $reportHash }}</span>
    @endif
</div>
@endif

<div class="pb"></div>

<table class="sh-blk"><tr><td>COMPLIANCE CLAUSE</td></tr></table>
<p class="body-p">
    This report confirms that valid consent has been obtained from the data subject. All personal data has been
    collected, processed, and safeguarded in compliance with the Personal Data Protection Act 2010 (Act 709),
    as amended by the Personal Data Protection (Amendment) Act 2024 and international standards (ISO 27001 / ISO 31000).
    @if($consentedCount > 0) Consent records on file for {{ $consentedCount }} of {{ $totalCandidates }} candidate(s).@endif
</p>

<table class="sh-blk"><tr><td>LEGAL DISCLAIMER</td></tr></table>
<ul class="body-ul">
    <li><strong>Permitted Use:</strong> Legitimate business purposes only.</li>
    <li><strong>Prohibited Use:</strong> Fraud, stalking, identity theft, or illegal activity.</li>
    <li><strong>Consumer-Report Limitation:</strong> NRH is not a consumer-reporting agency.</li>
    <li><strong>Confidentiality:</strong> Information must not be disclosed to unauthorized parties.</li>
    <li><strong>Source of Records:</strong> Records are obtained directly from the keeper of records. If such records are not updated or contain inaccuracies, NRH excludes liability for reliance on them.</li>
</ul>

<table class="sh-blk"><tr><td>ERM COMPLIANCE &amp; AUDIT-READY REPORTING</td></tr></table>
<p class="body-p">
    ERM mapping ensures NRH&rsquo;s professional reporting is standardized (ISO&nbsp;27001 / ISO&nbsp;31000) and audit-ready,
    allowing HR and compliance teams to interpret results with consistency and confidence.
</p>

<table class="sh-blk" style="margin-bottom:0;"><tr><td>DATA REPORT LEGEND</td></tr></table>
<table class="lgt">
    <tr><td class="lg-lbl">CLEAN RESULT</td><td colspan="2">No records or adverse findings identified.</td></tr>
    <tr><td class="lg-lbl">ADVERSE RESULT</td><td colspan="2">Negative findings detected.</td></tr>
    <tr><td class="lg-lbl">RECORD IDENTIFIED</td><td colspan="2">Record found in screening.</td></tr>
    <tr>
        <td class="lg-lbl" rowspan="3">RISK MATRIX INTERPRETATION</td>
        <td class="lg-dot"><span style="display:inline-block; width:12px; height:12px; border-radius:6px; background:#CC0000;"></span></td>
        <td class="lg-lvl">High</td>
    </tr>
    <tr>
        <td class="lg-dot"><span style="display:inline-block; width:12px; height:12px; border-radius:6px; background:#FFC000;"></span></td>
        <td class="lg-lvl">Moderate</td>
    </tr>
    <tr>
        <td class="lg-dot"><span style="display:inline-block; width:12px; height:12px; border-radius:6px; background:#00B050;"></span></td>
        <td class="lg-lvl">Low</td>
    </tr>
</table>

<div class="pb"></div>

{{-- ══════════════════════════════════════════
     CANDIDATE SECTIONS
═══════════════════════════════════════════ --}}
@foreach($candidates as $candidateIndex => $candidate)

{{-- ── Candidate Info ── --}}
@php
    // Locate the candidate's Name & ID screening scope to surface its result here.
    $nameIdScope = $candidate->scopeTypes->first(function ($s) {
        $h = strtolower($s->name.' '.($s->category ?? ''));
        return str_contains($h, 'name & id') || str_contains($h, 'name and id') || str_contains($h, 'name/id')
            || str_contains($h, 'identity') || str_contains($h, 'mykad') || str_contains($h, 'my kad')
            || str_contains($h, 'personal data') || str_contains($h, 'nric')
            || (str_contains($h, 'name') && (str_contains($h, ' id') || str_contains($h, 'i/c') || str_contains($h, ' ic')));
    });
    $nidF     = $nameIdScope ? ($nameIdScope->pivot->findings ?? []) : [];
    $nidSt    = $nameIdScope ? ($nameIdScope->pivot->status ?? 'new') : 'new';
    $nidT     = $getResultType($nidSt, $nidF);
    $nidLv    = $getRiskLevel($nidSt, $nidF);
    $nidStat  = $riskStatusText($nidLv, $nidT, $nidF);
    $nidVer   = $nidF['verification_method']
        ?? "Verification was conducted using the candidate's Name and ID against the official keeper of identity records (National Registration Department of Malaysia – NRD).";
@endphp
<div class="shd">CANDIDATE INFO</div>
<table class="rt">
    <tr>
        <th class="lbl" style="width:33%;">CANDIDATE NAME</th>
        <td class="val bold">{{ strtoupper($candidate->name) }}</td>
    </tr>
    <tr>
        <th class="lbl">{{ $candidate->identityType ? strtoupper($candidate->identityType->name) : 'ID' }} / PASSPORT NO</th>
        <td class="val bold">{{ $candidate->identity_number }}</td>
    </tr>
    @if($candidate->nationality)
    <tr><th class="lbl">NATIONALITY</th><td class="val bold">{{ strtoupper($candidate->nationality) }}</td></tr>
    @endif
    @if($candidate->date_of_birth)
    <tr><th class="lbl">DATE OF BIRTH</th><td class="val bold">{{ strtoupper($candidate->date_of_birth->format('jS F Y')) }}</td></tr>
    @endif
    <tr>
        <th class="lbl" rowspan="2">NAME &amp; ID SCREENING RESULT</th>
        <td class="val {{ $resultCss($nidT) }} bold">{{ $resultLabel($nidT) }}</td>
    </tr>
    <tr>
        <td class="val">{!! $riskBadge($nidLv) !!}&nbsp; {{ $nidStat }}</td>
    </tr>
    <tr>
        <th class="lbl">VERIFICATION METHOD</th>
        <td class="val small">{{ $nidVer }}</td>
    </tr>
</table>

{{-- ── Risk Matrix Summary ── --}}
<div style="font-family:'Oswald',sans-serif; font-weight:bold; font-size:9.5pt; letter-spacing:0.08em; color:#000; margin:12px 0 4px;">RISK MATRIX – COMPLIANCE SCREENING DATA REPORT SUMMARY</div>
<table class="rmt">
    <tr>
        <th style="width:35%;">SCOPE</th>
        <th style="width:18%;">RESULT</th>
        <th style="width:15%;">RISK LEVEL</th>
        <th style="width:32%;">IMPLICATION</th>
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
        <td class="rmt-scope">
            {{ $scope->name }}
        </td>
        <td>{{ ucwords(strtolower($resultLabel($pT))) }}</td>
        <td>{!! $riskBadge($pLv) !!}</td>
        <td class="small">{{ $pIm }}</td>
    </tr>
    @endforeach
</table>

{{-- ── Compliance Risk Heatmap ── --}}
@php
    $heatmapRows = [];
    foreach ($candidate->scopeTypes as $hs) {
        $hSt = $hs->pivot->status ?? 'new';
        $hF  = $hs->pivot->findings ?? [];
        $hLv = $getRiskLevel($hSt, $hF);
        $hDomain = $getScopeDomain($hs->name, $hs->category ?? '');
        if (!isset($heatmapRows[$hDomain])) {
            $heatmapRows[$hDomain] = ['scopes' => [], 'levels' => []];
        }
        $heatmapRows[$hDomain]['scopes'][] = $hs->name;
        $heatmapRows[$hDomain]['levels'][] = $hLv;
    }
    $domainOrder = ['LEGAL RISK','INTERNATIONAL RISK','FINANCIAL RISK','POLITICAL RISK','REPUTATIONAL RISK','REGULATORY RISK','OTHER'];
@endphp
@if(count($heatmapRows))
<div style="font-family:'Oswald',sans-serif; font-weight:bold; font-size:9.5pt; letter-spacing:0.08em; color:#000; margin:12px 0 4px;">COMPLIANCE RISK HEATMAP</div>
<table class="hmt">
    <tr>
        <th style="width:21%;">DOMAIN</th>
        <th style="width:33%;">SCOPE</th>
        <th style="width:15%;">RISK LEVEL</th>
        <th style="width:30%;">VISUAL HEATMAP INDICATOR</th>
    </tr>
    @foreach($domainOrder as $dom)
    @if(isset($heatmapRows[$dom]))
    @php
        $row = $heatmapRows[$dom];
        $aggRisk = $aggregateRisk($row['levels']);
    @endphp
    <tr>
        <td class="hm-domain">{{ $dom }}</td>
        <td class="bold" style="font-size:7.5pt;">{{ implode(', ', $row['scopes']) }}</td>
        <td class="bold">{!! $riskBadge($aggRisk) !!}</td>
        <td class="bold" style="font-size:7.5pt;">
            @if($aggRisk === 'high') Red blocks (critical exposure)
            @elseif($aggRisk === 'medium') Yellow block (enhanced due diligence required)
            @elseif($aggRisk === 'low') Green blocks (clean)
            @else Screening not conducted for this domain.
            @endif
        </td>
    </tr>
    @endif
    @endforeach
</table>
@endif

<div class="pb"></div>

{{-- ── DATA REPORT blocks ── --}}
<div style="font-family:'Oswald',sans-serif; font-weight:bold; font-size:9.5pt; letter-spacing:0.08em; color:#000; text-align:center; margin:12px 0 4px;">COMPLIANCE SCREENING DATA REPORT</div>

<div style="font-family:'Oswald',sans-serif; font-weight:bold; font-size:8.5pt; letter-spacing:0.05em; color:#000; margin:6px 0 2px;">DATA REPORT SCREENING:</div>
<div style="margin:0 0 8px; font-size:8.5pt; line-height:1.6; color:#333; text-align:justify;">
    The compliance screening framework evaluates exposures across six key domains: legal, international, financial,
    political, reputational, and regulatory risk. It encompasses checks such as crime integrity, corruption records,
    global sanctions, AML/CTF, PEPs, civil and financial defaults, directorship and shareholding risks, and
    intelligence from social, deep, and dark web sources. The results in this report reflect only the specific
    domains selected by the client for screening.
</div>

@php
    /* Structured scopes (academic / employment / referee) have dedicated report
       sections — the DATA REPORT loop covers compliance scopes only (as in Word). */
    $drKindOf = function ($s): string {
        $probe = function (string $hay): ?string {
            $hay = strtolower($hay);
            if (str_contains($hay, 'referee') || str_contains($hay, 'reference')) return 'referee';
            if (str_contains($hay, 'employment') || str_contains($hay, 'work history')) return 'employment';
            foreach (['academic','qualification','credential','education','degree','certificate','certification'] as $kw) {
                if (str_contains($hay, $kw) && !str_contains($hay, 'loan')) return 'academic';
            }
            return null;
        };
        return $probe($s->name) ?? $probe($s->category ?? '') ?? 'generic';
    };
@endphp
@foreach($candidate->scopeTypes as $scope)
@continue($nameIdScope && $scope->id === $nameIdScope->id)
@continue($drKindOf($scope) !== 'generic')
@php
    $pSt      = $scope->pivot->status ?? 'new';
    $findings = $scope->pivot->findings ?? [];
    $rType    = $getResultType($pSt, $findings);
    $rLevel   = $getRiskLevel($pSt, $findings);
    $rLabel   = $resultLabel($rType);
    $rCss     = $resultCss($rType);
    $rStat    = $riskStatusText($rLevel, $rType, $findings);
    $comment  = $findings['comment'] ?? null;
    $records  = $findings['records'] ?? [];
    $legacyRec= $findings['record']  ?? [];
    $verMethod= $findings['verification_method'] ?? $scope->verification_method ?? ($scope->description ?? null);
    $scopeDesc= $findings['scope_description'] ?? ($scope->description ?? null);
    $lvDot = fn(string $lv) => $dot(match($lv) {
        'high' => '#CC0000', 'medium' => '#FFC000', 'low' => '#00B050', default => '#BFBFBF',
    });
@endphp
<div class="mb8">
    <div class="shd">DATA REPORT</div>
    <table class="drt pi" style="margin-bottom:0;">
        <tr>
            <th class="dr-lbl">SCOPE</th>
            <td class="dr-scope" colspan="2">
                <span class="bold">{{ strtoupper($scope->name) }}</span>
                @if($scopeDesc && $scopeDesc !== $scope->name)
                <br><span class="small" style="display:block; margin-top:2px;">{{ $scopeDesc }}</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- Structured record entries (Word: each record opens with a RESULT row).
         Each record is its own sub-table so the RESULT rowspan never splits across pages. --}}
    @if(!empty($records))
        @foreach($records as $ri => $rec)
        @php
            $recRows = 1
                + (!empty($rec['title']) ? 1 : 0)
                + ((!empty($rec['section']) || !empty($rec['description']) || !empty($rec['penalty'])) ? 1 : 0)
                + count($rec['fields'] ?? [])
                + (!empty($rec['verdict']) ? 1 : 0)
                + (!empty($rec['risk_text']) ? 1 : 0);
        @endphp
        <table class="drt pi" style="margin-bottom:0; margin-top:-1px;">
            <tr>
                <th class="dr-lbl dr-lbl-c" rowspan="{{ $recRows }}">RESULT</th>
                <td class="dr-res" colspan="2">RECORD {{ count($records) > 1 ? $numWord($ri + 1).' ' : '' }}IDENTIFIED</td>
            </tr>
            @if(!empty($rec['title']))
            <tr>
                <td colspan="2">
                    <span class="t-red">{{ strtoupper($rec['title']) }}</span>
                    @if(!empty($rec['act']))<br><span class="small muted">{{ strtoupper($rec['act']) }}</span>@endif
                </td>
            </tr>
            @endif
            @if(!empty($rec['section']) || !empty($rec['description']) || !empty($rec['penalty']))
            <tr>
                @if(!empty($rec['section']))
                <td style="width:27%;"><span class="t-blue">{{ $rec['section'] }}</span></td>
                <td>
                    <span class="t-blue">Offence description</span>
                    @if(!empty($rec['description']))<br>{{ $rec['description'] }}@endif
                    @if(!empty($rec['penalty']))<br>Penalty: {{ $rec['penalty'] }}@endif
                </td>
                @else
                <td colspan="2">
                    @if(!empty($rec['description'])){{ $rec['description'] }}@endif
                    @if(!empty($rec['penalty']))<br>Penalty: {{ $rec['penalty'] }}@endif
                </td>
                @endif
            </tr>
            @endif
            @if(!empty($rec['fields']))
                @foreach($rec['fields'] as $fk => $fv)
                <tr>
                    <td style="width:27%;" class="t-red">{{ $fk }}</td>
                    <td>{{ $fv }}</td>
                </tr>
                @endforeach
            @endif
            @if(!empty($rec['verdict']))
            <tr>
                <td style="width:27%;" class="t-blue">Verdict</td>
                <td class="bold">{{ strtoupper($rec['verdict']) }}</td>
            </tr>
            @endif
            @if(!empty($rec['risk_text']))
            @php $recLv = $rec['risk_level'] ?? $rLevel; @endphp
            <tr>
                <td colspan="2">{!! $lvDot($recLv) !!} <span class="{{ $recLv === 'high' ? 't-risk' : 'bold' }}">Risk Level: {{ $rec['risk_text'] }}</span></td>
            </tr>
            @endif
        </table>
        @endforeach

    {{-- Legacy key-value record (backwards compatible) --}}
    @elseif(!empty($legacyRec))
        <table class="drt pi" style="margin-bottom:0; margin-top:-1px;">
            <tr>
                <th class="dr-lbl dr-lbl-c" rowspan="{{ 1 + count($legacyRec) }}">RESULT</th>
                <td class="dr-res" colspan="2">RECORD IDENTIFIED</td>
            </tr>
            @foreach($legacyRec as $rk => $rv)
            <tr>
                <td style="width:27%;" class="t-red">{{ $rk }}</td>
                <td>{{ $rv }}</td>
            </tr>
            @endforeach
        </table>

    {{-- Clean / pending result --}}
    @else
        <table class="drt pi" style="margin-bottom:0; margin-top:-1px;">
            <tr>
                <th class="dr-lbl dr-lbl-c" rowspan="2">RESULT</th>
                <td class="dr-res" colspan="2">{{ $rLabel }}</td>
            </tr>
            <tr>
                <td colspan="2" class="bold">{!! $lvDot($rLevel) !!} Status: {{ $rStat }}</td>
            </tr>
        </table>
    @endif

    <table class="drt pi" style="margin-bottom:0; margin-top:-1px;">
        @if($comment)
        <tr>
            <th class="dr-lbl">NOTES</th>
            <td colspan="2">{!! nl2br(e($comment)) !!}</td>
        </tr>
        @endif
        <tr>
            <th class="dr-lbl">VERIFICATION METHOD</th>
            <td colspan="2" class="small">
                @if($verMethod){{ $verMethod }}@else
                Verification was conducted using the candidate's Name and ID against authoritative databases and declared information.
                @endif
                <br><span class="muted ital">Compliance aligned with PDPA 2010 (Act 709), as amended 2024, and ISO 27001 / ISO 31000 standards.</span>
            </td>
        </tr>
    </table>
</div>
@endforeach

@if(!$loop->last)<div class="pb"></div>@endif
@endforeach

<div class="pb"></div>

{{-- ══════════════════════════════════════════
     PROFESSIONAL COMPETENCY VALIDATION
═══════════════════════════════════════════ --}}
<div class="sh-erm">PROFESSIONAL COMPETENCY VALIDATION REPORT</div>

<table class="cmt" style="margin-top:6px;">
    <tr><td class="cm-blk" colspan="5">ACADEMIC &amp; PROFESSIONAL CERTIFICATION<br>REPORT TERMINOLOGY</td></tr>
    <tr><td class="cm-crm" colspan="5">RISK MATRIX INTERPRETATION (BASED ON ERM COLOR)</td></tr>
    <tr>
        <td class="cm-gold" style="width:20%;">SCORES PER ASPECT</td>
        <td class="cm-gold" style="width:14%; text-align:center;">COLOR</td>
        <td class="cm-gold" style="width:18%;">ERM RISK</td>
        <td class="cm-gold" style="width:23%;">EXPLANATION</td>
        <td class="cm-gold" style="width:25%;">INTERPRETATION</td>
    </tr>
    <tr>
        <td class="t-blue">MATCH</td>
        <td style="text-align:center;">{!! $dot('#00B050') !!}</td>
        <td class="bold">LOW</td>
        <td>Provided info consistent with verified records.</td>
        <td class="bold">Verified. Safe to proceed.</td>
    </tr>
    <tr>
        <td class="t-blue">PARTIAL MATCH</td>
        <td style="text-align:center;">{!! $dot('#00B0F0') !!}</td>
        <td class="bold">MODERATE</td>
        <td>Record mostly consistent but not exact.</td>
        <td class="bold">Minor variation. Acceptable with caution.</td>
    </tr>
    <tr>
        <td class="t-blue">NO RECORD</td>
        <td style="text-align:center;">{!! $dot('#FFC000') !!}</td>
        <td class="bold">HIGH</td>
        <td>No official record exists.</td>
        <td class="bold">Missing record. Needs additional document.</td>
    </tr>
    <tr>
        <td class="t-blue">DISCREPANCY</td>
        <td style="text-align:center;">{!! $dot('#CC0000') !!}</td>
        <td class="bold">CRITICAL</td>
        <td>Information differs significantly or is false.</td>
        <td class="bold">Potential fraud. Serious concern for credibility.</td>
    </tr>
</table>

@foreach($candidates as $candidate)
@php
    $academicScopes    = $candidate->scopeTypes->filter(fn($s) =>
        collect(['academic','qualification','credential','education','degree','certificate','certification'])
            ->contains(fn($kw) => str_contains(strtolower($s->name . ' ' . ($s->category ?? '')), $kw))
        && !collect(['referee','reference','employment','work history','loan'])
            ->contains(fn($kw) => str_contains(strtolower($s->name . ' ' . ($s->category ?? '')), $kw))
    );
    $employmentScopes  = $candidate->scopeTypes->filter(fn($s) =>
        collect(['employment','work history'])
            ->contains(fn($kw) => str_contains(strtolower($s->name . ' ' . ($s->category ?? '')), $kw))
        && !collect(['referee','reference'])
            ->contains(fn($kw) => str_contains(strtolower($s->name), $kw))
    );
    $refereeScopes     = $candidate->scopeTypes->filter(fn($s) =>
        collect(['referee','reference'])
            ->contains(fn($kw) => str_contains(strtolower($s->name . ' ' . ($s->category ?? '')), $kw))
    );
    $hasCompetency = $academicScopes->count() || $employmentScopes->count();
@endphp

@if($hasCompetency || $academicScopes->count() === 0)
<div class="shs" style="margin-top:10px;">{{ strtoupper($candidate->name) }}</div>
@endif

{{-- ── Academic Credential Validation ── --}}
@if($academicScopes->count())
    @foreach($academicScopes as $aScope)
    @php
        $aF = $aScope->pivot->findings ?? [];
        $aSt = $aScope->pivot->status ?? 'new';
        $aT  = $getResultType($aSt, $aF);
        $aComment = $aF['comment'] ?? null;
        // New: array of credentials. Legacy: single credential from top-level fields.
        $aCredentials = $aF['credentials'] ?? null;
        if ($aCredentials === null && (!empty($aF['validation']) || !empty($aF['institution']) || !empty($aF['recognition']))) {
            $aCredentials = [[
                'institution'    => $aF['institution'] ?? null,
                'validation'     => $aF['validation'] ?? null,
                'recognition'    => $aF['recognition'] ?? null,
                'overall_risk'   => $aF['overall_risk'] ?? null,
                'overall_action' => $aF['overall_action'] ?? null,
            ]];
        }
    @endphp
    <div class="sh-gld" style="margin-top:8px;">ACADEMIC CREDENTIAL VALIDATION</div>

    @if(!empty($aCredentials))
        @foreach($aCredentials as $aCredIdx => $cred)
        @php
            $aValidation  = $cred['validation']  ?? null;
            $aInstitution = $cred['institution'] ?? $aScope->name;
            $aRecognition = $cred['recognition'] ?? null;
            $aOverallRisk = $cred['overall_risk'] ?? null;
            $aOverallAct  = $cred['overall_action'] ?? null;
            $aVerifier    = $cred['verifier'] ?? null;
        @endphp

        {{-- Credential info: VALIDATION | CANDIDATE PROVIDED | NRH VERIFIED INFORMATION --}}
        @if($aValidation)
        <table class="cmt" style="margin-top:{{ $aCredIdx === 0 ? '4px' : '10px' }};">
            <tr><td class="cm-crm" colspan="3">{{ strtoupper($aInstitution) }}</td></tr>
            <tr>
                <th style="width:33%;">VALIDATION</th>
                <th style="width:30%;">CANDIDATE PROVIDED</th>
                <th style="width:37%;">NRH VERIFIED INFORMATION</th>
            </tr>
            @foreach($aValidation as $av)
            <tr>
                <td class="bold">{{ strtoupper($av['aspect'] ?? '') }}</td>
                <td class="bold">{{ strtoupper($av['provided'] ?? '-') }}</td>
                <td class="bold">{{ strtoupper($av['verified'] ?? 'NO RECORD') }}</td>
            </tr>
            @endforeach
            @if($aVerifier)
            <tr>
                <td class="bold">VERIFIER</td>
                <td class="bold" colspan="2">{{ strtoupper($aVerifier) }}</td>
            </tr>
            @endif
        </table>

        {{-- Validation matrix --}}
        <table class="cmt">
            <tr><td class="cm-gold" colspan="5">ACADEMIC CREDENTIAL VALIDATION MATRIX</td></tr>
            <tr>
                <th style="width:26%;">ASPECT</th>
                <th style="width:20%;">VERIFIED INFORMATION</th>
                <th style="width:12%;">COLOR &amp; TERM</th>
                <th style="width:13%;">ERM RISK</th>
                <th style="width:29%;">INTERPRETATION</th>
            </tr>
            @foreach($aValidation as $av)
            @php
                $avMatch = strtolower($av['match'] ?? 'match');
                $avRisk  = strtolower($av['risk'] ?? 'low');
            @endphp
            <tr>
                <td class="bold">{{ strtoupper($av['aspect'] ?? '') }}</td>
                <td>{{ $av['verified'] ?? '—' }}</td>
                <td>{!! $matchCell($avMatch) !!}</td>
                <td class="bold">{{ ucfirst($avRisk) }}</td>
                <td class="bold small">{{ $av['interpretation'] ?? '' }}</td>
            </tr>
            @endforeach
        </table>
        @endif

        @if($aRecognition)
        @php $selScenario = strtoupper(trim($aRecognition['scenario'] ?? '')); $selMarked = false; @endphp
        <table class="rct">
            <tr><td class="cm-gold" colspan="5">RISK MATRIX FOR RECOGNITION + ACCREDITATION</td></tr>
            <tr>
                <th style="width:25%;">SCENARIO</th>
                <th style="width:23%;">INSTITUTION RECOGNITION</th>
                <th style="width:22%;">PROGRAM ACCREDITATION</th>
                <th style="width:17%;">RISK LEVEL</th>
                <th style="width:13%;">STATUS</th>
            </tr>
            @foreach($recScenarios as $rs)
            @php
                $isSel = !$selMarked && $selScenario === $rs[0];
                if ($isSel) $selMarked = true;
            @endphp
            <tr>
                <td class="bold">{{ $rs[0] }}</td>
                <td>{{ $rs[1] }}</td>
                <td>{{ $rs[2] }}</td>
                <td>{!! $dot($rs[3]) !!} <span class="bold">{{ $rs[4] }}</span></td>
                <td class="bold" style="text-align:center;">{{ $isSel ? 'X' : '' }}</td>
            </tr>
            @endforeach
        </table>
        @endif

        @if($aOverallRisk)
        <table class="cmt">
            <tr>
                <td class="cm-gold" style="width:25%;">OVERALL ERM RISK</td>
                <td class="t-blue" style="width:24%;">{{ strtoupper($aOverallRisk) }} RISK {!! $riskDot($aOverallRisk) !!}</td>
                <td class="bold" style="width:51%;">{{ $aOverallAct }}</td>
            </tr>
        </table>
        @endif

        @if(!$aValidation && !$aRecognition && !$aOverallRisk)
        <table class="rt" style="margin-top:4px;">
            <tr><th class="lbl" style="width:22%;">NOTE</th><td class="val muted ital small">Verification details will be reported upon completion with the institution.</td></tr>
        </table>
        @endif
        @endforeach

    @elseif($aComment)
    <table class="rt" style="margin-top:4px;">
        <tr><th class="lbl" style="width:22%;">DETAILS</th><td class="val">{!! nl2br(e($aComment)) !!}</td></tr>
        <tr><th class="lbl">RESULT</th><td class="val {{ $resultCss($aT) }}">{{ $resultLabel($aT) }}</td></tr>
    </table>
    @else
    <table class="rt" style="margin-top:4px;">
        <tr><th class="lbl" style="width:22%;">RESULT</th><td class="val {{ $resultCss($aT) }}">{{ $resultLabel($aT) }}</td></tr>
        <tr>
            <th class="lbl">NOTE</th>
            <td class="val muted ital small">Academic credential verification details will be reported upon completion of verification with the relevant institution.</td>
        </tr>
    </table>
    @endif
    @endforeach

@elseif(!$hasCompetency)
<div class="sh" style="margin-top:8px;">ACADEMIC CREDENTIAL VERIFICATION</div>
<table class="rt" style="margin-top:4px;">
    <tr>
        <th class="lbl" style="width:38%;">PROVIDED INFORMATION</th>
        <td class="val muted ital small">Academic and professional credential verification details will be reported upon completion of verification with the relevant institutions.</td>
    </tr>
</table>
@endif

{{-- ── Employment Validation ── --}}
@foreach($employmentScopes as $eScope)
@php
    $eF = $eScope->pivot->findings ?? [];
    $eSt = $eScope->pivot->status ?? 'new';
    $eT  = $getResultType($eSt, $eF);
    $eValidation  = $eF['validation']   ?? null;
    $eEmployer    = $eF['employer']     ?? $eScope->name;
    $eVerifier    = $eF['verifier']     ?? null;
    $eOverallRisk = $eF['overall_risk'] ?? null;
    $eOverallAct  = $eF['overall_action'] ?? null;
    $eComment     = $eF['comment'] ?? null;
@endphp
@if($loop->first)
<div class="sh-gld" style="margin-top:8px;">EMPLOYMENT VALIDATION</div>

{{-- Employment terminology (Word: black header + cream sub-header + gold columns) --}}
<table class="cmt" style="margin-top:4px;">
    <tr><td class="cm-blk" colspan="5">EMPLOYMENT VALIDATION REPORT TERMINOLOGY</td></tr>
    <tr><td class="cm-crm" colspan="5">RISK MATRIX INTERPRETATION (BASED ON ERM COLOR)</td></tr>
    <tr>
        <td class="cm-gold" style="width:20%;">SCORES PER ASPECT</td>
        <td class="cm-gold" style="width:14%; text-align:center;">COLOR</td>
        <td class="cm-gold" style="width:17%;">ERM RISK</td>
        <td class="cm-gold" style="width:22%;">EXPLANATION</td>
        <td class="cm-gold" style="width:27%;">INTERPRETATION</td>
    </tr>
    <tr>
        <td class="t-blue">MATCH</td>
        <td style="text-align:center;">{!! $dot('#00B050') !!}</td>
        <td class="bold">LOW</td>
        <td>Provided info consistent with verified records.</td>
        <td class="bold">Verified. Safe to proceed.</td>
    </tr>
    <tr>
        <td class="t-blue">PARTIAL MATCH</td>
        <td style="text-align:center;">{!! $dot('#00B0F0') !!}</td>
        <td class="bold">MODERATE</td>
        <td>Record mostly consistent but not exact.</td>
        <td class="bold">Minor variation. Acceptable with caution.</td>
    </tr>
    <tr>
        <td class="t-blue">NO RECORD</td>
        <td style="text-align:center;">{!! $dot('#FFC000') !!}</td>
        <td class="bold">HIGH</td>
        <td>No official record exists.</td>
        <td class="bold">Missing record. Needs additional document.</td>
    </tr>
    <tr>
        <td class="t-blue">DISCREPANCY</td>
        <td style="text-align:center;">{!! $dot('#CC0000') !!}</td>
        <td class="bold">CRITICAL</td>
        <td>Information differs significantly or is false.</td>
        <td class="bold">False or conflicting info, potential fraud. Serious concern for credibility.</td>
    </tr>
</table>
@endif

@if($eValidation)
{{-- Employer info: VALIDATION | CANDIDATE PROVIDED | NRH VERIFIED INFORMATION --}}
<table class="cmt" style="margin-top:8px;">
    <tr><td class="cm-gold" colspan="3">EMPLOYMENT VALIDATION</td></tr>
    <tr><td class="cm-crm" colspan="3">{{ strtoupper($eEmployer) }}</td></tr>
    <tr>
        <th style="width:33%;">VALIDATION</th>
        <th style="width:30%;">CANDIDATE PROVIDED</th>
        <th style="width:37%;">NRH VERIFIED INFORMATION</th>
    </tr>
    @foreach($eValidation as $ev)
    <tr>
        <td class="bold">{{ strtoupper($ev['aspect'] ?? '') }}</td>
        <td class="bold">{{ strtoupper($ev['provided'] ?? '-') }}</td>
        <td class="bold">{{ strtoupper($ev['verified'] ?? 'NO RECORD') }}</td>
    </tr>
    @endforeach
    @if($eVerifier)
    <tr>
        <td class="bold">VERIFIER</td>
        <td class="bold" colspan="2">{{ strtoupper($eVerifier) }}</td>
    </tr>
    @endif
</table>

{{-- ERM risk matrix --}}
<div style="font-family:'Oswald',sans-serif; font-weight:bold; font-size:8.5pt; letter-spacing:0.05em; color:#000; margin:6px 0 2px;">EMPLOYMENT ERM RISK MATRIX</div>
<table class="cmt" style="margin-top:0;">
    <tr>
        <th style="width:23%;">ASPECT</th>
        <th style="width:19%;">VERIFIED INFORMATION</th>
        <th style="width:10%; text-align:center;">COLOR</th>
        <th style="width:10%;">TERM</th>
        <th style="width:12%;">RISK</th>
        <th style="width:26%;">INTERPRETATION</th>
    </tr>
    @foreach($eValidation as $ev)
    @php
        $evMatch = strtolower($ev['match'] ?? 'match');
        $evRisk  = strtolower($ev['risk'] ?? 'low');
        $evDotC  = match($evMatch) { 'match'=>'#00B050', 'partial'=>'#00B0F0', 'no_record'=>'#FFC000', 'discrepancy'=>'#CC0000', default=>'#BFBFBF' };
        $evTerm  = match($evMatch) { 'match'=>'Match', 'partial'=>'Partial Match', 'no_record'=>'No Record', 'discrepancy'=>'Discrepancy', default=>strtoupper($evMatch) };
    @endphp
    <tr>
        <td class="bold">{{ strtoupper($ev['aspect'] ?? '') }}</td>
        <td>{{ $ev['verified'] ?? '–' }}</td>
        <td style="text-align:center;">{!! $dot($evDotC) !!}</td>
        <td>{{ $evTerm }}</td>
        <td class="bold">{{ ucfirst($evRisk) }}</td>
        <td class="bold small">{{ $ev['interpretation'] ?? '' }}</td>
    </tr>
    @endforeach
</table>

@if($eOverallRisk)
<table class="cmt">
    <tr>
        <td class="cm-gold" style="width:23%;">OVERALL ERM RISK</td>
        <td class="t-blue" style="width:20%;">{{ strtoupper($eOverallRisk) }} RISK {!! $riskDot($eOverallRisk) !!}</td>
        <td class="bold" style="width:57%;">{{ $eOverallAct }}</td>
    </tr>
</table>
@endif

@elseif($eComment)
<table class="rt" style="margin-top:4px;">
    <tr><th class="lbl" style="width:22%;">RESULT</th><td class="val {{ $resultCss($eT) }}">{{ $resultLabel($eT) }}</td></tr>
    <tr><th class="lbl">DETAILS</th><td class="val">{!! nl2br(e($eComment)) !!}</td></tr>
</table>
@else
<table class="rt" style="margin-top:4px;">
    <tr><th class="lbl" style="width:22%;">RESULT</th><td class="val {{ $resultCss($eT) }}">{{ $resultLabel($eT) }}</td></tr>
    <tr><th class="lbl">NOTE</th><td class="val muted ital small">Employment verification pending confirmation from the referenced organisation.</td></tr>
</table>
@endif
@endforeach

@endforeach

<div class="pb"></div>

{{-- ══════════════════════════════════════════
     REFEREE INTERVIEW REPORT
═══════════════════════════════════════════ --}}
@php
    $isRefereeScope = fn($s) =>
        collect(['referee','reference'])->contains(fn($kw) => str_contains(strtolower($s->name . ' ' . ($s->category ?? '')), $kw))
        && !collect(['employment','work history'])->contains(fn($kw) => str_contains(strtolower($s->name), $kw));
    $hasAnyReferee = $candidates->contains(fn($c) => $c->scopeTypes->filter($isRefereeScope)->count() > 0);
@endphp
@if($hasAnyReferee)
<div class="sh-gld">REFEREE INTERVIEW REPORT</div>

<table class="sh-blk" style="margin-top:6px;"><tr><td>REFEREE INTERVIEW REPORT TERMINOLOGY</td></tr></table>
<p class="body-p">
    A referee interview is a risk management tool within the ERM framework. It ensures that hiring decisions
    are evidence-based, defensible, and aligned with organizational standards.
</p>

{{-- Credibility hierarchy --}}
<table class="ref-t" style="margin-top:4px; margin-bottom:8px;">
    <tr><td class="cm-crm" colspan="4">REFEREE CREDIBILITY HIERARCHY RATING (ERM MATRIX)</td></tr>
    <tr>
        <th style="width:26%;">REFEREE TYPE</th>
        <th style="width:25%;">AUTHORITY LEVEL</th>
        <th style="width:19%;">CREDIBILITY</th>
        <th style="width:30%;">RISK INTERPRETATION</th>
    </tr>
    <tr>
        <td class="bold">DIRECT SUPERVISOR</td>
        <td class="bold">Highest – direct oversight of candidate's work</td>
        <td class="bold">{!! $stars(5) !!} (5/5)</td>
        <td class="bold small">Strongest validation</td>
    </tr>
    <tr>
        <td class="bold">LECTURER / FACULTY PROFESSOR</td>
        <td class="bold">High – academic oversight, authority in education</td>
        <td class="bold">{!! $stars(4) !!} (4/5)</td>
        <td class="bold small">Reliable for academic performance, discipline, and intellectual potential.</td>
    </tr>
    <tr>
        <td class="bold">SENIOR COLLEAGUE</td>
        <td class="bold">Moderate – peer-level observation</td>
        <td class="bold">{!! $stars(3) !!} (3/5)</td>
        <td class="bold small">Useful for teamwork, collaboration, and soft skills.</td>
    </tr>
    <tr>
        <td class="bold">COLLEAGUE</td>
        <td class="bold">Lower – peer without authority</td>
        <td class="bold">{!! $stars(2) !!} (2/5)</td>
        <td class="bold small">Provides insights into daily behavior.</td>
    </tr>
    <tr>
        <td class="bold">ACQUAINTANCE</td>
        <td class="bold">Very low – casual contact</td>
        <td class="bold">{!! $stars(1) !!} (1/5)</td>
        <td class="bold small">Minimal credibility</td>
    </tr>
    <tr>
        <td class="bold">FAMILY MEMBER</td>
        <td class="bold">Lowest – personal relationship only</td>
        <td class="bold">{!! $stars(1) !!} (1/5)</td>
        <td class="bold small">Highly biased.</td>
    </tr>
</table>

{{-- Question & reply reliability --}}
<table class="ref-t" style="margin-top:4px; margin-bottom:10px;">
    <tr><td class="cm-crm" colspan="4">REFEREE QUESTION AND REPLY RELIABILITY RATING (ERM MATRIX)</td></tr>
    <tr>
        <th style="width:19%;">CREDIBILITY</th>
        <th style="width:20%;">RISK</th>
        <th style="width:24%;">INTERPRETATION</th>
        <th style="width:37%;">MEANING IN REVIEW</th>
    </tr>
    <tr>
        <td class="bold">{!! $stars(5) !!} (5/5)</td>
        <td class="bold">Fully reliable.<br>Low Risk</td>
        <td class="bold">OUTSTANDING</td>
        <td class="bold small">Candidate excelled in all areas, showing exceptional reliability, integrity, and potential. Very strong endorsement.</td>
    </tr>
    <tr>
        <td class="bold">{!! $stars(4) !!} (4/5)</td>
        <td class="bold">Strong reliability. Moderate Risk</td>
        <td class="bold">STRONG PERFORMANCE</td>
        <td class="bold small">Candidate consistently met or exceeded expectations. Demonstrated solid skills and professionalism. Positive credibility.</td>
    </tr>
    <tr>
        <td class="bold">{!! $stars(3) !!} (3/5)</td>
        <td class="bold">Strong reliability. Moderate Risk</td>
        <td class="bold">ACCEPTABLE / COMPETENT</td>
        <td class="bold small">Candidate generally met expectations with some inconsistencies. Reliable but not outstanding. Neutral credibility.</td>
    </tr>
    <tr>
        <td class="bold">{!! $stars(2) !!} (2/5)</td>
        <td class="bold">Weak reliability.<br>High Risk</td>
        <td class="bold">BELOW AVERAGE</td>
        <td class="bold small">Candidate occasionally met expectations but showed gaps. Needs close supervision or improvement. Moderate risk.</td>
    </tr>
    <tr>
        <td class="bold">{!! $stars(1) !!} (1/5)</td>
        <td class="bold">Very low reliability. Critical Risk</td>
        <td class="bold">VERY WEAK PERFORMANCE</td>
        <td class="bold small">Candidate consistently failed to meet expectations; frequent issues in reliability, conduct, or skill. High risk signal.</td>
    </tr>
</table>

{{-- Per candidate per referee scope --}}
@foreach($candidates as $candidate)
@php
    $refScopes = $candidate->scopeTypes->filter($isRefereeScope);
@endphp
@foreach($refScopes as $refScope)
@php
    $rF  = $refScope->pivot->findings ?? [];
    $rSt = $refScope->pivot->status ?? 'new';
    // New: array of referees. Legacy: single referee from top-level fields.
    $referees = $rF['referees'] ?? null;
    if ($referees === null && (!empty($rF['referee_name']) || !empty($rF['questions']) || !empty($rF['relationship']))) {
        $referees = [$rF];
    }
@endphp

<div class="shs" style="margin-top:8px;">{{ strtoupper($candidate->name) }} — REFEREE INTERVIEW REPORT</div>

@if(!empty($referees))
    @foreach($referees as $refIdx => $ref)
    {{-- Credibility Validation --}}
    <table class="ref-t" style="margin-top:8px;">
        <tr><td class="cm-gold" colspan="3">REFEREE CREDIBILITY VALIDATION @if(count($referees) > 1)— REFEREE {{ $refIdx + 1 }}@endif</td></tr>
        <tr>
            <th style="width:33%;">VALIDATION</th>
            <th style="width:30%;">CANDIDATE PROVIDED</th>
            <th style="width:37%;">NRH VERIFIED INFORMATION</th>
        </tr>
        <tr>
            <td class="bold">AFFILIATED ORGANIZATION</td>
            <td></td>
            <td class="bold">{{ strtoupper($ref['affiliated_org'] ?? '—') }}</td>
        </tr>
        <tr>
            <td class="bold">REFEREE NAME</td>
            <td></td>
            <td class="bold">{{ strtoupper($ref['referee_name'] ?? '—') }}</td>
        </tr>
        <tr>
            <td class="bold">DESIGNATION</td>
            <td></td>
            <td class="bold">{{ strtoupper($ref['designation'] ?? '—') }}</td>
        </tr>
        <tr>
            <td class="bold">RELATIONSHIP</td>
            <td class="bold">-</td>
            <td class="bold">{{ strtoupper($ref['relationship'] ?? '—') }}</td>
        </tr>
        <tr>
            <td class="bold">CONTACT ESTABLISHED</td>
            <td></td>
            <td class="bold">
                @if(isset($ref['contact_established']))
                    {{ strtolower($ref['contact_established']) === 'successful' || $ref['contact_established'] === true ? 'SUCCESSFUL CONTACT' : 'UNSUCCESSFUL CONTACT' }}
                @else —
                @endif
            </td>
        </tr>
        <tr>
            <td class="bold">CONSENT TO REVIEW</td>
            <td></td>
            <td class="bold">
                @if(isset($ref['consent']))
                    {{ strtolower($ref['consent']) === 'consented' ? 'REFEREE CONSENTED FOR REVIEW' : 'REFEREE REFUSED REVIEW' }}
                @else —
                @endif
            </td>
        </tr>
        <tr>
            <td class="bold">INDEPENDENT / BIAS REVIEW</td>
            <td></td>
            <td class="bold">{{ !empty($ref['independent']) ? 'INDEPENDENT REVIEW' : 'POTENTIAL BIAS – FURTHER EVALUATION RECOMMENDED' }}</td>
        </tr>
        @if(isset($ref['credibility_weight']))
        <tr>
            <td class="bold" colspan="3">REFEREE CREDIBILITY WEIGHT: {!! $stars((int)$ref['credibility_weight']) !!} ({{ $ref['credibility_weight'] }}/5)</td>
        </tr>
        @endif
    </table>

    {{-- Q&A (Word: category | RATE | stars header rows, reply spans the table) --}}
    @if(!empty($ref['questions']))
    <table class="ref-t" style="margin-top:6px;">
        <tr><td class="cm-gold" colspan="3">REFEREE INTERVIEW QUESTION AND REPLY</td></tr>
        @foreach($ref['questions'] as $qa)
        <tr>
            <td class="ref-lbl" style="width:56%;">{{ strtoupper($qa['category'] ?? 'QUESTION') }}</td>
            <td class="ref-lbl" style="width:11%;">RATE</td>
            <td class="ref-lbl" style="width:33%;">@if(isset($qa['rating'])){!! $stars((int)$qa['rating']) !!} ({{ $qa['rating'] }}/5)@endif</td>
        </tr>
        <tr>
            <td colspan="3">{{ $qa['reply'] ?? '—' }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    {{-- Overall ERM Risk Analysis --}}
    @if(!empty($ref['overall_strong']) || !empty($ref['overall_moderate']) || !empty($ref['overall_weak']))
    <table class="ref-t" style="margin-top:6px;">
        <tr><td class="cm-gold" colspan="2">OVERALL ERM RISK ANALYSIS</td></tr>
        <tr>
            <td class="bold" style="width:39%;">STRONG AREAS (LOW RISK)</td>
            <td class="bold" style="width:61%;">{{ implode(', ', $ref['overall_strong'] ?? []) }}</td>
        </tr>
        <tr>
            <td class="bold">MODERATE AREAS (MEDIUM RISK)</td>
            <td class="bold">{{ implode(', ', $ref['overall_moderate'] ?? []) }}</td>
        </tr>
        <tr>
            <td class="bold">WEAK AREAS (HIGH RISK)</td>
            <td class="bold">{{ implode(', ', $ref['overall_weak'] ?? []) }}</td>
        </tr>
    </table>
    @endif
    @endforeach
@else
<table class="rt" style="margin-top:4px;">
    <tr><th class="lbl" style="width:22%;">NOTE</th><td class="val muted ital small">Referee interview details will be reported upon completion.</td></tr>
</table>
@endif

@endforeach
@endforeach

<div class="pb"></div>
@endif

{{-- ══════════════════════════════════════════
     END OF REPORT
═══════════════════════════════════════════ --}}
<div class="eor">— END OF REPORT —</div>

<div class="pb"></div>

{{-- ══════════════════════════════════════════
     MASTER INDEX OF COMPETENCY & RISK FRAMEWORKS
═══════════════════════════════════════════ --}}
<div class="sh">MASTER INDEX OF COMPETENCY &amp; RISK FRAMEWORKS</div>

<div class="sh-crm" style="margin-top:8px;">EDUCATION LEVEL HIERARCHY</div>
<table class="mi" style="margin-top:4px;">
    <tr>
        <th style="width:32%;">LEVEL</th>
        <th>EXAMPLES</th>
    </tr>
    <tr><td class="mi-lbl">CERTIFICATE</td><td>Short vocational/professional courses (SPM / STPM / IGCSE / O-Level)</td></tr>
    <tr><td class="mi-lbl">PRE-DIPLOMA</td><td>Foundation, Matriculation, A-Levels, Pre-University</td></tr>
    <tr><td class="mi-lbl">DIPLOMA</td><td>Diploma in Business Studies, Diploma in Engineering</td></tr>
    <tr><td class="mi-lbl">UNDERGRADUATE</td><td>Bachelor&rsquo;s degrees (BA, BSc, BEng)</td></tr>
    <tr><td class="mi-lbl">POSTGRADUATE CERTIFICATE</td><td>PG Certificate in Leadership, PG Certificate in HR</td></tr>
    <tr><td class="mi-lbl">POSTGRADUATE DIPLOMA</td><td>PG Diploma in Marketing, PG Diploma in Law</td></tr>
    <tr><td class="mi-lbl">POSTGRADUATE MASTER&rsquo;S</td><td>MA, MSc, MBA, MEng</td></tr>
    <tr><td class="mi-lbl">POSTGRADUATE PHD</td><td>Doctor of Philosophy (PhD), Doctor of Education (EdD)</td></tr>
    <tr><td class="mi-lbl">PROFESSIONAL QUALIFYING CERTIFICATE</td><td>Licensing/qualifying credentials for practice (e.g., CLP, Chartered Accountant, Medical Board exams)</td></tr>
</table>

<div class="sh-crm" style="margin-top:10px;">RECOGNIZED VS. ACCREDITED DEGREES – COMPARISON MATRIX</div>
<table class="mi" style="margin-top:4px;">
    <tr>
        <th style="width:15%;">ASPECT</th>
        <th style="width:42%;">RECOGNIZED DEGREE</th>
        <th style="width:43%;">ACCREDITED DEGREE</th>
    </tr>
    <tr>
        <td class="mi-lbl">Definition</td>
        <td>Officially acknowledged by a government or regulatory authority as valid.</td>
        <td>Formally evaluated by an accrediting body to meet quality standards.</td>
    </tr>
    <tr>
        <td class="mi-lbl">Focus</td>
        <td>Legal validity and acceptance.</td>
        <td>Academic quality and assurance.</td>
    </tr>
    <tr>
        <td class="mi-lbl">Authority</td>
        <td>Ministry of Education, national qualifications agency, government registry.</td>
        <td>Accreditation boards (AACSB, ABET, EQUIS, etc.).</td>
    </tr>
    <tr>
        <td class="mi-lbl">Risk Level (ERM)</td>
        <td><span class="bold">{!! $dot("#00B050") !!} Low Risk</span> if recognized. &nbsp; <span class="bold">{!! $dot("#FFC000") !!} High Risk</span> if not recognized.</td>
        <td><span class="bold">{!! $dot("#00B0F0") !!} Moderate Risk</span> if accredited but not recognized locally. &nbsp; <span class="bold">{!! $dot("#00B050") !!} Low Risk</span> if both.</td>
    </tr>
</table>

<div class="sh-crm" style="margin-top:10px;">RISK MATRIX FOR FAKE VS. REAL UNIVERSITIES</div>
<table class="mi" style="margin-top:4px;">
    <tr>
        <th style="width:24%;">SCENARIO</th>
        <th style="width:33%;">INSTITUTION RECOGNITION</th>
        <th style="width:22%;">PROGRAM ACCREDITATION</th>
        <th style="width:21%; text-align:center;">RISK LEVEL</th>
    </tr>
    <tr>
        <td class="mi-lbl">Real + Accredited</td>
        <td>Recognized by MOHE/MQA</td>
        <td>Program accredited</td>
        <td style="text-align:center;" class="bold">{!! $dot("#00B050") !!} Low</td>
    </tr>
    <tr>
        <td class="mi-lbl">Real + Not Accredited</td>
        <td>Recognized institution</td>
        <td>Program not accredited</td>
        <td style="text-align:center;" class="bold">{!! $dot("#FFC000") !!} High</td>
    </tr>
    <tr>
        <td class="mi-lbl">Fake / Virtual</td>
        <td>Not recognized</td>
        <td>No accreditation</td>
        <td style="text-align:center;" class="bold">{!! $dot("#CC0000") !!} Critical</td>
    </tr>
    <tr>
        <td class="mi-lbl">Recognized Abroad Only</td>
        <td>Recognized overseas, not by MQA</td>
        <td>Accreditation varies</td>
        <td style="text-align:center;" class="bold">{!! $dot("#00B0F0") !!} Moderate</td>
    </tr>
</table>

<div class="sh-olv" style="margin-top:10px;">OVERALL PRACTICAL / ERM HIRING RISK DECISION FRAMEWORK</div>
<table class="mi" style="margin-top:4px;">
    <tr>
        <th style="width:13%;">SCENARIO</th>
        <th style="width:40%;">TYPICAL FINDINGS</th>
        <th style="width:17%; text-align:center;">OVERALL RISK</th>
        <th style="width:30%;">RECOMMENDED ACTION</th>
    </tr>
    <tr>
        <td class="mi-lbl risk-low bold">Low Risk</td>
        <td>All aspects verified. Minor variations only in non-critical fields.</td>
        <td style="text-align:center;" class="bold">{!! $dot("#00B050") !!} Low Risk</td>
        <td>Safe to proceed with hiring.</td>
    </tr>
    <tr>
        <td class="mi-lbl risk-moderate bold">Moderate Risk</td>
        <td>One or two aspects show Partial Match (e.g., dates slightly off, salary variation). No critical discrepancies.</td>
        <td style="text-align:center;" class="bold">{!! $dot("#00B0F0") !!} Moderate Risk</td>
        <td>Proceed with caution; request clarification from candidate.</td>
    </tr>
    <tr>
        <td class="mi-lbl risk-high bold">High Risk</td>
        <td>At least one critical aspect shows No Record (e.g., grades missing, employment unverifiable). Other fields verified.</td>
        <td style="text-align:center;" class="bold">{!! $dot("#FFC000") !!} High Risk</td>
        <td>Require secondary evidence (transcripts, pay slips, references) before proceeding.</td>
    </tr>
    <tr>
        <td class="mi-lbl risk-critical bold">Critical Risk</td>
        <td>Any aspect shows Discrepancy (false or significantly different information).</td>
        <td style="text-align:center;" class="bold">{!! $dot("#CC0000") !!} Critical Risk</td>
        <td>Serious concern for credibility. Strong recommendation not to proceed.</td>
    </tr>
</table>

<div class="pb"></div>

{{-- ══════════════════════════════════════════
     NRH LEGAL DISCLAIMER
═══════════════════════════════════════════ --}}
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
