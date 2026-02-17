<div class="modal fade" id="rbQuickCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add customer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="rbQuickCustomerError"></div>

                <form id="rbQuickCustomerForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_first_name">{{ __('First name') }}</label>
                            <input type="text" class="form-control" id="rb_qc_first_name" name="first_name" autocomplete="given-name" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_last_name">{{ __('Last name') }}</label>
                            <input type="text" class="form-control" id="rb_qc_last_name" name="last_name" autocomplete="family-name" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_email">{{ __('Email') }}</label>
                            <input type="email" class="form-control" id="rb_qc_email" name="email" autocomplete="email" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_phone">{{ __('Phone') }}</label>
                            <input type="text" class="form-control" id="rb_qc_phone" name="phone" autocomplete="tel" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_company">{{ __('Company') }}</label>
                            <input type="text" class="form-control" id="rb_qc_company" name="company" autocomplete="organization" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_tax_id">{{ __('Tax ID') }}</label>
                            <input type="text" class="form-control" id="rb_qc_tax_id" name="tax_id" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_address_line1">{{ __('Address line 1') }}</label>
                            <input type="text" class="form-control" id="rb_qc_address_line1" name="address_line1" autocomplete="address-line1" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_address_line2">{{ __('Address line 2') }}</label>
                            <input type="text" class="form-control" id="rb_qc_address_line2" name="address_line2" autocomplete="address-line2" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_address_city">{{ __('City') }}</label>
                            <input type="text" class="form-control" id="rb_qc_address_city" name="address_city" autocomplete="address-level2" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_address_postal_code">{{ __('Postal code') }}</label>
                            <input type="text" class="form-control" id="rb_qc_address_postal_code" name="address_postal_code" autocomplete="postal-code" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_address_state">{{ __('State / Province') }}</label>
                            <input type="text" class="form-control" id="rb_qc_address_state" name="address_state" autocomplete="address-level1" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rb_qc_address_country">{{ __('Country') }}</label>
                            <select class="form-select" id="rb_qc_address_country" name="address_country" autocomplete="country-name">
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
                            <input type="hidden" id="rb_qc_address_country_code" name="address_country_code" value="" />
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="rbQuickCustomerSave">{{ __('Save') }}</button>
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
                var el = document.getElementById('rbQuickCustomerError');
                if (!el) return;
                el.textContent = message || 'Failed to create customer.';
                el.classList.remove('d-none');
            }

            function clearError() {
                var el = document.getElementById('rbQuickCustomerError');
                if (!el) return;
                el.textContent = '';
                el.classList.add('d-none');
            }

            function ensureModal() {
                var el = document.getElementById('rbQuickCustomerModal');
                if (!el || !window.bootstrap || !window.bootstrap.Modal) return null;
                return window.bootstrap.Modal.getOrCreateInstance(el);
            }

            function getCreateUrl() {
                return (window.RBQuickCustomerModal && window.RBQuickCustomerModal.createUrl) ? String(window.RBQuickCustomerModal.createUrl) : '';
            }

            function getTargetSelectId() {
                return (window.RBQuickCustomerModal && window.RBQuickCustomerModal.targetSelectId) ? String(window.RBQuickCustomerModal.targetSelectId) : 'customer_id';
            }

            function initCountrySelect() {
                var el = document.getElementById('rb_qc_address_country');
                var codeEl = document.getElementById('rb_qc_address_country_code');
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
                var form = document.getElementById('rbQuickCustomerForm');
                if (!form) return;

                clearError();

                var createUrl = getCreateUrl();
                if (!createUrl) {
                    showError('Customer create URL is missing.');
                    return;
                }

                var btn = document.getElementById('rbQuickCustomerSave');
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
                        showError('Failed to create customer.');
                        return;
                    }

                    var client = data && data.client ? data.client : null;
                    var id = client && typeof client.id === 'number' ? client.id : null;
                    var label = client && typeof client.label === 'string' ? client.label : '';

                    if (typeof id !== 'number') {
                        showError('Customer created but response is missing id.');
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
                            opt.textContent = label || ('Customer #' + String(id));
                            sel.appendChild(opt);
                        }

                        sel.value = String(id);

                        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                            window.jQuery(sel).trigger('change');
                        }
                    }

                    var m = ensureModal();
                    if (m) m.hide();

                    if (window.RBQuickCustomerModal && typeof window.RBQuickCustomerModal.onSaved === 'function') {
                        window.RBQuickCustomerModal.onSaved({ id: id, label: label });
                    }
                } catch (err) {
                    showError('Failed to create customer.');
                } finally {
                    if (btn) btn.disabled = false;
                }
            }

            window.RBQuickCustomerModal = window.RBQuickCustomerModal || {};
            window.RBQuickCustomerModal.open = function (opts) {
                clearError();
                window.RBQuickCustomerModal.onSaved = opts && typeof opts.onSaved === 'function' ? opts.onSaved : null;

                var form = document.getElementById('rbQuickCustomerForm');
                if (form) form.reset();

                initCountrySelect();

                var m = ensureModal();
                if (m) m.show();
            };

            var btn = document.getElementById('rbQuickCustomerSave');
            if (btn) {
                btn.addEventListener('click', function () {
                    void submit();
                });
            }
        })();
    </script>
@endpush
