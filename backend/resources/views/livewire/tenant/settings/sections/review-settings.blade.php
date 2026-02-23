{{-- Review Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></svg>
                    Job Review Settings
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="request_by_sms" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Request Feedback by SMS</label>
                        <p class="st-option-desc">Send review request via SMS to customers</p>
                    </div>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="request_by_email" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Request Feedback by Email</label>
                        <p class="st-option-desc">Send review request via email to customers</p>
                    </div>
                </div>

                <div class="st-grid st-grid-2" style="margin-top: .75rem;">
                    <div class="st-fg">
                        <label for="send_request_job_status">Send Review When Job Status Is</label>
                        <select id="send_request_job_status" wire:model.defer="send_request_job_status">
                            <option value="">— Select Status —</option>
                            @foreach ($statusOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Trigger review request when job reaches this status</p>
                    </div>
                    <div class="st-fg">
                        <label for="auto_request_interval">Auto Feedback Request Interval</label>
                        <select id="auto_request_interval" wire:model.defer="auto_request_interval">
                            @foreach ($intervalOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Automatically send follow-up review requests</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Email Template ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                    Review Email Template
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-fg">
                    <label for="email_subject">Email Subject</label>
                    <input type="text" id="email_subject" wire:model.defer="email_subject"
                           placeholder="We'd love your feedback" />
                </div>
                <div class="st-fg">
                    <label for="email_message">Email Message Template</label>
                    <textarea id="email_message" wire:model.defer="email_message" rows="6"
                              placeholder="Review request email body..."></textarea>
                    <p class="st-help">Keywords: @{{st_feedback_anch}}, @{{end_feedback_anch}}, @{{feedback_link}}, @{{job_id}}, @{{customer_device_label}}, @{{case_number}}, @{{customer_full_name}}</p>
                </div>
            </div>
        </div>

        {{-- ── SMS Template ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-6 18h6"/></svg>
                    Review SMS Template
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-fg">
                    <label for="sms_message">SMS Message Template</label>
                    <textarea id="sms_message" wire:model.defer="sms_message" rows="4"
                              placeholder="Review request SMS message..."></textarea>
                    <p class="st-help">Keywords: @{{feedback_link}}, @{{job_id}}, @{{customer_device_label}}, @{{case_number}}, @{{customer_full_name}}</p>
                </div>
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Review Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
