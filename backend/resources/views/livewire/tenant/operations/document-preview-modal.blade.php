<div>
    @if($showModal)
    {{-- ── Backdrop overlay ── --}}
    <div x-data
         x-on:keydown.escape.window="$wire.close()"
         wire:click.self="close"
         style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(0,0,0,.55); backdrop-filter: blur(4px);">

        {{-- ── Modal panel ── --}}
        <div style="background: #fff; border-radius: 12px; width: 100%; max-width: 860px; height: 90vh; display: flex; flex-direction: column; box-shadow: 0 32px 64px rgba(0,0,0,.22); overflow: hidden; position: relative;">

            {{-- ── Header (fixed, never scrolls) ── --}}
            <div style="display: flex; align-items: center; justify-content: space-between; padding: .65rem 1.25rem; border-bottom: 1px solid #ededed; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: .6rem;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; background: #eff6ff; color: #063e70; font-size: .9rem;">
                        @if($docType === 'estimate') &#128196; @else &#128221; @endif
                    </span>
                    <div>
                        <div style="font-size: .88rem; font-weight: 700; color: #2c304d;">
                            {{ $docType === 'estimate' ? 'Estimate Preview' : 'Job Order Preview' }}
                        </div>
                        @if($isLoading)
                        <div style="font-size: .72rem; color: #9496ad; display:flex; align-items:center; gap:.3rem;">
                            <svg style="width:11px;height:11px;animation:spin 1s linear infinite" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" opacity=".75"/></svg>
                            <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
                            Loading…
                        </div>
                        @elseif($docNumber)
                        <div style="font-size: .72rem; color: #9496ad;">{{ $docNumber }}</div>
                        @endif
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: .4rem;">
                    @if($printUrl && $printUrl !== '#')
                    <a href="{{ $printUrl }}?autoprint=1" target="_blank"
                       style="display: inline-flex; align-items: center; gap: .25rem; font-size: .74rem; font-weight: 600; padding: .35rem .75rem; border-radius: 5px; border: 1px solid #ededed; color: #5f6380; text-decoration: none; background: #f7f7f7; cursor: pointer;">
                        &#9113; Print
                    </a>
                    @endif
                    @if($pdfUrl && $pdfUrl !== '#')
                    <a href="{{ $pdfUrl }}" target="_blank"
                       style="display: inline-flex; align-items: center; gap: .25rem; font-size: .74rem; font-weight: 600; padding: .35rem .75rem; border-radius: 5px; border: 1px solid #063e70; color: #fff; text-decoration: none; background: #063e70; cursor: pointer;">
                        &#11015; PDF
                    </a>
                    @endif
                    <button type="button" wire:click="close"
                            style="width: 30px; height: 30px; border: none; background: #f7f7f7; border-radius: 8px; color: #9496ad; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; margin-left: .25rem;">&times;</button>
                </div>
            </div>

            {{-- ── Modal Body — scrollable document preview ── --}}
            <div style="flex: 1; overflow-y: auto; background: #f0f1f3; padding: 1.25rem;">
                @if($isLoading)
                    {{-- Skeleton loader --}}
                    <div style="background:#fff; border-radius:8px; padding:2rem 2.5rem; max-width:794px; margin:0 auto;">
                        @php $sk = 'background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%); background-size:200% 100%; animation:rb-shimmer 1.4s infinite; border-radius:4px;'; @endphp
                        <style>@keyframes rb-shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}</style>
                        {{-- Header row --}}
                        <div style="display:flex;justify-content:space-between;margin-bottom:1.5rem;">
                            <div>
                                <div style="width:160px;height:18px;margin-bottom:.5rem;{{ $sk }}"></div>
                                <div style="width:110px;height:11px;{{ $sk }}"></div>
                            </div>
                            <div style="text-align:right">
                                <div style="width:90px;height:13px;margin-bottom:.4rem;margin-left:auto;{{ $sk }}"></div>
                                <div style="width:70px;height:22px;margin-left:auto;{{ $sk }}"></div>
                            </div>
                        </div>
                        {{-- Info strip --}}
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0;border:1px solid #ededed;border-radius:5px;margin-bottom:1.25rem;overflow:hidden;">
                            @foreach(range(1,3) as $col)
                            <div style="padding:.65rem .9rem;{{ $col < 3 ? 'border-right:1px solid #ededed;' : '' }}">
                                <div style="width:55px;height:9px;margin-bottom:.4rem;{{ $sk }}"></div>
                                <div style="width:100px;height:13px;margin-bottom:.25rem;{{ $sk }}"></div>
                                <div style="width:75px;height:11px;{{ $sk }}"></div>
                            </div>
                            @endforeach
                        </div>
                        {{-- Items table skeleton --}}
                        <div style="margin-bottom:1rem;">
                            <div style="width:110px;height:9px;margin-bottom:.65rem;{{ $sk }}"></div>
                            @foreach(range(1,4) as $row)
                            <div style="display:flex;gap:1rem;padding:.45rem 0;border-bottom:1px solid #f5f5f5;">
                                <div style="flex:3;height:12px;{{ $sk }}"></div>
                                <div style="flex:1;height:12px;{{ $sk }}"></div>
                                <div style="flex:1;height:12px;{{ $sk }}"></div>
                                <div style="flex:1.5;height:12px;{{ $sk }}"></div>
                            </div>
                            @endforeach
                        </div>
                        {{-- Totals --}}
                        <div style="display:flex;justify-content:flex-end;">
                            <div style="width:200px;">
                                <div style="height:12px;margin-bottom:.4rem;{{ $sk }}"></div>
                                <div style="height:12px;margin-bottom:.6rem;{{ $sk }}"></div>
                                <div style="height:18px;{{ $sk }}"></div>
                            </div>
                        </div>
                    </div>
                @elseif($doc)
                    @include('print.document-a4', [
                        'embedded'      => true,
                        'doc'           => $doc,
                        'docType'       => $docType,
                        'docNumber'     => $docNumber,
                        'statusLabel'   => $statusLabel,
                        'paymentLabel'  => $paymentLabel,
                        'shopName'      => $shopName,
                        'shopAddress'   => $shopAddress,
                        'shopPhone'     => $shopPhone,
                        'shopEmail'     => $shopEmail,
                        'currencyCode'  => $currencyCode,
                        'warrantyLines' => $warrantyLines,
                        'customer'      => $customer,
                        'technician'    => $technician,
                        'items'         => $items,
                        'devices'       => $devices,
                        'backUrl'       => '#',
                        'pdfUrl'        => $pdfUrl,
                    ])
                @else
                    <div style="text-align: center; padding: 3rem 1rem; color: #9496ad;">
                        <div style="font-size: 2rem; margin-bottom: .5rem;">&#128196;</div>
                        <div style="font-size: .88rem; font-weight: 600;">Document not found</div>
                        <div style="font-size: .78rem; margin-top: .25rem;">The requested record could not be loaded.</div>
                    </div>
                @endif
            </div>

        </div>
    </div>
    @endif
</div>
