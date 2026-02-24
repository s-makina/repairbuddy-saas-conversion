<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $docType === 'estimate' ? 'Estimate' : 'Work Order' }} — {{ $docNumber }}</title>
<style>
/*
 * print/document.blade.php  |  Shared print template
 * Estimate-C design: 580 px, B&W, hairline accent, no gradients.
 * Works for both RepairBuddyEstimate ($docType='estimate')
 * and RepairBuddyJob ($docType='job').
 */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --ink: #111827; --ink2: #4b5563; --ink3: #9ca3af;
  --line: #d1d5db; --bg: #e5e7eb; --paper: #fff;
}
body {
  background: var(--bg);
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: var(--ink);
  font-size: 12.5px;
  line-height: 1.45;
  padding: 2.5rem 1rem 4rem;
  -webkit-font-smoothing: antialiased;
}

/* ── action bar (hidden on print) ── */
.bar {
  position: fixed; inset: 0 0 auto 0; z-index: 99;
  background: rgba(17,24,39,.95);
  display: flex; align-items: center; justify-content: space-between;
  padding: .5rem 1.4rem; gap: 1rem;
}
.bar-title { color: #e5e7eb; font-size: .78rem; font-weight: 600; display: flex; align-items: center; gap: .4rem; }
.bar-title i { color: #6b7280; font-style: normal; font-size: .82rem; }
.bar-actions { display: flex; gap: .35rem; }
.btn {
  display: inline-flex; align-items: center; gap: .3rem;
  font-size: .72rem; font-weight: 600; border-radius: 5px;
  border: 1px solid transparent; padding: .3rem .68rem;
  cursor: pointer; line-height: 1.4; text-decoration: none;
  background: none; transition: all .12s;
}
.btn-ghost  { background: rgba(255,255,255,.07); border-color: rgba(255,255,255,.15); color: #d1d5db; }
.btn-solid  { background: #111827; border-color: #374151; color: #fff; }
.btn-print  { background: #fff; border-color: #fff; color: #111827; }
.btn-pdf    { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }

/* ── paper ── */
.paper {
  max-width: 580px; margin: 3rem auto 0;
  background: var(--paper); border-radius: 8px;
  box-shadow: 0 2px 16px rgba(0,0,0,.10); overflow: hidden;
}

/* ── rule top ── */
.rule-top { height: 3px; background: var(--ink); }

/* ── header ── */
.hd {
  padding: 1.1rem 1.5rem .9rem;
  border-bottom: 1px solid var(--line);
  display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;
}
.shop-name { font-size: .9rem; font-weight: 800; color: var(--ink); letter-spacing: -.01em; }
.shop-meta { font-size: .68rem; color: var(--ink3); line-height: 1.65; margin-top: .1rem; }
.doc-right { text-align: right; }
.doc-type  { font-size: .58rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--ink3); }
.doc-num   { font-size: 1.25rem; font-weight: 900; color: var(--ink); line-height: 1.1; letter-spacing: -.02em; }
.doc-meta  { font-size: .68rem; color: var(--ink3); line-height: 1.65; }
.doc-status {
  display: inline-block; margin-top: .25rem;
  font-size: .6rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .05em; padding: .15rem .5rem;
  border: 1px solid var(--ink); border-radius: 3px; color: var(--ink);
}

/* ── info strip (3-col) ── */
.info-strip {
  display: grid; grid-template-columns: 1fr 1fr 1fr;
  gap: 0; border-bottom: 1px solid var(--line);
}
.is-cell { padding: .65rem 1rem; border-right: 1px solid var(--line); }
.is-cell:last-child { border-right: none; }
.is-label { font-size: .57rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--ink3); margin-bottom: .2rem; }
.is-val   { font-size: .8rem; color: var(--ink2); line-height: 1.55; }
.is-val strong { color: var(--ink); font-weight: 700; display: block; font-size: .82rem; }

/* ── body ── */
.bd { padding: 1rem 1.5rem; }
.sec { margin-bottom: .9rem; }
.sec-lbl { font-size: .57rem; font-weight: 800; text-transform: uppercase; letter-spacing: .09em; color: var(--ink3); margin-bottom: .4rem; border-bottom: 1px solid var(--line); padding-bottom: .25rem; }

/* ── chips ── */
.chips { display: flex; flex-wrap: wrap; gap: .28rem; }
.chip { font-size: .7rem; font-weight: 600; padding: .18rem .55rem; border: 1px solid var(--line); border-radius: 3px; color: var(--ink2); display: inline-flex; align-items: center; gap: .3rem; }
.chip-icon { color: var(--ink3); font-size: .75rem; }

/* ── device condition table (job only) ── */
.dtbl { width: 100%; border-collapse: collapse; }
.dtbl thead th { font-size: .57rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--ink3); padding: .28rem .45rem; border-bottom: 1.5px solid var(--ink); text-align: left; }
.dtbl tbody td { padding: .38rem .45rem; border-bottom: 1px solid var(--line); font-size: .78rem; vertical-align: middle; }
.dtbl tbody tr:last-child td { border-bottom: none; }
.ctag { font-size: .58rem; font-weight: 700; padding: .06rem .38rem; border-radius: 2px; border: 1px solid; display: inline-block; text-transform: uppercase; letter-spacing: .03em; }
.ctag-ok  { border-color: #d1d5db; color: var(--ink3); }
.ctag-bad { border-color: #111827; color: #111827; background: #f3f4f6; }

/* ── items table ── */
.tbl { width: 100%; border-collapse: collapse; }
.tbl thead th { font-size: .57rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--ink3); padding: .3rem .45rem; border-bottom: 1.5px solid var(--ink); text-align: left; }
.tbl thead th.r { text-align: right; }
.tbl thead th.c { text-align: center; }
.tbl tbody td { padding: .42rem .45rem; border-bottom: 1px solid var(--line); font-size: .8rem; vertical-align: middle; }
.tbl tbody tr:last-child td { border-bottom: none; }
.it-name { font-weight: 600; color: var(--ink); }
.it-sub  { font-size: .67rem; color: var(--ink3); }
.tag { font-size: .55rem; font-weight: 700; padding: .04rem .35rem; border: 1px solid var(--line); border-radius: 2px; color: var(--ink3); margin-top: .07rem; display: inline-block; text-transform: uppercase; letter-spacing: .04em; }
.tbl td.r { text-align: right; font-weight: 600; }
.tbl td.c { text-align: center; color: var(--ink3); }

/* ── totals ── */
.totals-wrap { display: flex; justify-content: flex-end; margin-top: .6rem; }
.totals { width: 215px; }
.tr { display: flex; justify-content: space-between; padding: .15rem 0; font-size: .78rem; color: var(--ink2); }
.tr span:last-child { font-weight: 600; color: var(--ink); }
.tdiv { border: none; border-top: 1px solid var(--line); margin: .22rem 0; }
.tgrand { display: flex; justify-content: space-between; padding: .3rem 0; border-top: 1.5px solid var(--ink); margin-top: .05rem; }
.tgrand span:first-child { font-size: .8rem; font-weight: 800; }
.tgrand span:last-child  { font-size: 1rem; font-weight: 900; color: var(--ink); }

/* ── work summary box (job only) ── */
.ws-box { padding: .6rem .75rem; border: 1px solid var(--line); border-radius: 3px; font-size: .78rem; color: var(--ink2); line-height: 1.6; white-space: pre-wrap; }
.ws-empty { color: var(--ink3); font-style: italic; }

/* ── warranty chips ── */
.warr-chips { display: flex; flex-wrap: wrap; gap: .28rem; }
.warr-chip { font-size: .68rem; font-weight: 600; padding: .18rem .55rem; border: 1px solid var(--line); border-radius: 3px; color: var(--ink2); display: inline-flex; align-items: center; gap: .3rem; }

/* ── note ── */
.note { font-size: .7rem; color: var(--ink3); line-height: 1.6; margin-top: .75rem; padding-top: .6rem; border-top: 1px solid var(--line); }

/* ── signature row ── */
.sig-row { margin-top: .85rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; padding: .7rem; border: 1px solid var(--line); border-radius: 4px; }
.sig-lbl  { font-size: .57rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--ink3); margin-bottom: .4rem; }
.sig-line { border-bottom: 1px solid var(--ink3); height: 26px; margin-bottom: .2rem; }
.sig-sub  { font-size: .62rem; color: var(--ink3); }

/* ── footer ── */
.foot { padding: .6rem 1.5rem; border-top: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; gap: .75rem; }
.foot-text { font-size: .62rem; color: var(--ink3); line-height: 1.55; max-width: 400px; }
.foot-num  { font-size: .62rem; font-weight: 700; color: var(--ink3); white-space: nowrap; }

@media print {
  @page { size: A5; margin: 8mm 10mm; }

  /* Force background graphics to print (Chrome/Edge/Safari) */
  *, *::before, *::after {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    color-adjust: exact !important;
  }

  body {
    background: #fff;
    padding: 0;
    font-size: 11px;
    margin: 0;
  }

  .bar { display: none !important; }

  .paper {
    margin: 0;
    box-shadow: none;
    border-radius: 0;
    max-width: 100%;
    border: none;
    overflow: visible;
  }

  /* Keep rule-top visible — use border as fallback */
  .rule-top {
    background: #111827 !important;
    height: 3px;
    border-top: 3px solid #111827;
  }

  /* Ensure table header rule prints */
  .tbl thead th,
  .dtbl thead th {
    border-bottom-color: #111827 !important;
  }

  /* Grand total top rule */
  .tgrand { border-top-color: #111827 !important; }

  /* Condition tag with fill */
  .ctag-bad { background: #f3f4f6 !important; }

  /* Page-break hints */
  .sig-row, .sec { break-inside: avoid; }
  .hd, .info-strip { break-inside: avoid; }
}
</style>
</head>
<body>
{{-- ════════════════ ACTION BAR ════════════════ --}}
<div class="bar">
  <div class="bar-title">
    <i>&#x2399;</i>
    @if($docType === 'estimate')
      Estimate &mdash; {{ $docNumber }}
    @else
      Work Order &mdash; {{ $docNumber }}
    @endif
  </div>
  <div class="bar-actions">
    @if($docType === 'estimate')
      <a href="{{ $backUrl }}" class="btn btn-ghost">&#8592; Back</a>
    @else
      <a href="{{ $backUrl }}" class="btn btn-ghost">&#8592; Back</a>
    @endif
    <a href="{{ $pdfUrl }}" class="btn btn-pdf" target="_blank">&#11015; Download PDF</a>
    <button class="btn btn-print" onclick="window.print()">&#x2399; Print</button>
  </div>
</div>

{{-- ════════════════ PAPER ════════════════ --}}
<div class="paper">
  <div class="rule-top"></div>

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
      <div class="doc-meta">
        @if($docType === 'estimate')
          Issued: {{ $doc->created_at?->format('M j, Y') ?? '—' }}<br>
          @if($doc->pickup_date) Est. Pickup: {{ \Carbon\Carbon::parse($doc->pickup_date)->format('M j, Y') }}@endif
        @else
          Opened: {{ $doc->opened_at?->format('M j, Y') ?? $doc->created_at?->format('M j, Y') ?? '—' }}<br>
          @if($doc->delivery_date) Est. Delivery: {{ \Carbon\Carbon::parse($doc->delivery_date)->format('M j, Y') }}@endif
        @endif
      </div>
      <div><span class="doc-status">{{ $statusLabel }}</span></div>
    </div>
  </div>

  {{-- ── INFO STRIP ── --}}
  <div class="info-strip">
    {{-- Bill To --}}
    <div class="is-cell">
      <div class="is-label">Bill To</div>
      <div class="is-val">
        <strong>{{ $customer?->name ?? '—' }}</strong>
        @if($customer?->email) {{ $customer->email }}<br>@endif
        @if($customer?->phone) {{ $customer->phone }}@endif
      </div>
    </div>
    {{-- Technician --}}
    <div class="is-cell">
      <div class="is-label">Technician</div>
      <div class="is-val">
        <strong>{{ $technician?->name ?? 'Unassigned' }}</strong>
        @if($doc->case_number) Case: {{ $doc->case_number }}@endif
      </div>
    </div>
    {{-- Priority / ETA --}}
    <div class="is-cell">
      <div class="is-label">
        @if($docType === 'job') Status / Payment @else Priority / ETA @endif
      </div>
      <div class="is-val">
        @if($docType === 'job')
          <strong>{{ $statusLabel }}</strong>
          {{ $paymentLabel }}
        @else
          <strong>{{ $doc->priority ?? 'Normal' }}</strong>
          @if($doc->delivery_date) ETA: {{ \Carbon\Carbon::parse($doc->delivery_date)->format('M j, Y') }}@endif
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
            <span class="chip-icon">&#9000;</span>
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
              $power   = $extras['power_on']   ?? null;
              $screen  = $extras['screen_ok']  ?? null;
              $charge  = $extras['charging_ok'] ?? null;
            @endphp
            <tr>
              <td>{{ $label }}@if($dev->serial_snapshot) <span style="color:var(--ink3);font-size:.67rem;display:block">S/N {{ $dev->serial_snapshot }}</span>@endif</td>
              <td>@if(!is_null($power))<span class="ctag {{ $power ? 'ctag-ok' : 'ctag-bad' }}">{{ $power ? 'OK' : 'Fault' }}</span>@else <span class="ctag ctag-ok">—</span>@endif</td>
              <td>@if(!is_null($screen))<span class="ctag {{ $screen ? 'ctag-ok' : 'ctag-bad' }}">{{ $screen ? 'OK' : 'Fault' }}</span>@else <span class="ctag ctag-ok">—</span>@endif</td>
              <td>@if(!is_null($charge))<span class="ctag {{ $charge ? 'ctag-ok' : 'ctag-bad' }}">{{ $charge ? 'OK' : 'Fault' }}</span>@else <span class="ctag ctag-ok">—</span>@endif</td>
              <td style="font-size:.7rem;color:var(--ink3)">{{ $dev->notes_snapshot ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif

    {{-- ── ITEMS TABLE ── --}}
    @if($items->isNotEmpty())
    <div class="sec">
      <div class="sec-lbl">{{ $docType === 'estimate' ? 'Estimated Items' : 'Items &amp; Services' }}</div>
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:47%">Description</th>
            <th class="c" style="width:7%">Qty</th>
            <th class="r" style="width:16%">Unit</th>
            <th class="r" style="width:10%">Tax</th>
            <th class="r" style="width:20%">Amount</th>
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
            @endphp
            <tr>
              <td>
                <div class="it-name">{{ $item->name_snapshot }}</div>
                @php $meta = is_string($item->meta_json) ? json_decode($item->meta_json, true) : (is_array($item->meta_json) ? $item->meta_json : []); @endphp
                @if(!empty($meta['description']))<div class="it-sub">{{ $meta['description'] }}</div>@endif
                <span class="tag">{{ ucfirst($item->item_type ?? 'item') }}</span>
              </td>
              <td class="c">{{ $qty }}</td>
              <td class="r">{{ $currency }} {{ number_format($unitCents / 100, 2) }}</td>
              <td class="r" style="color:var(--ink3);font-weight:400">
                @if($taxRate > 0){{ number_format($taxRate, 0) }}%@else —@endif
              </td>
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
            $cur        = strtoupper($currencyCode);
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

    {{-- ── WORK PERFORMED (job only) ── --}}
    @if($docType === 'job')
    <div class="sec">
      <div class="sec-lbl">Work Performed</div>
      @if($doc->case_detail && trim((string)$doc->case_detail) !== '')
        <div class="ws-box">{{ $doc->case_detail }}</div>
      @else
        <div class="ws-box ws-empty">No work summary provided.</div>
      @endif
    </div>
    @endif

    {{-- ── WARRANTY / NOTES (job) / NOTE (estimate) ── --}}
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
        Not a tax invoice. Tax invoice issued on job completion.
      @else
        Thank you for choosing {{ $shopName }}.
      @endif
      @if($shopPhone) Contact us: {{ $shopPhone }}.@endif
    </div>
    <div class="foot-num">{{ $docNumber }}</div>
  </div>

</div>{{-- .paper --}}

<script>
  // Auto-print when opened via ?autoprint=1 (used by the print button in the app)
  (function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') {
      window.addEventListener('load', function () { window.print(); });
    }
  })();
</script>
</body>
</html>
