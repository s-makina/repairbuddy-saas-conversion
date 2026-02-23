{{-- Tax Settings Section — Livewire Component --}}
<div>
    {{-- ── Tax Table ── --}}
    <div class="st-section" x-data="{ open: true }">
        <div class="st-section-header" @click="open = !open">
            <h3 class="st-section-title">
                <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 14.25 6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185ZM9.75 9h.008v.008H9.75V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 4.5h.008v.008h-.008V13.5Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                Manage Taxes
            </h3>
            <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </div>
        <div class="st-section-body" x-show="open" x-collapse>
            <div style="display: flex; justify-content: flex-end; margin-bottom: .75rem;">
                <button type="button" class="st-btn-save" wire:click="openAddModal" style="font-size: .78rem; padding: .45rem 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Tax
                </button>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: .82rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--st-border); text-align: left;">
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">ID</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Name</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Rate</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Status</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Default</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($taxes as $tax)
                            <tr style="border-bottom: 1px solid var(--st-border);">
                                <td style="padding: .55rem .75rem; color: var(--st-text-3);">{{ $tax['id'] ?? '-' }}</td>
                                <td style="padding: .55rem .75rem; font-weight: 600;">{{ $tax['name'] ?? '' }}</td>
                                <td style="padding: .55rem .75rem;">{{ $tax['rate'] ?? '0' }}%</td>
                                <td style="padding: .55rem .75rem;">
                                    <span style="display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 600;
                                        {{ ($tax['is_active'] ?? true) ? 'background: var(--st-success-soft); color: #15803d;' : 'background: var(--st-danger-soft); color: #dc2626;' }}">
                                        {{ ($tax['is_active'] ?? true) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td style="padding: .55rem .75rem;">
                                    @if ($tax['is_default'] ?? false)
                                        <span style="display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 600; background: var(--st-brand-soft); color: var(--st-brand);">Default</span>
                                    @else
                                        <button type="button" wire:click="setDefault({{ $tax['id'] ?? 0 }})"
                                            style="background: none; border: 1px dashed var(--st-border); border-radius: 6px; padding: .2rem .5rem; font-size: .7rem; color: var(--st-text-3); cursor: pointer;">
                                            Set Default
                                        </button>
                                    @endif
                                </td>
                                <td style="padding: .55rem .75rem;">
                                    <button type="button" wire:click="openEditModal({{ $tax['id'] ?? 0 }})"
                                        style="background: none; border: 1px solid var(--st-border); border-radius: 6px; padding: .3rem .6rem; font-size: .75rem; color: var(--st-brand); cursor: pointer;">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--st-text-3); font-size: .84rem;">
                                    No taxes configured yet. Click "Add Tax" to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Tax Settings ── --}}
    <div class="st-section" x-data="{ open: true }">
        <div class="st-section-header" @click="open = !open">
            <h3 class="st-section-title">
                <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
                Tax Configuration
            </h3>
            <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </div>
        <div class="st-section-body" x-show="open" x-collapse>
            <div class="st-option-card">
                <div class="st-option-control">
                    <label class="st-toggle">
                        <input type="checkbox" wire:model.defer="enable_taxes" />
                        <span class="st-toggle-track"></span>
                    </label>
                </div>
                <div class="st-option-body">
                    <label class="st-option-title">Enable Taxes</label>
                    <p class="st-option-desc">Apply tax calculations to jobs and invoices</p>
                </div>
            </div>

            <div class="st-grid st-grid-2" style="margin-top: .75rem;">
                <div class="st-fg">
                    <label for="default_tax">Default Tax</label>
                    <select id="default_tax" wire:model.defer="default_tax">
                        <option value="">— None —</option>
                        @foreach ($taxes as $tax)
                            <option value="{{ $tax['id'] ?? '' }}">{{ $tax['name'] ?? '' }} ({{ $tax['rate'] ?? '0' }}%)</option>
                        @endforeach
                    </select>
                    <p class="st-help">Default tax applied to new jobs</p>
                </div>
                <div class="st-fg">
                    <label for="prices_inclusive_exclusive">Invoice Amounts</label>
                    <select id="prices_inclusive_exclusive" wire:model.defer="prices_inclusive_exclusive">
                        @foreach ($taxInclusiveOptions as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="st-help">Whether prices include or exclude tax</p>
                </div>
            </div>

            <div class="st-save-bar">
                <button type="button" class="st-btn-save" wire:click="saveSettings" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveSettings">Save Tax Settings</span>
                    <span wire:loading wire:target="saveSettings" class="st-spinner"></span>
                    <span wire:loading wire:target="saveSettings">Saving…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Add/Edit Tax Modal ── --}}
    @if ($showModal)
        <div style="position: fixed; inset: 0; z-index: 999; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,.45); backdrop-filter: blur(4px);" wire:click.self="closeModal">
            <div style="background: #fff; border-radius: var(--st-radius); box-shadow: 0 20px 60px rgba(0,0,0,.2); width: 100%; max-width: 460px; padding: 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                    <h3 style="font-size: .95rem; font-weight: 700; color: var(--st-text); margin: 0;">
                        {{ $editingId ? 'Edit Tax' : 'Add New Tax' }}
                    </h3>
                    <button type="button" wire:click="closeModal" style="background: none; border: none; font-size: 1.2rem; color: var(--st-text-3); cursor: pointer;">&times;</button>
                </div>

                <div class="st-fg">
                    <label for="modal_tax_name">Tax Name</label>
                    <input type="text" id="modal_tax_name" wire:model.defer="modal_tax_name" placeholder="e.g. VAT" />
                </div>

                <div class="st-fg">
                    <label for="modal_tax_description">Description</label>
                    <input type="text" id="modal_tax_description" wire:model.defer="modal_tax_description" placeholder="Tax description" />
                </div>

                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="modal_tax_rate">Rate (%)</label>
                        <input type="number" id="modal_tax_rate" wire:model.defer="modal_tax_rate"
                               min="0" max="100" step="0.001" placeholder="0" />
                    </div>
                    <div class="st-fg">
                        <label for="modal_tax_status">Status</label>
                        <select id="modal_tax_status" wire:model.defer="modal_tax_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="st-save-bar">
                    <button type="button" class="st-btn-save" wire:click="saveTax">
                        {{ $editingId ? 'Update Tax' : 'Add Tax' }}
                    </button>
                    <button type="button" wire:click="closeModal"
                        style="padding: .55rem 1rem; font-size: .82rem; font-weight: 500; background: none; border: 1px solid var(--st-border); border-radius: var(--st-radius-sm); cursor: pointer; color: var(--st-text-2);">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
