{{-- Time Log Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        {{-- ── General ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Time Log Settings
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="disable_timelog" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Disable Time Log Completely</label>
                        <p class="st-option-desc">Hide the time log feature from all job views and interfaces</p>
                    </div>
                </div>

                <div class="st-fg" style="max-width:320px; margin-top:.75rem;">
                    <label for="default_tax_id">Default Tax for Hours</label>
                    <select id="default_tax_id" wire:model.defer="default_tax_id">
                        @foreach ($taxOptions as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="st-help">Tax rate applied to billable hours</p>
                </div>
            </div>
        </div>

        {{-- ── Status Inclusion ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg>
                    Enable Time Log for Statuses
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <p class="st-help" style="margin-bottom:.75rem;">Select which job statuses should have time logging enabled.</p>

                <div style="display:flex; flex-wrap:wrap; gap:.5rem;">
                    @foreach ($available_statuses as $status)
                        <label style="display:inline-flex; align-items:center; gap:.4rem; padding:6px 14px;
                                      border:1px solid var(--st-border,#e2e6ed); border-radius:999px;
                                      cursor:pointer; font-size:.85rem; transition:all .15s;
                                      {{ in_array($status['key'], $included_statuses) ? 'background:#eff6ff; border-color:#93c5fd; color:#1d4ed8;' : 'background:#fff;' }}">
                            <input type="checkbox" value="{{ $status['key'] }}" wire:model.defer="included_statuses"
                                   style="display:none;" />
                            <span>{{ $status['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Activities ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    Time Log Activities
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-fg">
                    <label for="activities">Activities</label>
                    <textarea id="activities" wire:model.defer="activities" rows="6"
                              placeholder="Diagnostics&#10;Screen Replacement&#10;Battery Replacement&#10;Software Update&#10;Data Recovery"></textarea>
                    <p class="st-help">Define activities for time log, one per line. These appear as selectable options when logging time.</p>
                </div>
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Time Log Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
