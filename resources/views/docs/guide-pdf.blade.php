<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>NRH Admin Portal — Staff Guide</title>
<style>
/* Layout mirrors reports/screening.blade.php so the two documents look like one
   family: Oswald for headers/labels, Courier Prime for body, same green/gold palette. */
@page { margin: 78px 45px 52px 45px; }
* { box-sizing: border-box; }
body { font-family: 'Courier Prime', 'Courier', monospace; font-size: 8.5pt; color: #111; line-height: 1.55; }

/* ── Persistent header / footer ── */
.ph { position: fixed; top: -64px; left: 0; right: 0; height: 54px; border-bottom: 2px solid #023527; }
.ph table { width: 100%; height: 51px; border-collapse: collapse; }
.ph td { border: none; padding: 0; vertical-align: top; }
.ph .ph-t { font-family: 'Oswald', sans-serif; font-size: 9pt; font-weight: bold;
            color: #023527; letter-spacing: 0.06em; }
.ph .ph-s { font-family: 'Oswald', sans-serif; font-size: 7.5pt; color: #666; letter-spacing: 0.05em; }
.ph .ph-logo { text-align: right; }
.ph .ph-logo img { height: 42px; }

.pf { position: fixed; bottom: -38px; left: 0; right: 0; height: 22px;
      border-top: 1px solid #023527; padding-top: 3px;
      font-size: 7pt; color: #888; font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; }

/* ── Cover ── */
.cov { text-align: center; padding-top: 120px; }
.cov img { height: 88px; }
.cov-title { font-size: 20pt; font-weight: bold; color: #023527; letter-spacing: 0.09em;
             margin: 26px 0 4px; font-family: 'Courier Prime', 'Courier', monospace; }
.cov-sub { font-family: 'Oswald', sans-serif; font-size: 8.5pt; color: #888;
           letter-spacing: 0.24em; text-transform: uppercase; margin-bottom: 30px; }
.cov-rule { border-top: 2px solid #C5A82D; width: 140px; margin: 0 auto 26px; }
.cov-meta { font-family: 'Oswald', sans-serif; font-size: 8pt; color: #555; letter-spacing: 0.06em; line-height: 2; }
.cov-conf { margin: 44px auto 0; width: 78%; border: 1px solid #023527; background: #f8fdf9;
            padding: 9px 14px; font-size: 7.5pt; line-height: 1.6; text-align: left; color: #023527; }

/* ── Contents ── */
.toc-h { background: #000; color: #fff; padding: 4px 12px 8px; font-weight: bold;
         font-size: 9.5pt; letter-spacing: 0.08em; font-family: 'Oswald', sans-serif;
         margin: 0 0 10px; }
table.toc { width: 100%; border-collapse: collapse; }
table.toc td { border: none; padding: 4px 0 4px 0; vertical-align: top;
               border-bottom: 1px dotted #ccc; }
table.toc td.toc-n { width: 30px; font-family: 'Oswald', sans-serif; font-weight: bold;
                     color: #C5A82D; font-size: 9pt; }
table.toc td.toc-t { font-family: 'Oswald', sans-serif; font-size: 9pt; }
table.toc td.toc-t a { color: #023527; text-decoration: none; font-weight: bold; }
table.toc .toc-s { display: block; font-family: 'Courier Prime', 'Courier', monospace;
                   font-size: 7.5pt; color: #777; font-weight: normal; letter-spacing: 0; }

/* ── Section headers ── */
table.sec-bar { width: 100%; border-collapse: collapse; margin: 0 0 6px; page-break-inside: avoid;
                page-break-after: avoid; }
table.sec-bar td { background: #000; color: #fff; padding: 5px 12px 8px; font-weight: bold;
                   font-size: 9.5pt; letter-spacing: 0.07em; font-family: 'Oswald', sans-serif;
                   vertical-align: middle; border: none; }
table.sec-bar td.sec-n { width: 34px; background: #C5A82D; color: #000; text-align: center; }
.sec-perm { background: #DDD9C3; color: #000; padding: 3px 10px 5px; font-size: 7.5pt;
            font-family: 'Oswald', sans-serif; letter-spacing: 0.04em; margin: 0 0 8px; }
.sec-perm b { color: #023527; }

/* ── Body blocks ── */
.g-p { margin: 0 0 8px; text-align: justify; }
.g-sub { font-family: 'Oswald', sans-serif; font-weight: bold; font-size: 8.5pt;
         color: #023527; letter-spacing: 0.05em; margin: 12px 0 5px;
         border-bottom: 1px solid #C5A82D; padding-bottom: 2px; page-break-after: avoid; }

table.g-steps, table.g-list { width: 100%; border-collapse: collapse; margin: 0 0 8px; }
table.g-steps td, table.g-list td { border: none; padding: 2px 0 4px; vertical-align: top; }
td.g-step-n { width: 20px; font-family: 'Oswald', sans-serif; font-weight: bold;
              color: #76923C; font-size: 8.5pt; }
td.g-bullet { width: 14px; color: #C5A82D; font-weight: bold; }
td.g-step-t, td.g-list-t { text-align: justify; }

/* Break avoidance is per row, not per table: the reference and troubleshooting
   tables are taller than a page, and avoiding a break on the whole table would
   push it off the bottom and clip it. */
table.g-table { width: 100%; border-collapse: collapse; margin: 0 0 10px; }
table.g-table tr { page-break-inside: avoid; }
table.g-table th { background: #76923C; color: #fff; font-family: 'Oswald', sans-serif;
                   font-weight: bold; letter-spacing: 0.04em; text-align: left;
                   border: 1px solid #2a2a2a; padding: 4px 8px 7px; font-size: 8.5pt; }
table.g-table td { border: 1px solid #2a2a2a; padding: 4px 8px 7px; vertical-align: top; line-height: 1.35; }

table.g-call { width: 100%; border-collapse: collapse; margin: 8px 0 10px; page-break-inside: avoid; }
table.g-call td { padding: 7px 11px 9px; font-size: 8pt; line-height: 1.55; }
table.g-note td { border: 1px solid #023527; background: #f8fdf9; }
table.g-warn td { border: 1px solid #c4453a; background: #fef4f3; }
.g-call-t { font-family: 'Oswald', sans-serif; font-weight: bold; font-size: 8pt;
            letter-spacing: 0.04em; text-transform: uppercase; }
table.g-note .g-call-t { color: #023527; }
table.g-warn .g-call-t { color: #c4453a; }
.g-call-b { display: block; margin-top: 2px; text-align: justify; }

table.g-links { width: 100%; border-collapse: collapse; margin: 8px 0 12px; page-break-inside: avoid; }
table.g-links td { border: none; padding: 4px 0; vertical-align: middle; font-size: 8pt; }
td.g-links-lbl { width: 82px; font-family: 'Oswald', sans-serif; font-size: 7.5pt;
                 color: #888; letter-spacing: 0.06em; text-transform: uppercase; }
a.g-ext { color: #0070C0; text-decoration: none; font-weight: bold; }
.g-sep { color: #ccc; }

code { font-family: 'Courier Prime', 'Courier', monospace; background: #f1f1ee;
       border: 1px solid #e0e0da; padding: 0 3px; font-size: 8pt; }
.sec { page-break-inside: auto; }
</style>
</head>
<body>

{{-- Running header / footer, repeated on every page --}}
<div class="ph">
    <table>
        <tr>
            <td>
                <div class="ph-t">ADMIN PORTAL GUIDE</div>
                <div class="ph-s">NRH INTELLIGENCE SDN. BHD. — INTERNAL</div>
            </td>
            @if($logoSrc)
            <td class="ph-logo"><img src="{{ $logoSrc }}" alt="NRH"></td>
            @endif
        </tr>
    </table>
</div>
<div class="pf">CONFIDENTIAL — INTERNAL USE ONLY &nbsp;·&nbsp; Generated {{ $generatedAt }}</div>

{{-- ─────────────── Cover ─────────────── --}}
<div class="cov">
    @if($logoSrc)<img src="{{ $logoSrc }}" alt="NRH">@endif
    <div class="cov-title">ADMIN PORTAL GUIDE</div>
    <div class="cov-sub">NRH Intelligence &mdash; Staff Operating Manual</div>
    <div class="cov-rule"></div>
    <div class="cov-meta">
        {{ $sectionCount }} SECTIONS<br>
        GENERATED {{ strtoupper($generatedAt) }}<br>
        {{ strtoupper($portalUrl) }}
    </div>
    <div class="cov-conf">
        <b>Confidential.</b> This guide describes internal systems that hold candidate personal
        data, client commercial terms and financial records. It is for NRH Intelligence staff
        only and must not be shared outside the company.
    </div>
</div>

{{-- ─────────────── Contents ─────────────── --}}
<div style="page-break-before:always;"></div>
<div class="toc-h">CONTENTS</div>
<table class="toc">
    @foreach($sections as $i => $s)
    <tr>
        <td class="toc-n">{{ $i + 1 }}</td>
        <td class="toc-t">
            <a href="#{{ $s['id'] }}">{{ $s['title'] }}</a>
            <span class="toc-s">{{ $s['summary'] }}</span>
        </td>
    </tr>
    @endforeach
</table>

{{-- ─────────────── Sections ─────────────── --}}
@foreach($sections as $i => $s)
{{-- The id must sit on a block that actually renders. dompdf registers a named
     destination from AbstractRenderer::addNamedDest(), which only runs for frames
     that reach a renderer — an empty <a name> anchor produces no frame, and the
     contents-page links then emit an empty action (a dead link). --}}
<div class="sec" id="{{ $s['id'] }}" style="page-break-before:always;">
    <table class="sec-bar">
        <tr>
            <td class="sec-n">{{ $i + 1 }}</td>
            <td>{{ strtoupper($s['title']) }}</td>
        </tr>
    </table>
    @if($s['permission'])
    <div class="sec-perm"><b>REQUIRES:</b> {{ $s['permission'] }}</div>
    @endif

    @include('docs._blocks', ['blocks' => $s['blocks']])
</div>
@endforeach

</body>
</html>
