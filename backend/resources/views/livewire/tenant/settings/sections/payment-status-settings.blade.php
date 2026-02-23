{{-- Payment Status Settings Section — Livewire Component --}}
<div>
    {{-- ── Payment Statuses Table ── --}}
    <div class="st-section" x-data="{ open: true }">
        <div class="st-section-header" @click="open = !open">
            <h3 class="st-section-title">
                <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                Payment Statuses
            </h3>
            <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </div>
        <div class="st-section-body" x-show="open" x-collapse>
            <div style="display: flex; justify-content: flex-end; margin-bottom: .75rem;">
                <button type="button" class="st-btn-save" wire:click="openAddModal" style="font-size: .78rem; padding: .45rem 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Payment Status
                </button>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: .82rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--st-border); text-align: left;">
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">ID</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Name</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Status</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paymentStatuses as $ps)
                            <tr style="border-bottom: 1px solid var(--st-border);">
                                <td style="padding: .55rem .75rem; color: var(--st-text-3);">{{ $ps['id'] ?? '-' }}</td>
                                <td style="padding: .55rem .75rem; font-weight: 600;">{{ $ps['name'] ?? '' }}</td>
                                <td style="padding: .55rem .75rem;">
                                    <span style="display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 600;
                                        {{ ($ps['is_active'] ?? true) ? 'background: var(--st-success-soft); color: #15803d;' : 'background: var(--st-danger-soft); color: #dc2626;' }}">
                                        {{ ($ps['is_active'] ?? true) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td style="padding: .55rem .75rem;">
                                    <button type="button" wire:click="openEditModal({{ $ps['id'] ?? 0 }})"
                                        style="background: none; border: 1px solid var(--st-border); border-radius: 6px; padding: .3rem .6rem; font-size: .75rem; color: var(--st-brand); cursor: pointer;">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 2rem; color: var(--st-text-3); font-size: .84rem;">
                                    No payment statuses configured yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Payment Methods ── --}}
    <div class="st-section" x-data="{ open: true }">
        <div class="st-section-header" @click="open = !open">
            <h3 class="st-section-title">
                <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Payment Methods
            </h3>
            <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </div>
        <div class="st-section-body" x-show="open" x-collapse>
            <div class="st-option-card">
                <div class="st-option-control">
                    <label class="st-toggle">
                        <input type="checkbox" wire:model.defer="method_cash" />
                        <span class="st-toggle-track"></span>
                    </label>
                </div>
                <div class="st-option-body">
                    <label class="st-option-title">Cash</label>
                    <p class="st-option-desc">Accept cash payments</p>
                </div>
            </div>

            <div class="st-option-card">
                <div class="st-option-control">
                    <label class="st-toggle">
                        <input type="checkbox" wire:model.defer="method_card" />
                        <span class="st-toggle-track"></span>
                    </label>
                </div>
                <div class="st-option-body">
                    <label class="st-option-title">Card</label>
                    <p class="st-option-desc">Accept card / terminal payments</p>
                </div>
            </div>

            <div class="st-option-card">
                <div class="st-option-control">
                    <label class="st-toggle">
                        <input type="checkbox" wire:model.defer="method_bank_transfer" />
                        <span class="st-toggle-track"></span>
                    </label>
                </div>
                <div class="st-option-body">
                    <label class="st-option-title">Bank Transfer</label>
                    <p class="st-option-desc">Accept bank transfer / wire payments</p>
                </div>
            </div>

            <div class="st-save-bar">
                <button type="button" class="st-btn-save" wire:click="saveMethods" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveMethods">Update Methods</span>
                    <span wire:loading wire:target="saveMethods" class="st-spinner"></span>
                    <span wire:loading wire:target="saveMethods">Saving…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Add/Edit Modal ── --}}
    @if ($showModal)
        <div style="position: fixed; inset: 0; z-index: 999; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,.45); backdrop-filter: blur(4px);" wire:click.self="closeModal">
            <div style="background: #fff; border-radius: var(--st-radius); box-shadow: 0 20px 60px rgba(0,0,0,.2); width: 100%; max-width: 420px; padding: 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                    <h3 style="font-size: .95rem; font-weight: 700; color: var(--st-text); margin: 0;">
                        {{ $editingId ? 'Edit Payment Status' : 'Add Payment Status' }}
                    </h3>
                    <button type="button" wire:click="closeModal" style="background: none; border: none; font-size: 1.2rem; color: var(--st-text-3); cursor: pointer;">&times;</button>
                </div>

                <div class="st-fg">
                    <label for="modal_name">Status Name</label>
                    <input type="text" id="modal_name" wire:model.defer="modal_name" placeholder="e.g. Paid" />
                </div>

                <div class="st-fg">
                    <label for="modal_active">Status</label>
                    <select id="modal_active" wire:model.defer="modal_active">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="st-save-bar">
                    <button type="button" class="st-btn-save" wire:click="savePaymentStatus">
                        {{ $editingId ? 'Update' : 'Add Status' }}
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
