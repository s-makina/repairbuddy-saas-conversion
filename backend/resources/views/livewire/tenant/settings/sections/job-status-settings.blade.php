{{-- Job Status Settings Section — Livewire Component --}}
<div>
    {{-- ── Status Table ── --}}
    <div class="st-section" x-data="{ open: true }">
        <div class="st-section-header" @click="open = !open">
            <h3 class="st-section-title">
                <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>
                Job Statuses
            </h3>
            <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </div>
        <div class="st-section-body" x-show="open" x-collapse>
            <div style="display: flex; justify-content: flex-end; margin-bottom: .75rem;">
                <button type="button" class="st-btn-save" wire:click="openAddModal" style="font-size: .78rem; padding: .45rem 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Status
                </button>
            </div>

            {{-- Status table --}}
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: .82rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--st-border); text-align: left;">
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">ID</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Name</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Description</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Invoice Label</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Status</th>
                            <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($statuses as $status)
                            <tr style="border-bottom: 1px solid var(--st-border);">
                                <td style="padding: .55rem .75rem; color: var(--st-text-3);">{{ $status['id'] ?? '-' }}</td>
                                <td style="padding: .55rem .75rem; font-weight: 600;">{{ $status['name'] ?? '' }}</td>
                                <td style="padding: .55rem .75rem; color: var(--st-text-2);">{{ $status['description'] ?? '' }}</td>
                                <td style="padding: .55rem .75rem;">{{ $status['invoice_label'] ?? 'Invoice' }}</td>
                                <td style="padding: .55rem .75rem;">
                                    <span style="display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 600;
                                        {{ ($status['is_active'] ?? true) ? 'background: var(--st-success-soft); color: #15803d;' : 'background: var(--st-danger-soft); color: #dc2626;' }}">
                                        {{ ($status['is_active'] ?? true) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td style="padding: .55rem .75rem; white-space: nowrap;">
                                    <button type="button" wire:click="openEditModal({{ $status['id'] ?? 0 }})"
                                        style="background: none; border: 1px solid var(--st-border); border-radius: 6px; padding: .3rem .6rem; font-size: .75rem; color: var(--st-brand); cursor: pointer;">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="deleteStatus({{ $status['id'] ?? 0 }})"
                                        wire:confirm="Are you sure you want to delete this status?"
                                        style="background: none; border: 1px solid var(--st-danger-soft, #fee2e2); border-radius: 6px; padding: .3rem .6rem; font-size: .75rem; color: #dc2626; cursor: pointer; margin-left: .35rem;">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--st-text-3); font-size: .84rem;">
                                    No job statuses configured yet. Click "Add Status" to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Status Settings ── --}}
    <div class="st-section" x-data="{ open: true }">
        <div class="st-section-header" @click="open = !open">
            <h3 class="st-section-title">
                <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
                Status Settings
            </h3>
            <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </div>
        <div class="st-section-body" x-show="open" x-collapse>
            <div class="st-grid st-grid-2">
                <div class="st-fg">
                    <label for="status_considered_completed">Job Status Considered Completed</label>
                    <select id="status_considered_completed" wire:model.defer="status_considered_completed">
                        <option value="">— Select —</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status['id'] ?? '' }}">{{ $status['name'] ?? '' }}</option>
                        @endforeach
                    </select>
                    <p class="st-help">When a job reaches this status, it's considered completed</p>
                </div>
                <div class="st-fg">
                    <label for="status_considered_cancelled">Job Status Considered Cancelled</label>
                    <select id="status_considered_cancelled" wire:model.defer="status_considered_cancelled">
                        <option value="">— Select —</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status['id'] ?? '' }}">{{ $status['name'] ?? '' }}</option>
                        @endforeach
                    </select>
                    <p class="st-help">When a job reaches this status, it's considered cancelled</p>
                </div>
            </div>

            <div class="st-save-bar">
                <button type="button" class="st-btn-save" wire:click="saveSettings" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveSettings">Update Settings</span>
                    <span wire:loading wire:target="saveSettings" class="st-spinner"></span>
                    <span wire:loading wire:target="saveSettings">Saving…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Add/Edit Modal ── --}}
    @if ($showAddModal || $showEditModal)
        <div style="position: fixed; inset: 0; z-index: 999; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,.45); backdrop-filter: blur(4px);" wire:click.self="closeModal">
            <div style="background: #fff; border-radius: var(--st-radius); box-shadow: 0 20px 60px rgba(0,0,0,.2); width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; padding: 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                    <h3 style="font-size: .95rem; font-weight: 700; color: var(--st-text); margin: 0;">
                        {{ $showAddModal ? 'Add New Status' : 'Edit Status' }}
                    </h3>
                    <button type="button" wire:click="closeModal" style="background: none; border: none; font-size: 1.2rem; color: var(--st-text-3); cursor: pointer;">&times;</button>
                </div>

                <div class="st-fg">
                    <label for="modal_status_name">Status Name</label>
                    <input type="text" id="modal_status_name" wire:model.defer="modal_status_name" placeholder="e.g. In Progress" />
                    @error('modal_status_name') <p class="st-field-error">{{ $message }}</p> @enderror
                </div>

                <div class="st-fg">
                    <label for="modal_status_description">Description</label>
                    <input type="text" id="modal_status_description" wire:model.defer="modal_status_description" placeholder="Status description" />
                </div>

                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="modal_invoice_label">Invoice Label</label>
                        <input type="text" id="modal_invoice_label" wire:model.defer="modal_invoice_label" placeholder="Invoice" />
                    </div>
                    <div class="st-fg">
                        <label for="modal_status_active">Status</label>
                        <select id="modal_status_active" wire:model.defer="modal_status_active">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="st-fg">
                    <label for="modal_email_message">Email Message Template</label>
                    <textarea id="modal_email_message" wire:model.defer="modal_email_message" rows="4"
                              placeholder="Email message sent to customer when job moves to this status..."></textarea>
                    <p class="st-help">Keywords: @{{device_name}}, @{{customer_name}}, @{{order_total}}, @{{order_balance}}</p>
                </div>

                <div class="st-save-bar">
                    <button type="button" class="st-btn-save" wire:click="saveStatus" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveStatus">{{ $showAddModal ? 'Add Status' : 'Update Status' }}</span>
                        <span wire:loading wire:target="saveStatus" class="st-spinner"></span>
                        <span wire:loading wire:target="saveStatus">Saving…</span>
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
