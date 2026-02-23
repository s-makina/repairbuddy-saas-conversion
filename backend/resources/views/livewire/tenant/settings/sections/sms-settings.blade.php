{{-- SMS Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        {{-- ── Activation ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                    SMS Activation
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="sms_active" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Activate SMS for Selective Statuses</label>
                        <p class="st-option-desc">Enable SMS notifications when job status changes to selected statuses</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Gateway Configuration ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z"/></svg>
                    Gateway Configuration
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-fg">
                    <label for="sms_gateway">SMS Gateway</label>
                    <select id="sms_gateway" wire:model="sms_gateway">
                        @foreach ($gatewayOptions as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Twilio-specific fields --}}
                @if ($sms_gateway === 'twilio')
                    <div class="st-grid st-grid-3" style="margin-top: .5rem;">
                        <div class="st-fg">
                            <label for="gateway_account_sid">Account SID</label>
                            <input type="text" id="gateway_account_sid" wire:model.defer="gateway_account_sid"
                                   placeholder="ACxxxxxx..." />
                        </div>
                        <div class="st-fg">
                            <label for="gateway_auth_token">Auth Token</label>
                            <input type="password" id="gateway_auth_token" wire:model.defer="gateway_auth_token"
                                   placeholder="Auth token" />
                        </div>
                        <div class="st-fg">
                            <label for="gateway_from_number">From Number</label>
                            <input type="tel" id="gateway_from_number" wire:model.defer="gateway_from_number"
                                   placeholder="+1234567890" />
                        </div>
                    </div>
                @elseif ($sms_gateway === 'vonage' || $sms_gateway === 'messagebird')
                    <div class="st-grid st-grid-2" style="margin-top: .5rem;">
                        <div class="st-fg">
                            <label for="gateway_account_sid">API Key</label>
                            <input type="text" id="gateway_account_sid" wire:model.defer="gateway_account_sid"
                                   placeholder="API Key" />
                        </div>
                        <div class="st-fg">
                            <label for="gateway_auth_token">API Secret</label>
                            <input type="password" id="gateway_auth_token" wire:model.defer="gateway_auth_token"
                                   placeholder="API Secret" />
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Status Selection ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    Send SMS When Status Changes To
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                @forelse ($available_statuses as $status)
                    <div class="st-option-card">
                        <div class="st-option-control">
                            <label class="st-toggle">
                                <input type="checkbox" value="{{ $status['key'] }}" wire:model.defer="included_statuses" />
                                <span class="st-toggle-track"></span>
                            </label>
                        </div>
                        <div class="st-option-body">
                            <label class="st-option-title">{{ $status['label'] }}</label>
                        </div>
                    </div>
                @empty
                    <p style="font-size: .82rem; color: var(--st-text-3); padding: .5rem 0;">No job statuses configured. Set up job statuses first.</p>
                @endforelse
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save SMS Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>

    {{-- ── Test SMS ── --}}
    <div class="st-section" x-data="{ open: false }" style="margin-top: 1.25rem;">
        <div class="st-section-header" @click="open = !open">
            <h3 class="st-section-title">
                <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                Send Test SMS
            </h3>
            <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </div>
        <div class="st-section-body" x-show="open" x-collapse>
            <div class="st-fg">
                <label for="test_number">Phone Number</label>
                <input type="tel" id="test_number" wire:model.defer="test_number" placeholder="+1234567890" />
            </div>
            <div class="st-fg">
                <label for="test_message">Message</label>
                <textarea id="test_message" wire:model.defer="test_message" rows="3"
                          placeholder="Test SMS message..."></textarea>
            </div>
            <div class="st-save-bar">
                <button type="button" class="st-btn-save" wire:click="sendTestSms" wire:loading.attr="disabled"
                        style="background: #22c55e;">
                    <span wire:loading.remove wire:target="sendTestSms">Send Test Message</span>
                    <span wire:loading wire:target="sendTestSms" class="st-spinner"></span>
                    <span wire:loading wire:target="sendTestSms">Sending…</span>
                </button>
            </div>
        </div>
    </div>
</div>
