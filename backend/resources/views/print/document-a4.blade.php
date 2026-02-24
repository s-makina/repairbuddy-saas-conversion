@if(!($embedded ?? false))
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $docType === 'estimate' ? 'Estimate' : 'Work Order' }} — {{ $docNumber }} — {{ $shopName }}</title>
@endif
<style>
/*
 * print/document-a4.blade.php
 * Printable A4 document — clean & light.
 * System tokens: --rb-blue, --rb-orange, --rb-text, --rb-border, --rb-surface-muted.
 * Supports both estimates ($docType='estimate') and jobs ($docType='job').
 */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --rb-blue:    #063e70;
    --rb-orange:  #fd6742;
    --rb-text:    #2c304d;
    --rb-text-2:  #5f6380;
    --rb-text-3:  #9496ad;
    --rb-border:  #ededed;
    --rb-muted:   #f7f7f7;
    --rb-surface: #ffffff;
    --rb-radius:  5px;
    --rb-green:   #16a34a;
}
body {
    background: #f0f1f3;
    font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    color: var(--rb-text); font-size: 12px; line-height: 1.4;
    -webkit-font-smoothing: antialiased;
    padding: 2rem 1rem 3rem;
}

/* ── Action bar ── */
.bar {
    position: fixed; inset: 0 0 auto 0; z-index: 100;
    background: rgba(6,62,112,.95); backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: space-between;
    padding: .5rem 1.4rem; gap: 1rem;
}
.bar-title { color: #e2e8f0; font-size: .8rem; font-weight: 600; display: flex; align-items: center; gap: .4rem; }
.bar-title i { color: var(--rb-orange); font-style: normal; }
.bar-actions { display: flex; gap: .35rem; }
.btn {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .73rem; font-weight: 600; border-radius: var(--rb-radius);
    border: 1px solid transparent; padding: .32rem .7rem;
    cursor: pointer; transition: all .12s; line-height: 1.4; text-decoration: none;
}
.btn-ghost { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.15); color: #d1d5db; }
.btn-ghost:hover { background: rgba(255,255,255,.14); }
.btn-primary { background: var(--rb-orange); border-color: var(--rb-orange); color: #fff; }
.btn-primary:hover { opacity: .9; }

/* ── Paper ── */
.paper {
    max-width: 760px;
    margin: 3rem auto 0;
    background: var(--rb-surface);
    border-radius: 8px;
    box-shadow: 0 1px 15px 1px rgba(52,40,104,.08);
    overflow: hidden;
}

/* ── Header ── */
.hd {
    padding: .75rem 1.5rem;
    border-bottom: 1px solid var(--rb-border);
    display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;
}
.shop-name { font-size: .85rem; font-weight: 800; color: var(--rb-text); }
.shop-meta { font-size: .65rem; color: var(--rb-text-3); line-height: 1.55; margin-top: .05rem; }
.doc-right { text-align: right; flex-shrink: 0; }
.doc-type  { font-size: .54rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--rb-text-3); }
.doc-num   { font-size: 1.05rem; font-weight: 900; color: var(--rb-blue); line-height: 1.1; }
.doc-dates { font-size: .65rem; color: var(--rb-text-3); line-height: 1.55; margin-top: .05rem; }
.badge {
    display: inline-flex; align-items: center; gap: .2rem;
    margin-top: .2rem; font-size: .56rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .04em; padding: .12rem .4rem; border-radius: var(--rb-radius);
    border: 1px solid;
}
.badge-pending  { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.badge-approved { background: #dcfce7; color: #14532d; border-color: #bbf7d0; }
.badge-open     { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
.badge-closed   { background: #f3f4f6; color: #374151; border-color: #d1d5db; }

/* ── Info strip ── */
.info-strip { display: grid; grid-template-columns: 1fr 1fr 1fr; border-bottom: 1px solid var(--rb-border); }
.is-cell { padding: .4rem .8rem; border-right: 1px solid var(--rb-border); }
.is-cell:last-child { border-right: none; }
.is-lbl { font-size: .54rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--rb-text-3); margin-bottom: .08rem; }
.is-val { font-size: .75rem; color: var(--rb-text-2); line-height: 1.45; }
.is-val strong { color: var(--rb-text); font-weight: 700; display: block; font-size: .78rem; }

/* ── Body ── */
.bd { padding: .75rem 1.5rem; }
.sec { margin-bottom: .65rem; }
.sec-lbl {
    font-size: .54rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .08em; color: var(--rb-text-3); margin-bottom: .25rem;
    padding-bottom: .15rem; border-bottom: 1px solid var(--rb-border);
}

/* Devices */
.chips { display: flex; flex-wrap: wrap; gap: .25rem; }
.chip {
    display: inline-flex; align-items: center; gap: .25rem;
    font-size: .68rem; font-weight: 600; padding: .12rem .45rem;
    border: 1px solid var(--rb-border); border-radius: var(--rb-radius);
    color: var(--rb-text-2); background: var(--rb-muted);
}
.chip i { color: var(--rb-text-3); font-size: .62rem; font-style: normal; }

/* Diagnostic / work summary note */
.diag-note {
    font-size: .72rem; color: var(--rb-text-2); line-height: 1.5;
    padding: .35rem .65rem; background: var(--rb-muted); border-radius: var(--rb-radius);
    white-space: pre-wrap;
}

/* Device condition table (job only) */
.dtbl { width: 100%; border-collapse: collapse; }
.dtbl thead th { font-size: .54rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--rb-text-3); padding: .25rem .4rem; border-bottom: 1.5px solid var(--rb-text); text-align: left; }
.dtbl tbody td { padding: .3rem .4rem; border-bottom: 1px solid var(--rb-border); font-size: .72rem; vertical-align: middle; }
.dtbl tbody tr:last-child td { border-bottom: none; }
.ctag { font-size: .55rem; font-weight: 700; padding: .04rem .35rem; border-radius: 2px; border: 1px solid; display: inline-block; text-transform: uppercase; letter-spacing: .03em; }
.ctag-ok  { border-color: #d1d5db; color: var(--rb-text-3); }
.ctag-bad { border-color: var(--rb-text); color: var(--rb-text); background: var(--rb-muted); }

/* Items table */
.tbl { width: 100%; border-collapse: collapse; }
.tbl thead th {
    font-size: .54rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    color: var(--rb-text-3); padding: .25rem .4rem;
    border-bottom: 1.5px solid var(--rb-text); text-align: left;
}
.tbl thead th.r { text-align: right; }
.tbl thead th.c { text-align: center; }
.tbl tbody td {
    padding: .3rem .4rem; border-bottom: 1px solid var(--rb-border);
    font-size: .75rem; vertical-align: top;
}
.tbl tbody tr:last-child td { border-bottom: none; }
.it-name { font-weight: 600; color: var(--rb-text); }
.it-sub  { font-size: .62rem; color: var(--rb-text-3); margin-top: .02rem; }
.tag {
    display: inline-block; font-size: .5rem; font-weight: 700; padding: .02rem .3rem;
    border: 1px solid var(--rb-border); border-radius: 3px;
    color: var(--rb-text-3); margin-top: .04rem; text-transform: uppercase; letter-spacing: .03em;
}
.tbl td.r { text-align: right; font-weight: 600; }
.tbl td.c { text-align: center; color: var(--rb-text-3); }

/* Totals */
.totals-wrap { display: flex; justify-content: flex-end; margin-top: .35rem; }
.totals { width: 210px; }
.tr { display: flex; justify-content: space-between; padding: .1rem 0; font-size: .72rem; color: var(--rb-text-2); }
.tr span:last-child { font-weight: 600; color: var(--rb-text); }
.tdiv { border: none; border-top: 1px solid var(--rb-border); margin: .15rem 0; }
.tgrand {
    display: flex; justify-content: space-between; padding: .2rem 0;
    border-top: 1.5px solid var(--rb-text); margin-top: .05rem;
}
.tgrand span:first-child { font-size: .78rem; font-weight: 800; }
.tgrand span:last-child  { font-size: .95rem; font-weight: 900; color: var(--rb-blue); }

/* Warranty chips */
.warr-chips { display: flex; flex-wrap: wrap; gap: .25rem; }
.warr-chip { font-size: .66rem; font-weight: 600; padding: .12rem .45rem; border: 1px solid var(--rb-border); border-radius: var(--rb-radius); color: var(--rb-text-2); display: inline-flex; align-items: center; gap: .25rem; }

/* Terms */
.terms {
    font-size: .62rem; color: var(--rb-text-3); line-height: 1.5;
    padding-top: .4rem; border-top: 1px solid var(--rb-border);
}
.terms ol { margin: 0; padding-left: .85rem; }
.terms li { margin-bottom: 0; }

/* Note */
.note {
    font-size: .68rem; color: var(--rb-text-3); line-height: 1.55;
    margin-top: .5rem; padding-top: .35rem; border-top: 1px solid var(--rb-border);
}

/* Signature */
.sig-row {
    margin-top: .6rem; display: grid; grid-template-columns: 1fr 1fr;
    gap: 1.2rem; padding: .45rem .55rem; border: 1px solid var(--rb-border); border-radius: var(--rb-radius);
}
.sig-lbl { font-size: .54rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--rb-text-3); margin-bottom: .3rem; }
.sig-line { border-bottom: 1px solid var(--rb-text-3); height: 22px; margin-bottom: .15rem; }
.sig-sub { font-size: .58rem; color: var(--rb-text-3); }

/* Footer */
.foot {
    padding: .4rem 1.5rem; border-top: 1px solid var(--rb-border);
    display: flex; justify-content: space-between; align-items: center; gap: .75rem;
}
.foot-text { font-size: .58rem; color: var(--rb-text-3); line-height: 1.45; max-width: 480px; }
.foot-num  { font-size: .62rem; font-weight: 700; color: var(--rb-text-3); white-space: nowrap; }

/* ── Print ── */
@media print {
    @page { size: A4; margin: 12mm 14mm; }
    *, *::before, *::after {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    body { background: #fff; padding: 0; font-size: 11px; margin: 0; }
    .bar { display: none !important; }
    .paper { margin: 0; box-shadow: none; border-radius: 0; max-width: 100%; border: none; overflow: visible; }
    .hd { padding: .5rem 1.2rem; }
    .is-cell { padding: .3rem .7rem; }
    .bd { padding: .6rem 1.2rem; }
    .foot { padding: .3rem 1.2rem; }
    .sec { margin-bottom: .5rem; }
    .sig-row { break-inside: avoid; }
    .tbl thead th, .dtbl thead th { border-bottom-color: var(--rb-text) !important; }
    .tgrand { border-top-color: var(--rb-text) !important; }
    .ctag-bad { background: var(--rb-muted) !important; }
}

@media (max-width: 640px) {
    .info-strip { grid-template-columns: 1fr; }
    .is-cell { border-right: none; border-bottom: 1px solid var(--rb-border); }
    .is-cell:last-child { border-bottom: none; }
    .sig-row { grid-template-columns: 1fr; }
    .hd { flex-direction: column; }
    .doc-right { text-align: left; }
    .totals { width: 100%; }
}
</style>
@if(!($embedded ?? false))
</head>
<body>
@endif

{{-- ════════════════ ACTION BAR ════════════════ --}}
@if(!($embedded ?? false))
<div class="bar">
  <div class="bar-title">
    <i>&#9776;</i>
    @if($docType === 'estimate')
      Estimate &mdash; {{ $docNumber }}
    @else
      Work Order &mdash; {{ $docNumber }}
    @endif
  </div>
  <div class="bar-actions">
    <a href="{{ $backUrl ?? '#' }}" class="btn btn-ghost">&#8592; Back</a>
    <a href="{{ $pdfUrl ?? '#' }}" class="btn btn-ghost" target="_blank">&#11015; PDF</a>
    <button class="btn btn-primary" onclick="window.print()">&#9113; Print</button>
  </div>
</div>
@endif

{{-- ════════════════ PAPER ════════════════ --}}
<div class="paper">

  {{-- ── HEADER ── --}}
  <div class="hd">
    <div>
      <div class="shop-name">{{ $shopName }}</div>
      <div class="shop-meta">
        @if($shopAddress) {{ $shopAddress }}<br>@endif
        @if($shopPhone) {{ $shopPhone }}@if($shopEmail) &nbsp;&middot;&nbsp; {{ $shopEmail }}@endif
        @elseif($shopEmail)
          {{ $shopEmail }}
        @endif
      </div>
    </div>
    <div class="doc-right">
      <div class="doc-type">{{ $docType === 'estimate' ? 'Estimate / Quote' : 'Work Order / Job' }}</div>
      <div class="doc-num">{{ $docNumber }}</div>
      <div class="doc-dates">
        @if($docType === 'estimate')
          Issued: {{ $doc->created_at?->format('M j, Y') ?? '—' }}<br>
          @if($doc->pickup_date) Est. Pickup: {{ \Carbon\Carbon::parse($doc->pickup_date)->format('M j, Y') }}@endif
        @else
          Opened: {{ $doc->opened_at?->format('M j, Y') ?? $doc->created_at?->format('M j, Y') ?? '—' }}<br>
          @if($doc->delivery_date) Est. Delivery: {{ \Carbon\Carbon::parse($doc->delivery_date)->format('M j, Y') }}@endif
        @endif
      </div>
      @php
        $badgeClass = 'badge-open';
        $sl = strtolower($statusLabel ?? '');
        if (in_array($sl, ['pending', 'draft', 'sent'])) $badgeClass = 'badge-pending';
        elseif (in_array($sl, ['approved', 'completed', 'closed', 'paid'])) $badgeClass = 'badge-approved';
        elseif (in_array($sl, ['rejected', 'cancelled'])) $badgeClass = 'badge-closed';
      @endphp
      <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
      @if($docType === 'job' && !empty($paymentLabel))
        <span class="badge badge-pending">{{ $paymentLabel }}</span>
      @endif
    </div>
  </div>

  {{-- ── INFO STRIP ── --}}
  <div class="info-strip">
    {{-- Prepared For / Bill To --}}
    <div class="is-cell">
      <div class="is-lbl">{{ $docType === 'estimate' ? 'Prepared For' : 'Bill To' }}</div>
      <div class="is-val">
        <strong>{{ $customer?->name ?? '—' }}</strong>
        @if($customer?->email) {{ $customer->email }}<br>@endif
        @if($customer?->phone) {{ $customer->phone }}@endif
      </div>
    </div>
    {{-- Reference --}}
    <div class="is-cell">
      <div class="is-lbl">{{ $docType === 'estimate' ? 'Reference' : 'Job Reference' }}</div>
      <div class="is-val">
        <strong>{{ $docNumber }}</strong>
        @if($doc->case_number) Case: {{ $doc->case_number }}@endif
        @if($docType === 'job' && ($doc->priority ?? null)) &middot; Priority: {{ ucfirst($doc->priority) }}@endif
      </div>
    </div>
    {{-- Technician --}}
    <div class="is-cell">
      <div class="is-lbl">{{ $docType === 'estimate' ? 'Prepared By' : 'Technician' }}</div>
      <div class="is-val">
        <strong>{{ $technician?->name ?? 'Unassigned' }}</strong>
        @if($docType === 'job')
          @if(!empty($paymentLabel)) Payment: {{ $paymentLabel }}@endif
        @endif
      </div>
    </div>
  </div>

  {{-- ════ BODY ════ --}}
  <div class="bd">

    {{-- ── DEVICES ── --}}
    @if($devices->isNotEmpty())
    <div class="sec">
      <div class="sec-lbl">Devices</div>
      <div class="chips">
        @foreach($devices as $dev)
          @php
            $label  = $dev->label_snapshot ?? $dev->customerDevice?->device?->name ?? 'Device';
            $serial = $dev->serial_snapshot ?? $dev->customerDevice?->serial_number ?? null;
          @endphp
          <span class="chip">
            <i>&#9000;</i>
            {{ $label }}@if($serial) &middot; S/N {{ $serial }}@endif
          </span>
        @endforeach
      </div>
    </div>
    @endif

    {{-- ── DEVICE CONDITION TABLE (job only) ── --}}
    @if($docType === 'job' && $devices->isNotEmpty())
    <div class="sec">
      <div class="sec-lbl">Device Condition at Drop-off</div>
      <table class="dtbl">
        <thead>
          <tr>
            <th style="width:35%">Device</th>
            <th style="width:15%">Power</th>
            <th style="width:15%">Screen</th>
            <th style="width:15%">Charging</th>
            <th style="width:20%">Notes</th>
          </tr>
        </thead>
        <tbody>
          @foreach($devices as $dev)
            @php
              $label  = $dev->label_snapshot ?? $dev->customerDevice?->device?->name ?? 'Device';
              $extras = is_string($dev->extra_fields_snapshot_json)
                        ? json_decode($dev->extra_fields_snapshot_json, true)
                        : (is_array($dev->extra_fields_snapshot_json) ? $dev->extra_fields_snapshot_json : []);
              $power   = $extras['power_on']    ?? null;
              $screen  = $extras['screen_ok']   ?? null;
              $charge  = $extras['charging_ok'] ?? null;
            @endphp
            <tr>
              <td>{{ $label }}@if($dev->serial_snapshot) <span style="color:var(--rb-text-3);font-size:.62rem;display:block">S/N {{ $dev->serial_snapshot }}</span>@endif</td>
              <td>@if(!is_null($power))<span class="ctag {{ $power ? 'ctag-ok' : 'ctag-bad' }}">{{ $power ? 'OK' : 'Fault' }}</span>@else <span class="ctag ctag-ok">&mdash;</span>@endif</td>
              <td>@if(!is_null($screen))<span class="ctag {{ $screen ? 'ctag-ok' : 'ctag-bad' }}">{{ $screen ? 'OK' : 'Fault' }}</span>@else <span class="ctag ctag-ok">&mdash;</span>@endif</td>
              <td>@if(!is_null($charge))<span class="ctag {{ $charge ? 'ctag-ok' : 'ctag-bad' }}">{{ $charge ? 'OK' : 'Fault' }}</span>@else <span class="ctag ctag-ok">&mdash;</span>@endif</td>
              <td style="font-size:.65rem;color:var(--rb-text-3)">{{ $dev->notes_snapshot ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif

    {{-- ── DIAGNOSTIC / CASE DETAIL ── --}}
    @if($doc->case_detail && trim((string)$doc->case_detail) !== '')
    <div class="sec">
      <div class="sec-lbl">{{ $docType === 'estimate' ? 'Diagnostic Summary' : 'Work Performed' }}</div>
      <div class="diag-note">{{ $doc->case_detail }}</div>
    </div>
    @endif

    {{-- ── ITEMS TABLE ── --}}
    @if($items->isNotEmpty())
    <div class="sec">
      <div class="sec-lbl">{{ $docType === 'estimate' ? 'Estimated Items' : 'Items & Services' }}</div>
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:44%">Description</th>
            <th class="c" style="width:7%">Qty</th>
            <th class="r" style="width:15%">Unit</th>
            <th class="r" style="width:10%">Tax</th>
            <th class="r" style="width:24%">Amount</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $item)
            @php
              $unitCents = is_numeric($item->unit_price_amount_cents) ? (int)$item->unit_price_amount_cents : 0;
              $qty       = is_numeric($item->qty) ? (int)$item->qty : 1;
              $lineCents = $unitCents * $qty;
              $taxRate   = $item->tax?->rate ?? 0;
              $currency  = strtoupper($item->unit_price_currency ?? $currencyCode);
              $meta      = is_string($item->meta_json) ? json_decode($item->meta_json, true) : (is_array($item->meta_json) ? $item->meta_json : []);
            @endphp
            <tr>
              <td>
                <div class="it-name">{{ $item->name_snapshot }}</div>
                @if(!empty($meta['description']))<div class="it-sub">{{ $meta['description'] }}</div>@endif
                <span class="tag">{{ ucfirst($item->item_type ?? 'item') }}</span>
              </td>
              <td class="c">{{ $qty }}</td>
              <td class="r">{{ $currency }} {{ number_format($unitCents / 100, 2) }}</td>
              <td class="c">@if($taxRate > 0){{ number_format($taxRate, 0) }}%@else &mdash;@endif</td>
              <td class="r">{{ $currency }} {{ number_format($lineCents / 100, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>

      {{-- TOTALS --}}
      <div class="totals-wrap">
        <div class="totals">
          @php
            $subtotalCents = 0;
            $taxCents      = 0;
            foreach ($items as $item) {
              $u = is_numeric($item->unit_price_amount_cents) ? (int)$item->unit_price_amount_cents : 0;
              $q = is_numeric($item->qty) ? (int)$item->qty : 1;
              $line = $u * $q;
              $subtotalCents += $line;
              $rate = $item->tax?->rate ?? 0;
              $taxCents += (int)round($line * $rate / 100);
            }
            $grandCents = $subtotalCents + $taxCents;
            $cur = strtoupper($currencyCode);
          @endphp
          <div class="tr"><span>Subtotal (ex tax)</span><span>{{ $cur }} {{ number_format($subtotalCents / 100, 2) }}</span></div>
          @if($taxCents > 0)
          <div class="tr"><span>Tax</span><span>{{ $cur }} {{ number_format($taxCents / 100, 2) }}</span></div>
          @endif
          <hr class="tdiv">
          <div class="tgrand">
            <span>Total</span>
            <span>{{ $cur }} {{ number_format($grandCents / 100, 2) }}</span>
          </div>
        </div>
      </div>
    </div>
    @endif

    {{-- ── WARRANTY (job) / TERMS (estimate) ── --}}
    @if($docType === 'job')
      @if(!empty($warrantyLines))
      <div class="sec">
        <div class="sec-lbl">Warranty</div>
        <div class="warr-chips">
          @foreach($warrantyLines as $w)
            <span class="warr-chip">&#10003; {{ $w }}</span>
          @endforeach
        </div>
      </div>
      @endif
    @else
      <div class="sec terms">
        <div class="sec-lbl">Terms &amp; Conditions</div>
        <ol>
          <li>Valid <strong>14 days</strong> from issue. Prices may change after expiry.</li>
          <li>Part substitutions will be communicated before proceeding.</li>
          <li>Additional issues found during repair require approval; extra charges may apply.</li>
          <li>Parts warranty: 90 days. Labour warranty: 30 days.</li>
          <li>We are not liable for data loss — backup before drop-off.</li>
        </ol>
      </div>
    @endif

    {{-- ── NOTE ── --}}
    @if($docType === 'estimate')
      <div class="note">Parts pricing may vary if unavailable — we will contact you before proceeding with any substitution. This estimate is valid for 14 days.</div>
    @endif

    {{-- ── SIGNATURE BLOCK ── --}}
    @if($docType === 'estimate')
    <div class="sig-row">
      <div>
        <div class="sig-lbl">Customer Approval</div>
        <div class="sig-line"></div>
        <div class="sig-sub">Signature &amp; Date — authorises work to proceed</div>
      </div>
      <div>
        <div class="sig-lbl">Authorised &mdash; {{ $shopName }}</div>
        <div class="sig-line"></div>
        <div class="sig-sub">{{ $technician?->name ?? 'Technician' }}</div>
      </div>
    </div>
    @else
    <div class="sig-row">
      <div>
        <div class="sig-lbl">Customer Pickup Signature</div>
        <div class="sig-line"></div>
        <div class="sig-sub">Confirms device received in satisfactory condition</div>
      </div>
      <div>
        <div class="sig-lbl">Completed By &mdash; {{ $shopName }}</div>
        <div class="sig-line"></div>
        <div class="sig-sub">{{ $technician?->name ?? 'Technician' }}</div>
      </div>
    </div>
    @endif

  </div>{{-- .bd --}}

  {{-- ── FOOTER ── --}}
  <div class="foot">
    <div class="foot-text">
      @if($docType === 'estimate')
        This is an estimate only — not a tax invoice. Final pricing may differ. Tax invoice issued on completion.
      @else
        Thank you for choosing {{ $shopName }}.
      @endif
      @if($shopPhone) Contact us: {{ $shopPhone }}.@endif
    </div>
    <div class="foot-num">{{ $docNumber }}</div>
  </div>

</div>{{-- .paper --}}

@if(!($embedded ?? false))
<script>
  (function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') {
      window.addEventListener('load', function () { window.print(); });
    }
  })();
</script>
@endif
@if(!($embedded ?? false))
</body>
</html>
@endif
