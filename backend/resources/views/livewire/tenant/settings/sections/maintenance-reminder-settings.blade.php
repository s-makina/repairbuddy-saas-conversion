{{-- Maintenance Reminder Settings Section — Livewire Component --}}
<div>
    {{-- ── Reminders Table ── --}}
    <div class="st-section" x-data="{ open: true }">
        <div class="st-section-header" @click="open = !open">
            <h3 class="st-section-title">
                <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                Maintenance Reminders
            </h3>
            <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </div>
        <div class="st-section-body" x-show="open" x-collapse>
            <div style="display:flex; justify-content:flex-end; gap:.5rem; margin-bottom:1rem;">
                <button type="button" class="st-btn-save" wire:click="openAddModal"
                        style="padding:6px 16px; font-size:.82rem;">
                    + Add Reminder
                </button>
            </div>

            @if (count($reminders) === 0)
                <div class="st-empty-state" style="text-align:center; padding:2rem 1rem; color:var(--st-text-muted,#8590a2);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:40px; height:40px; margin:0 auto .5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                    <p>No reminders configured yet.</p>
                    <p style="font-size:.82rem;">Click "Add Reminder" to create one.</p>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table class="st-table" style="width:100%; border-collapse:collapse; font-size:.85rem;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--st-border,#e2e6ed); text-align:left;">
                                <th style="padding:8px 10px;">Name</th>
                                <th style="padding:8px 10px;">Interval</th>
                                <th style="padding:8px 10px;">Device Type</th>
                                <th style="padding:8px 10px;">Brand</th>
                                <th style="padding:8px 10px;">Email</th>
                                <th style="padding:8px 10px;">SMS</th>
                                <th style="padding:8px 10px;">Status</th>
                                <th style="padding:8px 10px; text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reminders as $idx => $reminder)
                                <tr style="border-bottom:1px solid var(--st-border,#e2e6ed);">
                                    <td style="padding:8px 10px; font-weight:600;">{{ $reminder['name'] ?? '—' }}</td>
                                    <td style="padding:8px 10px;">{{ $reminder['interval_days'] ?? '-' }} days</td>
                                    <td style="padding:8px 10px;">{{ $reminder['device_type'] ?? 'All' }}</td>
                                    <td style="padding:8px 10px;">{{ $reminder['brand'] ?? 'All' }}</td>
                                    <td style="padding:8px 10px;">
                                        <span class="st-badge {{ ($reminder['email_enabled'] ?? '') === 'active' ? 'st-badge-active' : 'st-badge-inactive' }}"
                                              style="font-size:.75rem; padding:2px 8px; border-radius:999px;">
                                            {{ ($reminder['email_enabled'] ?? '') === 'active' ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td style="padding:8px 10px;">
                                        <span class="st-badge {{ ($reminder['sms_enabled'] ?? '') === 'active' ? 'st-badge-active' : 'st-badge-inactive' }}"
                                              style="font-size:.75rem; padding:2px 8px; border-radius:999px;">
                                            {{ ($reminder['sms_enabled'] ?? '') === 'active' ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td style="padding:8px 10px;">
                                        <span class="st-badge {{ ($reminder['reminder_enabled'] ?? '') === 'active' ? 'st-badge-active' : 'st-badge-inactive' }}"
                                              style="font-size:.75rem; padding:2px 8px; border-radius:999px;">
                                            {{ ($reminder['reminder_enabled'] ?? '') === 'active' ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td style="padding:8px 10px; text-align:right; white-space:nowrap;">
                                        <button type="button" wire:click="sendTestReminder({{ $idx }})"
                                                style="background:none; border:none; color:var(--st-brand,#063e70); cursor:pointer; font-size:.82rem; margin-right:6px;"
                                                title="Send test to admin">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px; height:16px; vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                                            Test
                                        </button>
                                        <button type="button" wire:click="openEditModal({{ $idx }})"
                                                style="background:none; border:none; color:var(--st-brand,#063e70); cursor:pointer; font-size:.82rem; margin-right:6px;"
                                                title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px; height:16px; vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                            Edit
                                        </button>
                                        <button type="button" wire:click="deleteReminder({{ $idx }})"
                                                wire:confirm="Delete this reminder?"
                                                style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:.82rem;"
                                                title="Delete">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px; height:16px; vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Add / Edit Modal ── --}}
    @if ($showModal)
        <div class="st-modal-backdrop" style="position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center;"
             x-data x-cloak>
            <div class="st-modal" @click.outside="$wire.closeModal()"
                 style="background:#fff; border-radius:10px; width:95%; max-width:620px; max-height:85vh; overflow-y:auto; padding:1.5rem; box-shadow:0 8px 30px rgba(0,0,0,.18);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h3 style="font-size:1.08rem; font-weight:700; color:var(--st-text,#1e293b);">
                        {{ $editingId !== null ? 'Edit Reminder' : 'Add Reminder' }}
                    </h3>
                    <button type="button" wire:click="closeModal" style="background:none; border:none; cursor:pointer; font-size:1.3rem; color:var(--st-text-muted,#8590a2);">&times;</button>
                </div>

                <div class="st-fg">
                    <label for="modal_name">Reminder Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="modal_name" wire:model.defer="modal_name" placeholder="e.g. 30-Day Service Follow-up" />
                </div>

                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="modal_interval_days">Run After <span style="color:#ef4444;">*</span></label>
                        <select id="modal_interval_days" wire:model.defer="modal_interval_days">
                            @foreach ($intervalOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="st-fg">
                        <label for="modal_description">Description</label>
                        <input type="text" id="modal_description" wire:model.defer="modal_description" placeholder="Optional description" />
                    </div>
                </div>

                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="modal_device_type_id">Device Type</label>
                        <select id="modal_device_type_id" wire:model.defer="modal_device_type_id">
                            @foreach ($deviceTypeOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="st-fg">
                        <label for="modal_device_brand_id">Brand</label>
                        <select id="modal_device_brand_id" wire:model.defer="modal_device_brand_id">
                            @foreach ($deviceBrandOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="st-fg">
                    <label for="modal_email_body">Email Message</label>
                    <textarea id="modal_email_body" wire:model.defer="modal_email_body" rows="5"
                              placeholder="Email body for reminder..."></textarea>
                    <p class="st-help">Keywords: @{{device_name}}, @{{customer_name}}, @{{unsubscribe_device}}</p>
                </div>

                <div class="st-fg">
                    <label for="modal_sms_body">SMS Message</label>
                    <textarea id="modal_sms_body" wire:model.defer="modal_sms_body" rows="3"
                              placeholder="SMS body for reminder..."></textarea>
                    <p class="st-help">Keywords: @{{device_name}}, @{{customer_name}}</p>
                </div>

                <div class="st-grid st-grid-3" style="margin-top:.5rem;">
                    <div class="st-fg">
                        <label for="modal_email_enabled">Email Reminder</label>
                        <select id="modal_email_enabled" wire:model.defer="modal_email_enabled">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="st-fg">
                        <label for="modal_sms_enabled">SMS Reminder</label>
                        <select id="modal_sms_enabled" wire:model.defer="modal_sms_enabled">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="st-fg">
                        <label for="modal_reminder_enabled">Reminder Status</label>
                        <select id="modal_reminder_enabled" wire:model.defer="modal_reminder_enabled">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:.5rem; margin-top:1rem; padding-top:.75rem; border-top:1px solid var(--st-border,#e2e6ed);">
                    <button type="button" wire:click="closeModal"
                            style="padding:8px 20px; border:1px solid var(--st-border,#e2e6ed); border-radius:6px; background:#fff; cursor:pointer; color:var(--st-text,#1e293b);">
                        Cancel
                    </button>
                    <button type="button" class="st-btn-save" wire:click="saveReminder"
                            style="padding:8px 20px;" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveReminder">
                            {{ $editingId !== null ? 'Update' : 'Add' }} Reminder
                        </span>
                        <span wire:loading wire:target="saveReminder" class="st-spinner"></span>
                        <span wire:loading wire:target="saveReminder">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
