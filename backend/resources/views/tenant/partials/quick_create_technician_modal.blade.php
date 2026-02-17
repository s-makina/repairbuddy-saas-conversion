<div class="modal fade" id="rbQuickTechnicianModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add technician') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="rbQuickTechnicianError"></div>

                <form id="rbQuickTechnicianForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qt_name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" id="rb_qt_name" name="name" autocomplete="name" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qt_email">{{ __('Email') }}</label>
                            <input type="email" class="form-control" id="rb_qt_email" name="email" autocomplete="email" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qt_phone">{{ __('Phone') }}</label>
                            <input type="text" class="form-control" id="rb_qt_phone" name="phone" autocomplete="tel" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qt_address_line1">{{ __('Address line 1') }}</label>
                            <input type="text" class="form-control" id="rb_qt_address_line1" name="address_line1" autocomplete="address-line1" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qt_address_line2">{{ __('Address line 2') }}</label>
                            <input type="text" class="form-control" id="rb_qt_address_line2" name="address_line2" autocomplete="address-line2" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qt_address_city">{{ __('City') }}</label>
                            <input type="text" class="form-control" id="rb_qt_address_city" name="address_city" autocomplete="address-level2" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qt_address_postal_code">{{ __('Postal code') }}</label>
                            <input type="text" class="form-control" id="rb_qt_address_postal_code" name="address_postal_code" autocomplete="postal-code" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qt_address_state">{{ __('State / Province') }}</label>
                            <input type="text" class="form-control" id="rb_qt_address_state" name="address_state" autocomplete="address-level1" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qt_address_country">{{ __('Country') }}</label>
                            <select class="form-select" id="rb_qt_address_country" name="address_country" autocomplete="country-name">
                                <option value="">{{ __('Select a country') }}</option>
                                @php
                                    $countries = [
                                        'Afghanistan','Albania','Algeria','Andorra','Angola','Antigua and Barbuda','Argentina','Armenia','Australia','Austria','Azerbaijan',
                                        'Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bhutan','Bolivia','Bosnia and Herzegovina','Botswana','Brazil','Brunei','Bulgaria','Burkina Faso','Burundi',
                                        'Cabo Verde','Cambodia','Cameroon','Canada','Central African Republic','Chad','Chile','China','Colombia','Comoros','Congo (Congo-Brazzaville)','Costa Rica','Croatia','Cuba','Cyprus','Czechia (Czech Republic)',
                                        'Democratic Republic of the Congo','Denmark','Djibouti','Dominica','Dominican Republic',
                                        'Ecuador','Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Eswatini (fmr. Swaziland)','Ethiopia',
                                        'Fiji','Finland','France',
                                        'Gabon','Gambia','Georgia','Germany','Ghana','Greece','Grenada','Guatemala','Guinea','Guinea-Bissau','Guyana',
                                        'Haiti','Honduras','Hungary',
                                        'Iceland','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy',
                                        'Jamaica','Japan','Jordan',
                                        'Kazakhstan','Kenya','Kiribati','Kuwait','Kyrgyzstan',
                                        'Laos','Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg',
                                        'Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Marshall Islands','Mauritania','Mauritius','Mexico','Micronesia','Moldova','Monaco','Mongolia','Montenegro','Morocco','Mozambique','Myanmar (formerly Burma)',
                                        'Namibia','Nauru','Nepal','Netherlands','New Zealand','Nicaragua','Niger','Nigeria','North Korea','North Macedonia','Norway',
                                        'Oman',
                                        'Pakistan','Palau','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Poland','Portugal',
                                        'Qatar',
                                        'Romania','Russia','Rwanda',
                                        'Saint Kitts and Nevis','Saint Lucia','Saint Vincent and the Grenadines','Samoa','San Marino','Sao Tome and Principe','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia','Solomon Islands','Somalia','South Africa','South Korea','South Sudan','Spain','Sri Lanka','Sudan','Suriname','Sweden','Switzerland','Syria',
                                        'Taiwan','Tajikistan','Tanzania','Thailand','Timor-Leste','Togo','Tonga','Trinidad and Tobago','Tunisia','Turkey','Turkmenistan','Tuvalu',
                                        'Uganda','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Uzbekistan',
                                        'Vanuatu','Vatican City','Venezuela','Vietnam',
                                        'Yemen',
                                        'Zambia','Zimbabwe',
                                    ];
                                @endphp
                                @foreach ($countries as $country)
                                    <option value="{{ $country }}">{{ $country }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="rb_qt_address_country_code" name="address_country_code" value="" />
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Shops') }}</label>
                            <div class="border rounded p-2" style="max-height: 260px; overflow:auto;">
                                @php
                                    $branches = is_iterable($branches ?? null) ? $branches : [];
                                @endphp
                                @forelse ($branches as $b)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="{{ $b->id }}" id="rb_qt_branch_{{ $b->id }}" name="branch_ids[]" @checked(($b->is_active ?? false) && (isset($tenant) && (int) ($tenant->default_branch_id ?? 0) === (int) $b->id)) />
                                        <label class="form-check-label" for="rb_qt_branch_{{ $b->id }}">
                                            {{ (string) ($b->code ?? '') }} - {{ (string) ($b->name ?? '') }}
                                            @if (!($b->is_active ?? true)) ({{ __('inactive') }}) @endif
                                        </label>
                                    </div>
                                @empty
                                    <div class="text-muted">{{ __('No shops available.') }}</div>
                                @endforelse
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">
                                {{ __('A one-time password will be generated and emailed to the technician.') }}
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="rbQuickTechnicianSave">{{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>

@push('page-scripts')
    <script>
        (function () {
            function getCsrfToken() {
                var meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? (meta.getAttribute('content') || '') : '';
            }

            function showError(message) {
                var el = document.getElementById('rbQuickTechnicianError');
                if (!el) return;
                el.textContent = message || 'Failed to create technician.';
                el.classList.remove('d-none');
            }

            function clearError() {
                var el = document.getElementById('rbQuickTechnicianError');
                if (!el) return;
                el.textContent = '';
                el.classList.add('d-none');
            }

            function ensureModal() {
                var el = document.getElementById('rbQuickTechnicianModal');
                if (!el || !window.bootstrap || !window.bootstrap.Modal) return null;
                return window.bootstrap.Modal.getOrCreateInstance(el);
            }

            function getCreateUrl() {
                return (window.RBQuickTechnicianModal && window.RBQuickTechnicianModal.createUrl) ? String(window.RBQuickTechnicianModal.createUrl) : '';
            }

            function getTargetSelectId() {
                return (window.RBQuickTechnicianModal && window.RBQuickTechnicianModal.targetSelectId) ? String(window.RBQuickTechnicianModal.targetSelectId) : 'technician_ids';
            }

            function initCountrySelect() {
                var el = document.getElementById('rb_qt_address_country');
                var codeEl = document.getElementById('rb_qt_address_country_code');
                if (!el || !codeEl) return;

                var countryCodes = {
                    'South Africa': 'ZA',
                    'United States': 'US',
                    'United Kingdom': 'GB',
                    'Germany': 'DE',
                    'France': 'FR',
                    'Italy': 'IT',
                    'Spain': 'ES',
                    'Netherlands': 'NL',
                    'Belgium': 'BE',
                    'Switzerland': 'CH',
                    'Austria': 'AT',
                    'Sweden': 'SE',
                    'Norway': 'NO',
                    'Denmark': 'DK',
                    'Finland': 'FI',
                    'Ireland': 'IE',
                    'Portugal': 'PT',
                    'Poland': 'PL',
                    'Czechia (Czech Republic)': 'CZ',
                    'Greece': 'GR',
                    'Hungary': 'HU',
                    'Romania': 'RO',
                    'Bulgaria': 'BG',
                    'Croatia': 'HR',
                    'Slovakia': 'SK',
                    'Slovenia': 'SI',
                    'Estonia': 'EE',
                    'Latvia': 'LV',
                    'Lithuania': 'LT',
                    'Canada': 'CA',
                    'Australia': 'AU',
                    'New Zealand': 'NZ',
                    'India': 'IN',
                    'Japan': 'JP',
                    'China': 'CN',
                    'Singapore': 'SG',
                    'United Arab Emirates': 'AE'
                };

                function updateCode() {
                    var name = (el.value || '').toString();
                    codeEl.value = countryCodes[name] || '';
                }

                if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                    var $el = window.jQuery(el);
                    if (!$el.hasClass('select2-hidden-accessible')) {
                        $el.select2({ width: '100%', placeholder: @json(__('Select a country')), allowClear: true });
                    }
                    $el.on('change', updateCode);
                } else {
                    el.addEventListener('change', updateCode);
                }

                updateCode();
            }

            async function submit() {
                var form = document.getElementById('rbQuickTechnicianForm');
                if (!form) return;

                clearError();

                var createUrl = getCreateUrl();
                if (!createUrl) {
                    showError('Technician create URL is missing.');
                    return;
                }

                var btn = document.getElementById('rbQuickTechnicianSave');
                if (btn) btn.disabled = true;

                try {
                    var fd = new FormData(form);

                    var res = await fetch(createUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        },
                        body: fd
                    });

                    var data = null;
                    try {
                        data = await res.json();
                    } catch (e) {
                        data = null;
                    }

                    if (!res.ok) {
                        if (data && data.message) {
                            showError(String(data.message));
                            return;
                        }
                        showError('Failed to create technician.');
                        return;
                    }

                    var user = data && data.user ? data.user : null;
                    var id = user && typeof user.id === 'number' ? user.id : null;
                    var label = user && typeof user.label === 'string' ? user.label : '';

                    if (typeof id !== 'number') {
                        showError('Technician created but response is missing id.');
                        return;
                    }

                    var selectId = getTargetSelectId();
                    var sel = document.getElementById(selectId);
                    if (sel && sel.tagName === 'SELECT') {
                        var exists = false;
                        for (var i = 0; i < sel.options.length; i++) {
                            if (String(sel.options[i].value) === String(id)) {
                                exists = true;
                                break;
                            }
                        }

                        if (!exists) {
                            var opt = document.createElement('option');
                            opt.value = String(id);
                            opt.textContent = label || ('Technician #' + String(id));
                            opt.selected = true;
                            sel.appendChild(opt);
                        } else {
                            for (var j = 0; j < sel.options.length; j++) {
                                if (String(sel.options[j].value) === String(id)) {
                                    sel.options[j].selected = true;
                                }
                            }
                        }

                        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                            window.jQuery(sel).trigger('change');
                        }
                    }

                    var m = ensureModal();
                    if (m) m.hide();

                    if (window.RBQuickTechnicianModal && typeof window.RBQuickTechnicianModal.onSaved === 'function') {
                        window.RBQuickTechnicianModal.onSaved({ id: id, label: label });
                    }
                } catch (err) {
                    showError('Failed to create technician.');
                } finally {
                    if (btn) btn.disabled = false;
                }
            }

            window.RBQuickTechnicianModal = window.RBQuickTechnicianModal || {};
            window.RBQuickTechnicianModal.open = function (opts) {
                clearError();
                window.RBQuickTechnicianModal.onSaved = opts && typeof opts.onSaved === 'function' ? opts.onSaved : null;

                var form = document.getElementById('rbQuickTechnicianForm');
                if (form) form.reset();

                initCountrySelect();

                var m = ensureModal();
                if (m) m.show();
            };

            var btn = document.getElementById('rbQuickTechnicianSave');
            if (btn) {
                btn.addEventListener('click', function () {
                    void submit();
                });
            }
        })();
    </script>
@endpush
