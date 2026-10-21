{{--
    Official NRH Intelligence invoice — mirrors the approved Excel templates
    (NRH INVOICE TEMPLATE CASH / CREDIT). The only cash-vs-credit difference is
    the PAYMENT TERM value ($paymentTerm), resolved in the controller from the
    customer's billing mode.

    Palette: dark green #13501B (section bars, Excel theme accent-3 darkened),
    gold #D9C51A (payment-method headers). Kept faithful to the source file.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        font-size: 10px;
        color: #1a1a1a;
        background: #fff;
    }

    .page { padding: 30px 34px; }

    /* ── Company header ── */
    .masthead { width: 100%; border-collapse: collapse; }
    .masthead td { vertical-align: middle; }
    .mast-logo { width: 96px; }
    .mast-logo img { width: 88px; height: 88px; }
    .co-name {
        font-size: 17px; font-weight: 700; color: #13501B;
        letter-spacing: 0.02em; line-height: 1.1;
    }
    .co-reg { font-size: 9px; color: #444; margin-top: 4px; line-height: 1.5; }

    .contact { margin-top: 10px; }
    .contact-row { margin-bottom: 2px; }
    .contact-lbl {
        display: inline-block; width: 58px;
        font-size: 8.5px; font-weight: 700; letter-spacing: 0.08em;
        color: #6b7280; text-transform: uppercase;
    }
    .contact-val { font-size: 9.5px; color: #1a1a1a; }

    /* ── Section bars ── */
    .invoice-bar {
        background: #13501B; color: #fff;
        text-align: center; font-size: 16px; font-weight: 700;
        letter-spacing: 0.30em; padding: 8px 0;
        margin: 16px 0 14px;
    }

    /* ── Bill-to / meta grid ── */
    .meta { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .meta td { vertical-align: top; }
    .meta-left { width: 58%; padding-right: 20px; }
    .meta-right { width: 42%; }

    .block-lbl {
        font-size: 8.5px; font-weight: 700; letter-spacing: 0.10em;
        color: #6b7280; text-transform: uppercase; margin-bottom: 4px;
    }
    .bill-name { font-size: 12px; font-weight: 700; color: #111; margin-bottom: 4px; }
    .bill-line { font-size: 9.5px; color: #333; line-height: 1.55; }
    .bill-field { margin-top: 5px; }
    .bill-field .k {
        display: inline-block; width: 70px;
        font-size: 8.5px; font-weight: 700; letter-spacing: 0.06em;
        color: #6b7280; text-transform: uppercase;
    }
    .bill-field .v { font-size: 9.5px; color: #1a1a1a; }

    .meta-table { width: 100%; border-collapse: collapse; }
    .meta-table td { padding: 4px 8px; font-size: 9.5px; border: 1px solid #d8d8d8; }
    .meta-table td.k {
        background: #f4f6f4; font-weight: 700; color: #444;
        text-transform: uppercase; letter-spacing: 0.05em; font-size: 8.5px;
        width: 46%; white-space: nowrap;
    }
    .meta-table td.v { font-family: 'Courier New', monospace; text-align: right; }

    /* ── Line items ── */
    .items { width: 100%; border-collapse: collapse; }
    .items th {
        background: #13501B; color: #fff;
        font-size: 8.5px; font-weight: 700; letter-spacing: 0.08em;
        text-transform: uppercase; padding: 8px 8px; border: 1px solid #13501B;
    }
    .items td { border: 1px solid #d8d8d8; padding: 7px 8px; font-size: 9.5px; vertical-align: top; }
    .col-item  { width: 34px;  text-align: center; }
    .col-qty   { width: 42px;  text-align: center; }
    .col-price { width: 96px;  text-align: right; }
    .col-amt   { width: 96px;  text-align: right; }
    .items td.num { font-family: 'Courier New', monospace; }

    .line-rep { font-weight: 700; color: #111; }
    .line-scope { color: #444; margin-top: 2px; }
    .line-scope .tag {
        font-size: 8px; font-weight: 700; letter-spacing: 0.06em;
        color: #6b7280; text-transform: uppercase;
    }

    /* ── Totals ── */
    .totals { width: 100%; border-collapse: collapse; margin-top: -1px; }
    .totals td { padding: 6px 8px; font-size: 10px; border: 1px solid #d8d8d8; }
    .totals .spacer { border: none; }
    .totals .t-lbl {
        text-align: right; font-weight: 700; color: #444;
        text-transform: uppercase; letter-spacing: 0.06em; font-size: 9px;
        width: 96px;
    }
    .totals .t-val { text-align: right; font-family: 'Courier New', monospace; width: 96px; }
    .totals .grand td {
        background: #13501B; color: #fff; font-weight: 700;
        border-color: #13501B;
    }
    .totals .grand .t-lbl { color: #fff; font-size: 10px; }

    /* ── Payment methods ── */
    .pay { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .pay td { vertical-align: top; width: 50%; }
    .pay td.left { padding-right: 9px; }
    .pay td.right { padding-left: 9px; }
    .pay-box { border: 1px solid #d8d8d8; }
    .pay-head {
        background: #D9C51A; color: #1a1a1a;
        font-size: 9px; font-weight: 700; letter-spacing: 0.08em;
        text-transform: uppercase; padding: 6px 10px;
    }
    .pay-body { padding: 9px 10px; }
    .pay-row { width: 100%; border-collapse: collapse; }
    .pay-row td { border: none; padding: 2px 0; font-size: 9.5px; }
    .pay-row td.k {
        width: 42%; font-weight: 700; color: #6b7280;
        text-transform: uppercase; letter-spacing: 0.05em; font-size: 8.5px;
    }
    .pay-row td.v { font-family: 'Courier New', monospace; color: #111; }
    .cheque-lead { font-size: 9px; color: #555; margin-bottom: 6px; }
    .cheque-name { font-size: 11px; font-weight: 700; color: #111; }

    /* ── Note + footer ── */
    .note {
        margin-top: 14px; font-size: 9px; color: #555; font-style: italic;
        line-height: 1.5;
    }
    .gen-footer {
        margin-top: 12px; text-align: center;
        font-size: 9px; font-weight: 700; letter-spacing: 0.08em;
        color: #13501B; text-transform: uppercase;
        border-top: 1.5px solid #13501B; border-bottom: 1.5px solid #13501B;
        padding: 7px 0;
    }
</style>
</head>
<body>
<div class="page">

    {{-- ── Company masthead ── --}}
    <table class="masthead">
        <tr>
            @if($logoSrc)
            <td class="mast-logo"><img src="{{ $logoSrc }}" alt="NRH Intelligence"></td>
            @endif
            <td>
                <div class="co-name">{{ $company['name'] }}</div>
                <div class="co-reg">
                    Co. Reg No.: {{ $company['reg_no'] }}<br>
                    Tax Identification No: {{ $company['tax_no'] }}
                </div>
            </td>
        </tr>
    </table>

    <div class="contact">
        <div class="contact-row">
            <span class="contact-lbl">Address:</span>
            <span class="contact-val">{{ $company['address'] }}</span>
        </div>
        <div class="contact-row">
            <span class="contact-lbl">Tel:</span>
            <span class="contact-val">{{ $company['phone'] }}</span>
        </div>
        <div class="contact-row">
            <span class="contact-lbl">Email:</span>
            <span class="contact-val">{{ $company['email'] }}</span>
        </div>
    </div>

    {{-- ── INVOICE bar ── --}}
    <div class="invoice-bar">INVOICE</div>

    {{-- ── Invoiced-to / invoice meta ── --}}
    @php
        $cust = $invoice->customer;
        $issued = $invoice->issued_at?->format('d/m/Y') ?? '';
    @endphp
    <table class="meta">
        <tr>
            <td class="meta-left">
                <div class="block-lbl">Invoiced To:</div>
                <div class="bill-name">{{ $cust->name }}</div>
                <div class="bill-field"><span class="k">Address:</span><span class="v">{{ $cust->address ?: '—' }}</span></div>
                <div class="bill-field"><span class="k">Attention:</span><span class="v">{{ $cust->contact_name ?: '—' }}</span></div>
                <div class="bill-field"><span class="k">Phone:</span><span class="v">{{ $cust->contact_phone ?: '—' }}</span></div>
                <div class="bill-field"><span class="k">Email:</span><span class="v">{{ $cust->contact_email ?: '—' }}</span></div>
            </td>
            <td class="meta-right">
                <table class="meta-table">
                    <tr><td class="k">Date</td><td class="v">{{ $issued }}</td></tr>
                    <tr><td class="k">Invoice No</td><td class="v">{{ $invoice->number }}</td></tr>
                    <tr><td class="k">P.O No</td><td class="v">NIL</td></tr>
                    <tr><td class="k">Invoice Date</td><td class="v">{{ $issued }}</td></tr>
                    <tr><td class="k">Payment Term</td><td class="v">{{ $paymentTerm }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ── Line items ── --}}
    <table class="items">
        <thead>
            <tr>
                <th class="col-item">Item</th>
                <th style="text-align:left;">Description</th>
                <th class="col-qty">Qty</th>
                <th class="col-price">Per / Price<br>(MYR)</th>
                <th class="col-amt">Amount<br>(MYR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
                @php
                    // Two description shapes reach the invoice:
                    //   cash  → "{candidate} — {scope}"  (REP NO lives on the request)
                    //   admin → "{scope} – {REF}"        (ref embedded, no candidate)
                    // Resolve each into REP NO / CANDIDATE lines; the scope type is
                    // the billing kind, not the scope name — Package when the line was
                    // generated from a bundle (tagged "(package)"), otherwise Ala Carte.
                    $desc = (string) $item->description;
                    $repNo = null;
                    $candidate = null;

                    if (preg_match('/\bRE[PQ]-\d{4}-\d+/u', $desc, $m)) {
                        $repNo = $m[0];
                    } elseif (str_contains($desc, ' — ')) {
                        [$candidate] = array_map('trim', explode(' — ', $desc, 2));
                    }
                    // Cash lines carry no embedded ref — take it from the linked request.
                    $repNo = $repNo ?: optional($invoice->screeningRequests->first())->reference;

                    $scopeType = preg_match('/\(\s*package\s*\)/iu', $desc) ? 'Package' : 'Ala Carte';
                @endphp
                <tr>
                    <td class="col-item num">{{ $i + 1 }}</td>
                    <td>
                        @if($repNo)
                            <div class="line-rep">REP NO: {{ $repNo }}</div>
                        @endif
                        @if($candidate)
                            <div class="line-scope"><span class="tag">Candidate:</span> {{ $candidate }}</div>
                        @endif
                        <div class="line-scope"><span class="tag">Scope Type:</span> {{ $scopeType }}</div>
                    </td>
                    <td class="col-qty num">{{ $item->qty }}</td>
                    <td class="col-price num">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="col-amt num">{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── Totals ── --}}
    @php
        $sstRate = $invoice->subtotal > 0 ? round($invoice->tax / $invoice->subtotal * 100) : 0;
    @endphp
    <table class="totals">
        <tr>
            <td class="spacer"></td>
            <td class="t-lbl">Subtotal</td>
            <td class="t-val">{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="spacer"></td>
            <td class="t-lbl">SST ({{ $sstRate }}%)</td>
            <td class="t-val">{{ number_format($invoice->tax, 2) }}</td>
        </tr>
        <tr class="grand">
            <td class="spacer"></td>
            <td class="t-lbl">Total (MYR)</td>
            <td class="t-val">{{ number_format($invoice->total, 2) }}</td>
        </tr>
    </table>

    {{-- ── Payment methods ── --}}
    <table class="pay">
        <tr>
            <td class="left">
                <div class="pay-box">
                    <div class="pay-head">Online Transfer Payment Method</div>
                    <div class="pay-body">
                        <table class="pay-row">
                            <tr><td class="k">Bank</td><td class="v">{{ $bank['name'] }}</td></tr>
                            <tr><td class="k">Account No</td><td class="v">{{ $bank['account_number'] }}</td></tr>
                            <tr><td class="k">Swift Code</td><td class="v">{{ $bank['swift'] }}</td></tr>
                            <tr><td class="k">Account Holder</td><td class="v">{{ $bank['account_holder'] }}</td></tr>
                        </table>
                    </div>
                </div>
            </td>
            <td class="right">
                <div class="pay-box">
                    <div class="pay-head">Cheque Payment Method</div>
                    <div class="pay-body">
                        <div class="cheque-lead">All Cheques must be issued to:</div>
                        <div class="cheque-name">{{ $chequePayee }}</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Note + computer-generated footer ── --}}
    <div class="note">
        Note: Payment Notification is required upon full settlement for Closure of Invoice Status
    </div>
    <div class="gen-footer">
        This is a computer-generated invoice; no signature required
    </div>

</div>
</body>
</html>
