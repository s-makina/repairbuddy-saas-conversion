{{-- Styling & Labels Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        {{-- ── Labels ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
                    Labels
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <p class="st-help" style="margin-bottom:1rem;">Customize the labels displayed on forms, invoices, and customer-facing pages.</p>

                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="delivery_date_label">Delivery Date Label</label>
                        <input type="text" id="delivery_date_label" wire:model.defer="delivery_date_label"
                               placeholder="e.g. Delivery Date" />
                    </div>
                    <div class="st-fg">
                        <label for="pickup_date_label">Pickup Date Label</label>
                        <input type="text" id="pickup_date_label" wire:model.defer="pickup_date_label"
                               placeholder="e.g. Pickup Date" />
                    </div>
                    <div class="st-fg">
                        <label for="nextservice_date_label">Next Service Date Label</label>
                        <input type="text" id="nextservice_date_label" wire:model.defer="nextservice_date_label"
                               placeholder="e.g. Next Service Date" />
                    </div>
                    <div class="st-fg">
                        <label for="casenumber_label">Case Number Label</label>
                        <input type="text" id="casenumber_label" wire:model.defer="casenumber_label"
                               placeholder="e.g. Case Number" />
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Styling / Colors ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008Z"/></svg>
                    Styling
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <p class="st-help" style="margin-bottom:1rem;">Set your brand colors used across customer-facing pages and documents.</p>

                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="primary_color">Primary Color</label>
                        <div style="display:flex; align-items:center; gap:.75rem;">
                            <input type="color" id="primary_color" wire:model.defer="primary_color"
                                   style="width:52px; height:42px; padding:4px; border:1px solid var(--st-border,#e2e6ed); border-radius:6px; cursor:pointer;" />
                            <input type="text" wire:model.defer="primary_color"
                                   style="max-width:120px; font-family:monospace; font-size:.88rem;" maxlength="7"
                                   placeholder="#063e70" />
                        </div>
                        <p class="st-help">Used for headers, navigation, and buttons</p>
                    </div>
                    <div class="st-fg">
                        <label for="secondary_color">Secondary Color</label>
                        <div style="display:flex; align-items:center; gap:.75rem;">
                            <input type="color" id="secondary_color" wire:model.defer="secondary_color"
                                   style="width:52px; height:42px; padding:4px; border:1px solid var(--st-border,#e2e6ed); border-radius:6px; cursor:pointer;" />
                            <input type="text" wire:model.defer="secondary_color"
                                   style="max-width:120px; font-family:monospace; font-size:.88rem;" maxlength="7"
                                   placeholder="#fd6742" />
                        </div>
                        <p class="st-help">Used for accents and highlights</p>
                    </div>
                </div>

                {{-- Live preview --}}
                <div style="margin-top:1rem; padding:1rem; border:1px solid var(--st-border,#e2e6ed); border-radius:8px; background:#fafbfc;">
                    <p style="font-size:.82rem; color:var(--st-text-muted,#8590a2); margin-bottom:.5rem;">Preview</p>
                    <div style="display:flex; gap:1rem; align-items:center;">
                        <div style="width:60px; height:36px; border-radius:6px;" x-data
                             :style="'background:' + $wire.primary_color"></div>
                        <span style="font-size:.85rem; color:var(--st-text,#1e293b);">Primary</span>
                        <div style="width:60px; height:36px; border-radius:6px;" x-data
                             :style="'background:' + $wire.secondary_color"></div>
                        <span style="font-size:.85rem; color:var(--st-text,#1e293b);">Secondary</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Styling Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
