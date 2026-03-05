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
 * Refined Printable A4 document — premium aesthetic.
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
    --rb-radius:  6px;
    --rb-green:   #16a34a;
}
body {
    background: #f0f1f3;
    font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
    color: var(--rb-text); font-size: 12px; line-height: 1.4;
    -webkit-font-smoothing: antialiased;
    padding: @if(!($embedded ?? false)) 3rem 1rem 3rem @else 0 @endif;
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
    max-width: 800px;
    margin: @if(!($embedded ?? false)) 0 auto @else 0 @endif;
    background: var(--rb-surface);
    border-radius: @if(!($embedded ?? false)) 8px @else 0 @endif;
    box-shadow: @if(!($embedded ?? false)) 0 1px 15px 1px rgba(52,40,104,.08) @else none @endif;
    overflow: hidden;
    padding: 30px 40px;
}

/* ── Header ── */
.hd {
    padding-bottom: 20px;
    border-bottom: 2px solid var(--rb-orange);
    display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;
    margin-bottom: 25px;
}
.shop-name { font-size: 1.4rem; font-weight: 800; color: var(--rb-blue); }
.shop-meta { font-size: .75rem; color: var(--rb-text-2); line-height: 1.5; margin-top: 4px; }
.doc-right { text-align: right; flex-shrink: 0; }
.doc-type  { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--rb-text-2); }
.doc-num   { font-size: 1.6rem; font-weight: 900; color: var(--rb-blue); line-height: 1.1; margin-top: 2px; }
.doc-dates { font-size: .75rem; color: var(--rb-text-2); line-height: 1.5; margin-top: 5px; }

.badge-pill {
    display: inline-flex; align-items: center; gap: .2rem;
    margin-top: .4rem; font-size: .65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .04em; padding: .15rem .5rem; border-radius: 999px;
    border: 1px solid;
}
.badge-pending  { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.badge-approved { background: #dcfce7; color: #14532d; border-color: #bbf7d0; }
.badge-open     { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
.badge-closed   { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
.badge-paid     { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }

/* ── Info strip ── */
.info-strip { 
    display: grid; grid-template-columns: 1fr 1fr 1fr; 
    border: 1px solid var(--rb-border); 
    border-radius: var(--rb-radius); 
    margin-bottom: 25px; 
    overflow: hidden;
}
.is-cell { padding: 12px 15px; border-right: 1px solid var(--rb-border); }
.is-cell:last-child { border-right: none; }
.is-lbl { font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--rb-text-3); margin-bottom: 4px; }
.is-val { font-size: .85rem; color: var(--rb-text); line-height: 1.4; font-weight: 600; }
.is-val small { font-weight: 400; color: var(--rb-text-2); display: block; font-size: .75rem; }

/* ── Body ── */
.sec { margin-bottom: 20px; }
.sec-lbl {
    font-size: .65rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .08em; color: var(--rb-blue); margin-bottom: 8px;
    padding-bottom: 4px; border-bottom: 1px solid var(--rb-border);
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
.dtbl thead th { font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--rb-text-2); padding: 6px 10px; border-bottom: 1.5px solid var(--rb-text); text-align: left; }
.dtbl tbody td { padding: 8px 10px; border-bottom: 1px solid var(--rb-border); font-size: .8rem; vertical-align: middle; }
.dtbl tbody tr:last-child td { border-bottom: none; }
.ctag { font-size: .6rem; font-weight: 700; padding: 2px 8px; border-radius: 99px; border: 1px solid; display: inline-block; text-transform: uppercase; }
.ctag-ok  { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
.ctag-bad { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

/* Items table */
.tbl { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
.tbl thead th {
    font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    color: var(--rb-text-2); padding: 8px 10px;
    border-bottom: 1.5px solid var(--rb-text); text-align: left;
}
.tbl thead th.r { text-align: right; }
.tbl thead th.c { text-align: center; }
.tbl tbody td {
    padding: 10px; border-bottom: 1px solid var(--rb-border);
    font-size: .85rem; vertical-align: top;
}
.tbl tbody td.r { text-align: right; }
.tbl tbody td.c { text-align: center; }
.tbl tbody tr:last-child td { border-bottom: none; }
.it-name { font-weight: 700; color: var(--rb-text); }
.it-sub  { font-size: .7rem; color: var(--rb-text-3); margin-top: 2px; }
.it-tag { display: inline-block; font-size: .55rem; font-weight: 700; padding: 1px 5px; border-radius: 3px; border: 1px solid var(--rb-border); color: var(--rb-text-2); margin-top: 4px; text-transform: uppercase; }

/* Totals */
.totals-wrap { display: flex; justify-content: flex-end; }
.totals { width: 220px; }
.tr { display: flex; justify-content: space-between; padding: 4px 0; font-size: .8rem; color: var(--rb-text-2); }
.tr span:last-child { font-weight: 700; color: var(--rb-text); }
.tdiv { border: none; border-top: 1px solid var(--rb-border); margin: .15rem 0; }
.tgrand {
    display: flex; justify-content: space-between; padding: 8px 0;
    border-top: 2px solid var(--rb-text); margin-top: 4px;
}
.tgrand span:first-child { font-size: .9rem; font-weight: 800; }
.tgrand span:last-child  { font-size: 1.1rem; font-weight: 900; color: var(--rb-blue); }

/* Boxes */
.note-box {
    font-size: .8rem; color: var(--rb-text-2); line-height: 1.5;
    padding: 12px 15px; background: var(--rb-muted); border-radius: var(--rb-radius);
    white-space: pre-wrap; border: 1px solid var(--rb-border);
}

.warranty-box {
    padding: 12px 15px; border: 1px dashed var(--rb-text-3); border-radius: var(--rb-radius);
}
.w-item { font-size: .75rem; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
.w-item strong { color: var(--rb-green); font-weight: 900; }

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
    margin-top: 25px; display: grid; grid-template-columns: 1fr 1fr;
    gap: 30px;
}
.sig-box { border-top: 1px solid var(--rb-text); padding-top: 6px; height: 60px; display: flex; flex-direction: column; justify-content: space-between; }
.sig-lbl { font-size: .6rem; font-weight: 700; text-transform: uppercase; color: var(--rb-text-2); }
.sig-sub { font-size: .65rem; color: var(--rb-text-3); }

/* Footer */
.foot {
    margin-top: 30px; padding-top: 15px; border-top: 1px solid var(--rb-border);
    display: flex; justify-content: space-between; align-items: center;
}
.foot-text { font-size: .65rem; color: var(--rb-text-3); line-height: 1.5; max-width: 500px; }
.foot-num  { font-size: .75rem; font-weight: 800; color: var(--rb-text-3); }

/* ── Print ── */
@media print {
    @page { size: A4; margin: 10mm; }
    body { background: white; padding: 0; }
    .paper { box-shadow: none; border: none; max-width: 100%; padding: 0; }
    .bar { display: none !important; }
    * { -webkit-print-color-adjust: exact !important; color-adjust: exact !important; }
}

@media (max-width: 640px) {
    .info-strip { grid-template-columns: 1fr; }
    .is-cell { border-right: none; border-bottom: 1px solid var(--rb-border); }
    .sig-row { grid-template-columns: 1fr; gap: 20px; }
    .hd { flex-direction: column; text-align: center; }
    .doc-right { text-align: center; margin-top: 15px; }
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
    {{ $docType === 'estimate' ? 'Estimate' : 'Work Order' }} &mdash; {{ $docNumber }}
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
      <div class="doc-type">{{ $docType === 'estimate' ? 'Estimate / Quote' : 'Work Order / Job Card' }}</div>
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
        $sl = strtolower($statusLabel ?? '');
        $slBadge = 'badge-open';
        if (in_array($sl, ['pending', 'draft', 'sent'])) $slBadge = 'badge-pending';
        elseif (in_array($sl, ['approved', 'completed', 'closed'])) $slBadge = 'badge-approved';
        elseif (in_array($sl, ['rejected', 'cancelled'])) $slBadge = 'badge-closed';
      @endphp
      <span class="badge-pill {{ $slBadge }}">{{ $statusLabel }}</span>
      @if($docType === 'job' && !empty($paymentLabel))
        @php $pStatus = strtolower($paymentLabel); @endphp
        <span class="badge-pill {{ $pStatus === 'paid' ? 'badge-paid' : 'badge-pending' }}">{{ $paymentLabel }}</span>
      @endif
    </div>
  </div>

  {{-- ── INFO STRIP ── --}}
  <div class="info-strip">
    {{-- Prepared For / Bill To --}}
    <div class="is-cell">
      <div class="is-lbl">{{ $docType === 'estimate' ? 'Prepared For' : 'Bill To' }}</div>
      <div class="is-val">
        {{ $customer?->name ?? '—' }}
        <small>{{ $customer?->email ?? '' }}</small>
        <small>{{ $customer?->phone ?? '' }}</small>
      </div>
    </div>
    {{-- Reference --}}
    <div class="is-cell">
      <div class="is-lbl">{{ $docType === 'estimate' ? 'Reference' : 'Job Reference' }}</div>
      <div class="is-val">
        #{{ $docNumber }}
        @if($doc->case_number)<small>Case: {{ $doc->case_number }}</small>@endif
        @if($docType === 'job' && ($doc->priority ?? null))<small style="color:var(--rb-orange);font-weight:800;">PRIORITY: {{ strtoupper($doc->priority) }}</small>@endif
      </div>
    </div>
    {{-- Technician --}}
    <div class="is-cell">
      <div class="is-lbl">{{ $docType === 'estimate' ? 'Prepared By' : 'Technician' }}</div>
      <div class="is-val">
        {{ $technician?->name ?? 'Unassigned' }}
        <small>{{ $docType === 'job' ? 'Assigned Tech' : 'Account Manager' }}</small>
      </div>
    </div>
  </div>

  {{-- ════ BODY ════ --}}
  <div class="bd">
    {{-- ── DEVICES  ── --}}
    @if($devices->isNotEmpty())
    @php
      $fields = $deviceFields ?? [];
      $fieldCount = count($fields);
      $deviceWidth = $fieldCount > 0 ? max(10, (int) (60 / $fieldCount)) : 15;
    @endphp
    <div class="sec">
      <div class="sec-lbl">Devices</div>
      <table class="dtbl">
        <thead>
          <tr>
            <th style="width:40%">Device / Model</th>
            @foreach($fields as $field)
              <th style="width:{{ $deviceWidth }}%">{{ $field['label'] }}</th>
            @endforeach
            <th style="width:{{ max(10, 60 - ($deviceWidth * $fieldCount)) }}%">Serial / SN</th>
          </tr>
        </thead>
        <tbody>
          @foreach($devices as $dev)
            @php
              $label  = $dev->label_snapshot ?? $dev->customerDevice?->device?->name ?? 'Device';
              $extras = is_string($dev->extra_fields_snapshot_json) ? json_decode($dev->extra_fields_snapshot_json, true) : (is_array($dev->extra_fields_snapshot_json) ? $dev->extra_fields_snapshot_json : []);
            @endphp
            <tr>
              <td>
                <div style="font-weight:700;">{{ $label }}</div>
                @if($dev->notes_snapshot)<div style="font-size:10px;color:var(--rb-text-3);">{{ $dev->notes_snapshot }}</div>@endif
              </td>
              @foreach($fields as $field)
                @php
                  $fKey = $field['key'];
                  $fVal = $extras[$fKey] ?? null;
                @endphp
                <td>
                  @if(is_bool($fVal))
                    <span class="ctag {{ $fVal ? 'ctag-ok' : 'ctag-bad' }}">{{ $fVal ? 'OK' : 'Fault' }}</span>
                  @elseif(!is_null($fVal))
                    {{ $fVal }}
                  @else
                    &mdash;
                  @endif
                </td>
              @endforeach
              <td><small style="font-family:monospace;">{{ $dev->serial_snapshot ?? '—' }}</small></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif

    {{-- ── PROBLEM / WORK SUMMARY ── --}}
    @if($doc->case_detail && trim((string)$doc->case_detail) !== '')
    <div class="sec">
      <div class="sec-lbl">{{ $docType === 'estimate' ? 'Diagnostic Summary' : 'Work Description & Reported Issues' }}</div>
      <div class="note-box">{{ $doc->case_detail }}</div>
    </div>
    @endif

    {{-- ── ITEMS ── --}}
    @if($items->isNotEmpty())
    <div class="sec">
      <div class="sec-lbl">{{ $docType === 'estimate' ? 'Estimated Items' : 'Items & Services' }}</div>
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:45%">Description</th>
            <th style="width:10%">Qty</th>
            <th class="r" style="width:20%">Price</th>
            <th class="r" style="width:25%">Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $item)
            @php
              $unitCents = is_numeric($item->unit_price_amount_cents) ? (int)$item->unit_price_amount_cents : 0;
              $qty       = is_numeric($item->qty) ? (int)$item->qty : 1;
              $lineCents = $unitCents * $qty;
              $currency  = strtoupper($item->unit_price_currency ?? $currencyCode);
              $meta      = is_string($item->meta_json) ? json_decode($item->meta_json, true) : (is_array($item->meta_json) ? $item->meta_json : []);
            @endphp
            <tr>
              <td>
                <div class="it-name">{{ $item->name_snapshot }}</div>
                @if(!empty($meta['description']))<div class="it-sub">{{ $meta['description'] }}</div>@endif
                <span class="it-tag">{{ ucfirst($item->item_type ?? 'service') }}</span>
              </td>
              <td>{{ $qty }}</td>
              <td class="r">{{ $currency }} {{ number_format($unitCents / 100, 2) }}</td>
              <td class="r">{{ $currency }} {{ number_format($lineCents / 100, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>

      <div class="totals-wrap">
        <div class="totals">
          @php
            $subTotal = 0; $taxTotal = 0;
            foreach ($items as $it) {
                $line = ((int)$it->unit_price_amount_cents) * ((int)$it->qty);
                $subTotal += $line;
                $rate = $it->tax?->rate ?? 0;
                $taxTotal += (int)round($line * $rate / 100);
            }
            $grand = $subTotal + $taxTotal;
            $cur = strtoupper($currencyCode);
          @endphp
          <div class="tr"><span>Subtotal</span><span>{{ $cur }} {{ number_format($subTotal/100, 2) }}</span></div>
          @if($taxTotal > 0)
          <div class="tr"><span>Tax</span><span>{{ $cur }} {{ number_format($taxTotal/100, 2) }}</span></div>
          @endif
          <div class="tgrand">
            <span>Total</span>
            <span>{{ $cur }} {{ number_format($grand/100, 2) }}</span>
          </div>
        </div>
      </div>
    </div>
    @endif

    {{-- ── WARRANTY & SIGNATURE ── --}}
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 5px;">
        <div>
            @if($docType === 'job')
                <div class="sec-lbl">Warranty Coverage</div>
                <div class="warranty-box">
                    @forelse($warrantyLines as $w)
                        <div class="w-item"><strong>✓</strong> {{ $w }}</div>
                    @empty
                        <div class="w-item" style="color:var(--rb-text-3);font-style:italic;">No warranty specified</div>
                    @endforelse
                </div>
            @else
                <div class="sec-lbl">Terms & Conditions</div>
                <div style="font-size: 10px; line-height: 1.4; color: var(--rb-text-2);">
                    1. Valid for 14 days.<br>
                    2. Authorization required for extra work.<br>
                    3. Parts warranty: 90 days. Labor: 30 days.<br>
                    4. Not liable for data loss - please backup.
                </div>
            @endif
        </div>
        <div style="display:flex; flex-direction:column; justify-content: flex-end;">
            <div class="sig-box">
                <div class="sig-lbl">{{ $docType === 'estimate' ? 'Technician Signature' : 'Technician Signature' }}</div>
                <div class="sig-sub">{{ $technician?->name ?? 'Repair Shop' }}</div>
            </div>
            <div class="sig-box" style="margin-top: 20px;">
                <div class="sig-lbl">Customer Signature</div>
                <div class="sig-sub">{{ $docType === 'estimate' ? 'Authorized to proceed' : 'Device received' }}</div>
            </div>
        </div>
    </div>

  </div>

  <div class="foot">
    <div class="foot-text">
      {{ $docType === 'estimate' ? 'This is a quote only. Final work order issued on approval.' : 'Thank you for your business. Visit us again for premium tech support.' }}
      @if($shopPhone)<br>Support: {{ $shopPhone }}@endif
    </div>
    <div class="foot-num">{{ $docNumber }}</div>
  </div>

</div>

@if(!($embedded ?? false))
<script>
  (function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') {
      window.addEventListener('load', function () { window.print(); });
    }
  })();
</script>
</body>
</html>
@endif
