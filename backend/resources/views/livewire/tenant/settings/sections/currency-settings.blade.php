{{-- Currency Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Currency Settings
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="currency">Currency</label>
                        <select id="currency" wire:model.defer="currency">
                            @foreach ($currencyOptions as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('currency') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="st-fg">
                        <label for="currency_position">Currency Position</label>
                        <select id="currency_position" wire:model.defer="currency_position">
                            @foreach ($positionOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('currency_position') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="st-grid st-grid-3">
                    <div class="st-fg">
                        <label for="thousand_separator">Thousand Separator</label>
                        <input type="text" id="thousand_separator" wire:model.defer="thousand_separator"
                               style="max-width: 80px;" maxlength="3" />
                        @error('thousand_separator') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="st-fg">
                        <label for="decimal_separator">Decimal Separator</label>
                        <input type="text" id="decimal_separator" wire:model.defer="decimal_separator"
                               style="max-width: 80px;" maxlength="3" />
                        @error('decimal_separator') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="st-fg">
                        <label for="number_of_decimals">Number of Decimals</label>
                        <input type="number" id="number_of_decimals" wire:model.defer="number_of_decimals"
                               min="0" max="8" step="1" style="max-width: 80px;" />
                        @error('number_of_decimals') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Preview --}}
                <div style="margin-top: .5rem; padding: .75rem 1rem; background: var(--st-bg, #f8fafc); border: 1px solid var(--st-border, #e2e8f0); border-radius: 8px;">
                    <p style="font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3, #94a3b8); margin: 0 0 .25rem;">Preview</p>
                    <p style="font-size: 1.1rem; font-weight: 700; color: var(--st-text, #0f172a); margin: 0;"
                       x-data="{
                           format() {
                               let cur = $wire.currency || 'USD';
                               let pos = $wire.currency_position || 'left';
                               let thou = $wire.thousand_separator || ',';
                               let dec = $wire.decimal_separator || '.';
                               let places = parseInt($wire.number_of_decimals) || 0;
                               let num = '1' + thou + '234' + (places > 0 ? dec + '5'.repeat(places) : '');
                               let sym = cur;
                               if (pos === 'left') return sym + num;
                               if (pos === 'right') return num + sym;
                               if (pos === 'left_space') return sym + ' ' + num;
                               return num + ' ' + sym;
                           }
                       }"
                       x-text="format()">
                    </p>
                </div>
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Currency Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
