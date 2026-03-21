@php
    $tenantSlug = $tenantSlug ?? $business ?? null;
    $tenant = $tenant ?? null;
    $user = $user ?? null;
    $activePage = 'book';
    $isSubdomain = request()->routeIs('tenant.subdomain.*');
    $tenantRoutePrefix = $isSubdomain ? 'tenant.subdomain' : 'tenant';
    $homeRoute = $tenantRoutePrefix . '.welcome';
    $apiBase = '/api/t/' . e($tenantSlug) . '/booking';
    
    // Prepopulate contact info from logged-in user
    $prefillFirstName = $user?->first_name ?? '';
    $prefillLastName = $user?->last_name ?? '';
    $prefillEmail = $user?->email ?? '';
    $prefillPhone = $user?->phone ?? '';
    $prefillCompany = $user?->company ?? '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $tenant->name ?? 'RepairBuddy' }} — Book a Repair</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/tenant-public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tenant-book.css') }}">
</head>
<body>
    @include('tenant.partials.tenant-nav', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant,
        'activePage' => $activePage
    ])

    <!-- BOOKING DISABLED -->
    <div class="booking-page" id="bookingDisabled" style="display:none">
        <div class="step-card">
            <div class="success-card">
                <div class="success-icon" style="background:rgba(253,103,66,.08);color:var(--rb-orange)">✕</div>
                <h2>Booking Unavailable</h2>
                <p>Online booking is currently disabled. Please contact us directly for repair services.</p>
                <a href="{{ route($homeRoute, ['business' => $tenantSlug]) }}" class="btn btn-primary btn-lg" style="margin-top:24px">← Back to Home</a>
            </div>
        </div>
    </div>

    <!-- BOOKING LOADING -->
    <div class="booking-page" id="bookingLoading">
        <div class="booking-page-header">
            <h1>Book a Repair</h1>
            <p>Loading booking form…</p>
        </div>
        <div class="step-card" style="text-align:center;padding:60px 40px">
            <div class="rb-spinner"></div>
            <p style="margin-top:16px;color:var(--rb-text-2);font-size:14px">Preparing your booking experience…</p>
        </div>
    </div>

    <!-- BOOKING MAIN -->
    <div class="booking-page" id="bookingMain" style="display:none">
        <div class="booking-page-header">
            <h1>Book a Repair</h1>
            <p>Follow the steps below to submit your repair request</p>
        </div>

        <!-- Progress Bar -->
        <div class="progress-bar" id="progressBar">
            <div class="progress-step current" data-step="1"><div class="progress-step-circle">1</div><span class="progress-step-label">Device Type</span></div>
            <div class="progress-connector"></div>
            <div class="progress-step" data-step="2"><div class="progress-step-circle">2</div><span class="progress-step-label">Brand</span></div>
            <div class="progress-connector"></div>
            <div class="progress-step" data-step="3"><div class="progress-step-circle">3</div><span class="progress-step-label">Device</span></div>
            <div class="progress-connector"></div>
            <div class="progress-step" data-step="4"><div class="progress-step-circle">4</div><span class="progress-step-label">Service</span></div>
            <div class="progress-connector"></div>
            <div class="progress-step" data-step="5"><div class="progress-step-circle">5</div><span class="progress-step-label">Details</span></div>
        </div>

        <!-- STEP 1: Device Type -->
        <div class="step-card" id="step1">
            <div class="step-header"><h2>Select Device Type</h2><p>What type of device needs repair?</p></div>
            <div id="step1Loading" class="rb-loading-inline"><div class="rb-spinner"></div></div>
            <div class="selection-grid" id="deviceTypeGrid" style="display:none"></div>
            <div id="step1Empty" class="rb-empty-state" style="display:none">No device types available.</div>
        </div>

        <!-- STEP 2: Brand -->
        <div class="step-card" id="step2" style="display:none">
            <div class="step-header"><h2>Select Brand</h2><p>Which manufacturer or brand?</p></div>
            <button class="btn-back" id="step2Back" onclick="RB.goToStep(1)">← Back to Device Types</button>
            <div id="step2Loading" class="rb-loading-inline" style="margin-top:20px"><div class="rb-spinner"></div></div>
            <div class="selection-grid" id="brandGrid" style="display:none;margin-top:20px"></div>
            <div id="step2Empty" class="rb-empty-state" style="display:none">No brands available for this device type.</div>
        </div>

        <!-- STEP 3: Device -->
        <div class="step-card" id="step3" style="display:none">
            <div class="step-header"><h2>Select Device</h2><p>Search and select the device(s) that need repair.</p></div>
            <button class="btn-back" onclick="RB.goToStep(2)">← Back to Brands</button>
            <div class="search-wrapper" style="margin-top:20px">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" id="deviceSearch" placeholder="Search devices…" oninput="RB.filterDevices(this.value)" />
            </div>
            <div id="step3Loading" class="rb-loading-inline"><div class="rb-spinner"></div></div>
            <div class="selection-grid selection-grid-compact" id="deviceGrid" style="display:none"></div>
            <div id="step3Empty" class="rb-empty-state" style="display:none">No devices found.</div>

            <!-- Selected Devices -->
            <div class="selected-devices" id="selectedDevices" style="display:none">
                <div class="selected-devices-title">✅ Selected Devices <span class="count" id="deviceCount">0</span></div>
                <div id="deviceList"></div>
                <div class="step-actions">
                    <div></div>
                    <button class="btn btn-primary btn-lg" onclick="RB.goToStep(4)">Continue to Services →</button>
                </div>
            </div>
        </div>

        <!-- STEP 4: Services -->
        <div class="step-card" id="step4" style="display:none">
            <div class="step-header"><h2>Select Services</h2><p>Choose one or more services for each device.</p></div>
            <button class="btn-back" onclick="RB.goToStep(3)">← Back to Devices</button>
            <div id="step4Loading" class="rb-loading-inline" style="margin-top:20px"><div class="rb-spinner"></div></div>
            <div id="serviceDeviceEntries" style="margin-top:20px;display:none"></div>

            <!-- Appointment -->
            <div id="appointmentSection" class="appointment-section" style="display:none;margin-top:24px">
                <h5>📅 Schedule an Appointment <span class="opt-badge">Optional</span></h5>
                <p>Select a preferred date and time for your visit.</p>
                <div class="appt-options" id="appointmentTypes"></div>
                <div class="appt-datetime" id="appointmentDateTime" style="display:none">
                    <div>
                        <label>Preferred Date</label>
                        <input type="date" id="appointmentDate" onchange="RB.onAppointmentDateChange()" />
                    </div>
                    <div>
                        <label>Preferred Time</label>
                        <div class="time-slots" id="appointmentTimeSlots"></div>
                    </div>
                </div>
            </div>

            <div class="step-actions" id="step4Actions" style="display:none">
                <button class="btn btn-outline" onclick="RB.goToStep(3)">+ Add Another Device</button>
                <button class="btn btn-primary btn-lg" onclick="RB.validateAndGoToStep5()">Continue →</button>
            </div>
        </div>

        <!-- STEP 5: Your Details -->
        <div class="step-card" id="step5" style="display:none">
            <div class="step-header"><h2>Your Details</h2><p>Tell us how to reach you.</p></div>
            <button class="btn-back" onclick="RB.goToStep(4)">← Back to Services</button>

            <!-- Summary -->
            <div class="booking-summary" id="bookingSummary" style="margin-top:20px">
                <h6>🛒 Your Devices & Services</h6>
                <div id="summaryItems"></div>
                <div id="summaryTotal" class="summary-total" style="display:none"></div>
            </div>

            <!-- Warranty section (shown only in warranty mode) -->
            <div id="warrantySection" class="form-section" style="display:none">
                <div class="form-section-title"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>Warranty Details</div>
                <div class="form-grid">
                    <div class="form-group"><label>Date of Purchase <span class="req">*</span></label><input type="date" id="warrantyDate" /></div>
                </div>
            </div>

            <!-- Contact -->
            <div class="form-section">
                <div class="form-section-title"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Contact Information</div>
                <div class="form-grid">
                    <div class="form-group"><label>First Name <span class="req">*</span></label><input type="text" id="customerFirstName" placeholder="John" maxlength="255" required value="{{ $prefillFirstName }}" /></div>
                    <div class="form-group"><label>Last Name <span class="req">*</span></label><input type="text" id="customerLastName" placeholder="Doe" maxlength="255" required value="{{ $prefillLastName }}" /></div>
                    <div class="form-group"><label>Email <span class="req">*</span></label><input type="email" id="customerEmail" placeholder="john@example.com" maxlength="255" required value="{{ $prefillEmail }}" /></div>
                    <div class="form-group"><label>Phone</label><input type="tel" id="customerPhone" placeholder="+1 (555) 000-0000" maxlength="64" value="{{ $prefillPhone }}" /></div>
                    <div class="form-group"><label>Company</label><input type="text" id="customerCompany" placeholder="Company name" maxlength="255" value="{{ $prefillCompany }}" /></div>
                    <div class="form-group"><label>Tax ID</label><input type="text" id="customerTaxId" placeholder="Tax ID" maxlength="64" /></div>
                </div>
            </div>

            <!-- Address -->
            <div class="form-section">
                <div class="form-section-title"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Address <span style="color:var(--rb-text-3);font-weight:400;font-size:12px">(Optional)</span></div>
                <div class="form-grid">
                    <div class="form-group full"><label>Street Address</label><input type="text" id="customerAddress" placeholder="123 Main St" maxlength="255" /></div>
                    <div class="form-group"><label>City</label><input type="text" id="customerCity" placeholder="Portland" maxlength="255" /></div>
                    <div class="form-group"><label>Postal Code</label><input type="text" id="customerPostal" placeholder="97201" maxlength="64" /></div>
                </div>
            </div>

            <!-- Job Details -->
            <div class="form-section">
                <div class="form-section-title"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Booking Details</div>
                <div class="form-grid">
                    <div class="form-group full"><label>Job Details <span class="req">*</span></label><textarea rows="4" id="jobDetails" placeholder="Describe the issue or service needed…" maxlength="5000" required></textarea></div>
                </div>
            </div>

            <!-- File Attachments -->
            <div class="form-section">
                <div class="form-section-title"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>Attachments <span style="color:var(--rb-text-3);font-weight:400;font-size:12px">(Optional, max 5 files)</span></div>
                <div class="form-grid">
                    <div class="form-group full">
                        <input type="file" id="attachments" multiple accept="image/*,.pdf,.doc,.docx" style="font-size:13px" />
                        <div id="attachmentList" style="margin-top:8px;font-size:12px;color:var(--rb-text-2)"></div>
                    </div>
                </div>
            </div>

            <div id="gdprSection" class="gdpr-check" style="display:none">
                <input type="checkbox" id="gdprCheckbox" />
                <label for="gdprCheckbox" id="gdprLabel">I agree to the processing of my personal data in accordance with the <a href="#" id="gdprLink">Privacy Policy</a>.</label>
            </div>

            <!-- Validation errors -->
            <div id="submitErrors" class="rb-error-box" style="display:none"></div>

            <div class="step-actions">
                <div></div>
                <button class="btn btn-orange btn-lg" id="submitBtn" onclick="RB.submitBooking()">
                    <span id="submitBtnText">📨 Submit Booking</span>
                    <span id="submitBtnSpinner" style="display:none"><span class="rb-spinner-sm"></span> Submitting…</span>
                </button>
            </div>
        </div>

        <!-- SUCCESS -->
        <div class="step-card" id="stepSuccess" style="display:none">
            <div class="success-card">
                <div class="success-icon">✓</div>
                <h2>Booking Submitted!</h2>
                <p>Thank you! Your repair request has been received. We'll review it and get back to you shortly.</p>
                <div class="case-number">
                    <span class="label">Case Number</span>
                    <span class="value" id="successCaseNumber"></span>
                </div>
                <p style="margin-top:12px;font-size:13px;color:var(--rb-text-2)">You will receive an email confirmation shortly.</p>
                <div style="margin-top:24px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
                    <a href="#" id="statusCheckLink" class="btn btn-outline">Check Status</a>
                    <a href="{{ route($homeRoute, ['business' => $tenantSlug]) }}" class="btn btn-primary btn-lg">← Back to Home</a>
                </div>
            </div>
        </div>
    </div>

    @include('tenant.partials.tenant-footer', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant
    ])

    <script>
    (function() {
        'use strict';

        const API_BASE = @json($apiBase);

        // ─── State ───
        const state = {
            config: null,
            deviceTypes: [],
            brands: [],
            allDevices: [],
            filteredDevices: [],
            appointmentSettings: [],
            deviceFieldDefs: [],
            selectedTypeId: null,
            selectedTypeName: '',
            selectedBrandId: null,
            selectedBrandName: '',
            selectedDevices: [],   // [{device_id, model, is_other, serial, pin, notes, extra_fields:[{key,label,value_text}], services:[{service_id,name,price}], other_service:''}]
            servicesCache: {},     // deviceId -> {services/groups}
            otherDeviceCounter: 0, // counter for generating unique __other_N__ ids
            selectedAppointment: { setting_id: null, date: null, time_slot: null },
            currentStep: 1,
            uiStyle: 'wizard',
            submitting: false,
        };

        // ─── Helpers ───
        function esc(str) {
            const d = document.createElement('div');
            d.textContent = str ?? '';
            return d.innerHTML;
        }

        function formatPrice(cents, currency) {
            if (cents == null || currency == null) return '';
            try {
                return new Intl.NumberFormat(undefined, { style: 'currency', currency: currency }).format(cents / 100);
            } catch {
                return (currency + ' ' + (cents / 100).toFixed(2));
            }
        }

        async function apiFetch(path, opts = {}) {
            const url = API_BASE + path;
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...opts.headers },
                ...opts,
            });
            if (!res.ok) {
                const body = await res.json().catch(() => ({}));
                const err = new Error(body.message || 'Request failed');
                err.status = res.status;
                err.body = body;
                throw err;
            }
            return res.json();
        }

        function show(id) { document.getElementById(id).style.display = ''; }
        function hide(id) { document.getElementById(id).style.display = 'none'; }
        function el(id) { return document.getElementById(id); }

        // ─── Init ───
        async function init() {
            try {
                const [configData, fieldData] = await Promise.all([
                    apiFetch('/config'),
                    apiFetch('/device-field-definitions'),
                ]);

                state.config = configData;
                state.deviceFieldDefs = fieldData.fields || [];

                if (configData.disabled) {
                    hide('bookingLoading');
                    show('bookingDisabled');
                    return;
                }

                // Setup GDPR
                const gdpr = configData.general || {};
                if (gdpr.gdprAcceptanceText && gdpr.gdprAcceptanceText.trim() !== '') {
                    show('gdprSection');
                    const labelEl = el('gdprLabel');
                    let text = esc(gdpr.gdprAcceptanceText);
                    if (gdpr.gdprLinkUrl && gdpr.gdprLinkLabel) {
                        el('gdprLink').href = gdpr.gdprLinkUrl;
                        el('gdprLink').textContent = gdpr.gdprLinkLabel;
                    }
                    labelEl.innerHTML = text + (gdpr.gdprLinkUrl ? ' <a href="' + esc(gdpr.gdprLinkUrl) + '" target="_blank" rel="noopener">' + esc(gdpr.gdprLinkLabel || 'Privacy Policy') + '</a>.' : '');
                }

                // Setup warranty mode
                if (state.config.booking.publicBookingMode === 'warranty') {
                    show('warrantySection');
                }

                // Setup UI style
                state.uiStyle = configData.booking.publicBookingUiStyle || 'wizard';
                if (state.uiStyle === 'images') {
                    document.querySelectorAll('.selection-grid').forEach(g => g.classList.add('selection-grid-images'));
                }

                // Setup ungrouped mode (skip Device Type step)
                const isUngrouped = configData.booking.publicBookingMode === 'ungrouped';
                if (isUngrouped) {
                    // Hide step 1 progress indicator + first connector
                    const progSteps = el('progressBar').querySelectorAll('.progress-step');
                    const progConns = el('progressBar').querySelectorAll('.progress-connector');
                    if (progSteps[0]) progSteps[0].style.display = 'none';
                    if (progConns[0]) progConns[0].style.display = 'none';
                    // Relabel remaining steps
                    const labels = ['Brand', 'Device', 'Service', 'Details'];
                    for (let i = 1; i < progSteps.length; i++) {
                        const circle = progSteps[i].querySelector('.progress-step-circle');
                        const label = progSteps[i].querySelector('.progress-step-label');
                        if (circle) circle.textContent = i;
                        if (label) label.textContent = labels[i - 1] || '';
                    }
                    // Hide "Back to Device Types" button
                    const step2Back = el('step2Back');
                    if (step2Back) step2Back.style.display = 'none';
                }

                // Load appointment settings
                apiFetch('/appointment-settings').then(data => {
                    state.appointmentSettings = data.appointment_settings || [];
                }).catch(() => {});

                const defaults = configData.booking || {};

                if (isUngrouped) {
                    // Ungrouped: skip Device Type, go straight to Brands
                    hide('bookingLoading');
                    show('bookingMain');
                    goToStep(2);

                    if (defaults.defaultBrand) {
                        await loadBrands(null);
                        const defBrand = state.brands.find(b => b.id == defaults.defaultBrand);
                        if (defBrand) {
                            state.selectedBrandId = defBrand.id;
                            state.selectedBrandName = defBrand.name;
                            await loadDevices(null, defBrand.id);
                            if (defaults.defaultDevice) {
                                const defDev = state.allDevices.find(d => d.id == defaults.defaultDevice);
                                if (defDev) { addDevice(defDev); goToStep(4); return; }
                            }
                            goToStep(3);
                            return;
                        }
                    } else {
                        await loadBrands(null);
                    }
                    return;
                }

                // Grouped / Warranty: load device types
                await loadDeviceTypes();

                hide('bookingLoading');
                show('bookingMain');

                // Default selections
                if (defaults.defaultType) {
                    const defType = state.deviceTypes.find(t => t.id == defaults.defaultType);
                    if (defType) {
                        state.selectedTypeId = defType.id;
                        state.selectedTypeName = defType.name;
                        await loadBrands(defType.id);

                        if (defaults.defaultBrand) {
                            const defBrand = state.brands.find(b => b.id == defaults.defaultBrand);
                            if (defBrand) {
                                state.selectedBrandId = defBrand.id;
                                state.selectedBrandName = defBrand.name;
                                await loadDevices(defType.id, defBrand.id);

                                if (defaults.defaultDevice) {
                                    const defDev = state.allDevices.find(d => d.id == defaults.defaultDevice);
                                    if (defDev) {
                                        addDevice(defDev);
                                        goToStep(4);
                                        return;
                                    }
                                }
                                goToStep(3);
                                return;
                            }
                        }
                        goToStep(2);
                        return;
                    }
                }
            } catch (err) {
                console.error('Booking init error:', err);
                hide('bookingLoading');
                show('bookingMain');
            }
        }

        // ─── Step Navigation ───
        function goToStep(step) {
            document.querySelectorAll('#bookingMain .step-card').forEach(el => el.style.display = 'none');
            const target = document.getElementById('step' + step);
            if (target) target.style.display = 'block';
            state.currentStep = step;
            updateProgress(step);
            window.scrollTo({ top: 200, behavior: 'smooth' });
        }

        function updateProgress(step) {
            const isUngrouped = state.config?.booking?.publicBookingMode === 'ungrouped';
            const steps = document.querySelectorAll('.progress-step');
            const connectors = document.querySelectorAll('.progress-connector');
            steps.forEach((el, i) => {
                const n = i + 1;
                // In ungrouped, step 1 is hidden; display number offset by -1
                const displayNum = isUngrouped ? n - 1 : n;
                el.classList.remove('active', 'current', 'done');
                if (isUngrouped && n === 1) return; // skip hidden step
                if (n < step) { el.classList.add('active', 'done'); el.querySelector('.progress-step-circle').textContent = '✓'; }
                else if (n === step) { el.classList.add('active', 'current'); el.querySelector('.progress-step-circle').textContent = displayNum; }
                else { el.querySelector('.progress-step-circle').textContent = displayNum; }
            });
            connectors.forEach((el, i) => {
                if (isUngrouped && i === 0) return; // skip hidden connector
                el.classList.toggle('active', i < step - 1);
            });
        }

        // ─── Step 1: Device Types ───
        async function loadDeviceTypes() {
            show('step1Loading');
            hide('deviceTypeGrid');
            hide('step1Empty');
            try {
                const data = await apiFetch('/device-types');
                state.deviceTypes = data.device_types || [];
                renderDeviceTypes();
            } catch (err) {
                console.error(err);
                el('step1Empty').textContent = 'Failed to load device types. Please refresh.';
                show('step1Empty');
            } finally {
                hide('step1Loading');
            }
        }

        const TYPE_ICONS = {
            'phone': '📱', 'smartphone': '📱', 'mobile': '📱',
            'tablet': '📋', 'ipad': '📋',
            'laptop': '💻', 'notebook': '💻',
            'desktop': '🖥️', 'pc': '🖥️', 'computer': '🖥️',
            'watch': '⌚', 'smartwatch': '⌚',
            'console': '🎮', 'game': '🎮', 'gaming': '🎮',
            'camera': '📷', 'drone': '🚁', 'printer': '🖨️',
            'speaker': '🔊', 'headphone': '🎧', 'earbuds': '🎧',
            'tv': '📺', 'television': '📺', 'monitor': '🖥️',
        };

        const TYPE_COLORS = [
            'rgba(253,103,66,.08)', 'rgba(6,62,112,.06)', 'rgba(43,138,62,.06)',
            'rgba(112,72,232,.06)', 'rgba(25,113,194,.06)', 'rgba(230,119,0,.06)',
            'rgba(194,25,113,.06)', 'rgba(59,130,246,.06)',
        ];

        function guessIcon(name) {
            const lower = (name || '').toLowerCase();
            for (const [key, icon] of Object.entries(TYPE_ICONS)) {
                if (lower.includes(key)) return icon;
            }
            return '📦';
        }

        function renderDeviceTypes() {
            const grid = el('deviceTypeGrid');
            if (state.deviceTypes.length === 0) {
                show('step1Empty');
                return;
            }
            const isImages = state.uiStyle === 'images';
            if (isImages) grid.classList.add('selection-grid-images');
            grid.innerHTML = state.deviceTypes.map((t, i) => {
                const bg = TYPE_COLORS[i % TYPE_COLORS.length];
                if (isImages && t.image_url) {
                    return '<div class="selection-card selection-card-image" data-type-id="' + t.id + '" onclick="RB.selectType(' + t.id + ',this)">'
                        + '<div class="selection-card-img"><img src="' + esc(t.image_url) + '" alt="' + esc(t.name) + '" /></div>'
                        + '<div class="selection-card-name">' + esc(t.name) + '</div>'
                        + (t.description ? '<div class="selection-card-sub">' + esc(t.description) + '</div>' : '')
                        + '</div>';
                }
                const icon = t.image_url
                    ? '<img src="' + esc(t.image_url) + '" alt="" style="width:28px;height:28px;object-fit:contain" />'
                    : guessIcon(t.name);
                return '<div class="selection-card" data-type-id="' + t.id + '" onclick="RB.selectType(' + t.id + ',this)">'
                    + '<div class="selection-card-icon" style="background:' + bg + '">' + icon + '</div>'
                    + '<div class="selection-card-name">' + esc(t.name) + '</div>'
                    + (t.description ? '<div class="selection-card-sub">' + esc(t.description) + '</div>' : '')
                    + '</div>';
            }).join('');
            show('deviceTypeGrid');
        }

        async function selectType(typeId, cardEl) {
            if (cardEl) {
                cardEl.closest('.selection-grid').querySelectorAll('.selection-card').forEach(c => c.classList.remove('selected'));
                cardEl.classList.add('selected');
            }
            const type = state.deviceTypes.find(t => t.id === typeId);
            state.selectedTypeId = typeId;
            state.selectedTypeName = type ? type.name : '';
            goToStep(2);
            await loadBrands(typeId);
        }

        // ─── Step 2: Brands ───
        async function loadBrands(typeId) {
            show('step2Loading');
            hide('brandGrid');
            hide('step2Empty');
            try {
                const params = typeId ? '?typeId=' + encodeURIComponent(typeId) : '';
                const data = await apiFetch('/brands' + params);
                state.brands = data.brands || [];
                renderBrands();
            } catch (err) {
                console.error(err);
                el('step2Empty').textContent = 'Failed to load brands. Please try again.';
                show('step2Empty');
            } finally {
                hide('step2Loading');
            }
        }

        function renderBrands() {
            const grid = el('brandGrid');
            const showOtherBrand = !state.config?.booking?.turnOffOtherDeviceBrand;
            if (state.brands.length === 0 && !showOtherBrand) {
                show('step2Empty');
                return;
            }
            const isImages = state.uiStyle === 'images';
            if (isImages) grid.classList.add('selection-grid-images');
            let brandsHtml = state.brands.map((b, i) => {
                const bg = TYPE_COLORS[i % TYPE_COLORS.length];
                if (isImages && b.image_url) {
                    return '<div class="selection-card selection-card-image" data-brand-id="' + b.id + '" onclick="RB.selectBrand(' + b.id + ',this)">'
                        + '<div class="selection-card-img"><img src="' + esc(b.image_url) + '" alt="' + esc(b.name) + '" /></div>'
                        + '<div class="selection-card-name">' + esc(b.name) + '</div>'
                        + '</div>';
                }
                const icon = b.image_url
                    ? '<img src="' + esc(b.image_url) + '" alt="" style="width:28px;height:28px;object-fit:contain" />'
                    : '🏷️';
                return '<div class="selection-card" data-brand-id="' + b.id + '" onclick="RB.selectBrand(' + b.id + ',this)">'
                    + '<div class="selection-card-icon" style="background:' + bg + '">' + icon + '</div>'
                    + '<div class="selection-card-name">' + esc(b.name) + '</div>'
                    + '</div>';
            }).join('');
            if (showOtherBrand) {
                brandsHtml += '<div class="selection-card" data-brand-id="__other__" style="border-style:dashed" onclick="RB.selectBrand(\'__other__\',this)">'
                    + '<div class="selection-card-icon" style="background:rgba(0,0,0,.04);color:#555;font-size:20px">❓</div>'
                    + '<div class="selection-card-name">Other</div>'
                    + '</div>';
            }
            grid.innerHTML = brandsHtml;
            show('brandGrid');
        }

        async function selectBrand(brandId, cardEl) {
            if (cardEl) {
                cardEl.closest('.selection-grid').querySelectorAll('.selection-card').forEach(c => c.classList.remove('selected'));
                cardEl.classList.add('selected');
            }
            state.selectedBrandId = brandId;
            if (brandId === '__other__') {
                state.selectedBrandName = 'Other';
                goToStep(3);
                state.allDevices = [];
                state.filteredDevices = [];
                hide('step3Loading');
                hide('step3Empty');
                el('deviceSearch').value = '';
                renderDeviceGrid();
                return;
            }
            const brand = state.brands.find(b => b.id === brandId);
            state.selectedBrandName = brand ? brand.name : '';
            goToStep(3);
            await loadDevices(state.selectedTypeId, brandId);
        }

        // ─── Step 3: Devices ───
        async function loadDevices(typeId, brandId) {
            show('step3Loading');
            hide('deviceGrid');
            hide('step3Empty');
            el('deviceSearch').value = '';
            try {
                const params = new URLSearchParams();
                if (typeId) params.set('typeId', typeId);
                if (brandId) params.set('brandId', brandId);
                const data = await apiFetch('/devices?' + params.toString());
                state.allDevices = data.devices || [];
                state.filteredDevices = [...state.allDevices];
                renderDeviceGrid();
            } catch (err) {
                console.error(err);
                el('step3Empty').textContent = 'Failed to load devices. Please try again.';
                show('step3Empty');
            } finally {
                hide('step3Loading');
            }
        }

        function filterDevices(query) {
            const q = (query || '').toLowerCase().trim();
            state.filteredDevices = q === ''
                ? [...state.allDevices]
                : state.allDevices.filter(d => (d.model || '').toLowerCase().includes(q));
            renderDeviceGrid();
        }

        function renderDeviceGrid() {
            const grid = el('deviceGrid');
            const showOtherDevice = !state.config?.booking?.turnOffOtherDeviceBrand;
            if (state.filteredDevices.length === 0 && !showOtherDevice) {
                hide('deviceGrid');
                show('step3Empty');
                el('step3Empty').textContent = state.allDevices.length === 0
                    ? 'No devices found for this brand.'
                    : 'No devices match your search.';
                return;
            }
            hide('step3Empty');
            const alreadySelected = state.selectedDevices.map(d => d.device_id);
            const isImages = state.uiStyle === 'images';
            if (isImages) grid.classList.add('selection-grid-images');
            let devicesHtml = state.filteredDevices.map(d => {
                const sel = alreadySelected.includes(d.id) ? ' selected' : '';
                const isOther = d.is_other;
                const style = isOther ? ' style="border-style:dashed"' : '';

                if (isImages && d.image_url) {
                    return '<div class="selection-card selection-card-image' + sel + '"' + style + ' data-device-id="' + d.id + '" onclick="RB.toggleDevice(' + d.id + ',this)">'
                        + '<div class="selection-card-img"><img src="' + esc(d.image_url) + '" alt="' + esc(d.model) + '" /></div>'
                        + '<div class="selection-card-name">' + esc(d.model) + '</div>'
                        + '</div>';
                }

                const icon = isOther ? '❓' : '📱';
                return '<div class="selection-card' + sel + '"' + style + ' data-device-id="' + d.id + '" onclick="RB.toggleDevice(' + d.id + ',this)">'
                    + '<div class="selection-card-icon" style="background:rgba(0,0,0,.04);color:#333;font-size:16px">' + icon + '</div>'
                    + '<div class="selection-card-name">' + esc(d.model) + '</div>'
                    + '</div>';
            }).join('');
            if (showOtherDevice) {
                devicesHtml += '<div class="selection-card rb-add-other-btn" style="border-style:dashed;cursor:pointer" onclick="RB.addOtherDevice()">'
                    + '<div class="selection-card-icon" style="background:rgba(0,0,0,.04);color:#555;font-size:20px">❓</div>'
                    + '<div class="selection-card-name">Other Device</div>'
                    + '<div style="font-size:10px;color:var(--rb-text-3);margin-top:2px">Tap to add</div>'
                    + '</div>';
            }
            grid.innerHTML = devicesHtml;
            show('deviceGrid');
        }

        function toggleDevice(deviceId, cardEl) {
            const existing = state.selectedDevices.findIndex(d => d.device_id === deviceId);
            if (existing >= 0) {
                state.selectedDevices.splice(existing, 1);
                if (cardEl) cardEl.classList.remove('selected');
            } else {
                const dev = state.allDevices.find(d => d.id === deviceId);
                if (dev) addDevice(dev);
                if (cardEl) cardEl.classList.add('selected');
            }
            renderSelectedDevices();
        }

        function addOtherDevice() {
            state.otherDeviceCounter++;
            const otherId = '__other_' + state.otherDeviceCounter + '__';
            addDevice({ id: otherId, model: 'Other Device', is_other: true, image_url: null });
            renderSelectedDevices();
        }

        function addDevice(dev) {
            if (state.selectedDevices.find(d => d.device_id === dev.id)) return;
            state.selectedDevices.push({
                device_id: dev.id,
                model: dev.model,
                is_other: dev.is_other || false,
                device_label: dev.is_other ? '' : dev.model,
                serial: '',
                pin: '',
                notes: '',
                extra_fields: state.deviceFieldDefs.map(f => ({ key: f.key, label: f.label, value_text: '' })),
                services: [],
                other_service: '',
            });
            renderSelectedDevices();
        }

        function removeDevice(deviceId) {
            state.selectedDevices = state.selectedDevices.filter(d => d.device_id !== deviceId);
            // Un-highlight in grid
            const card = el('deviceGrid')?.querySelector('[data-device-id="' + deviceId + '"]');
            if (card) card.classList.remove('selected');
            renderSelectedDevices();
        }

        function renderSelectedDevices() {
            const container = el('selectedDevices');
            const list = el('deviceList');
            const count = el('deviceCount');
            if (state.selectedDevices.length === 0) { hide('selectedDevices'); return; }
            show('selectedDevices');
            count.textContent = state.selectedDevices.length;

            const cfg = state.config?.booking || {};
            const hideImei = cfg.turnOffIdImeiInBooking;
            const showPin = state.config?.devicesBrands?.enablePinCodeField;
            const customLabels = state.config?.devicesBrands?.labels || {};

            list.innerHTML = state.selectedDevices.map((d, i) => {
                const serialLabel = customLabels.imei_serial || 'IMEI / Serial Number';
                const pinLabel = customLabels.pin || 'Pin / Passcode';
                const notesLabel = customLabels.notes || 'Device Notes';

                let fieldsHtml = '';
                if (d.is_other) {
                    fieldsHtml += '<div class="sd-field full"><label>Device Name <span class="req">*</span></label>'
                        + '<input type="text" placeholder="e.g. iPhone 15 Pro Max" value="' + esc(d.device_label) + '" onchange="RB.updateDeviceField(' + i + ',\'device_label\',this.value)" /></div>';
                }
                if (!hideImei) {
                    fieldsHtml += '<div class="sd-field"><label>' + esc(serialLabel) + '</label>'
                        + '<input type="text" placeholder="e.g. 353456789012345" value="' + esc(d.serial) + '" onchange="RB.updateDeviceField(' + i + ',\'serial\',this.value)" /></div>';
                }
                if (showPin) {
                    fieldsHtml += '<div class="sd-field"><label>' + esc(pinLabel) + '</label>'
                        + '<input type="text" placeholder="Device unlock code" value="' + esc(d.pin) + '" onchange="RB.updateDeviceField(' + i + ',\'pin\',this.value)" /></div>';
                }
                // Dynamic fields from device field definitions
                d.extra_fields.forEach((ef, fi) => {
                    fieldsHtml += '<div class="sd-field sd-field-dynamic"><label>' + esc(ef.label) + '</label>'
                        + '<input type="text" placeholder="" value="' + esc(ef.value_text) + '" onchange="RB.updateExtraField(' + i + ',' + fi + ',this.value)" /></div>';
                });
                fieldsHtml += '<div class="sd-field full"><label>' + esc(notesLabel) + '</label>'
                    + '<textarea rows="2" placeholder="Any additional notes about this device…" onchange="RB.updateDeviceField(' + i + ',\'notes\',this.value)">' + esc(d.notes) + '</textarea></div>';

                const removeIdStr = typeof d.device_id === 'string' ? "'" + d.device_id + "'" : d.device_id;
                return '<div class="selected-device">'
                    + '<div class="selected-device-header" onclick="RB.toggleDeviceFields(' + i + ')">'
                    + '<div class="selected-device-name">📱 ' + esc(d.model) + '</div>'
                    + '<div class="selected-device-actions">'
                    + '<span class="selected-device-toggle open" id="toggle-' + i + '">▼</span>'
                    + '<button class="selected-device-remove" onclick="event.stopPropagation();RB.removeDevice(' + removeIdStr + ')">✕</button>'
                    + '</div></div>'
                    + '<div class="selected-device-fields" id="fields-' + i + '">' + fieldsHtml + '</div>'
                    + '</div>';
            }).join('');
        }

        function updateDeviceField(idx, field, value) {
            if (state.selectedDevices[idx]) state.selectedDevices[idx][field] = value;
        }

        function updateDeviceOtherService(idx, value) {
            if (state.selectedDevices[idx]) {
                state.selectedDevices[idx].other_service = value;
            }
        }

        function updateExtraField(idx, fieldIdx, value) {
            if (state.selectedDevices[idx] && state.selectedDevices[idx].extra_fields[fieldIdx]) {
                state.selectedDevices[idx].extra_fields[fieldIdx].value_text = value;
            }
        }

        function toggleDeviceFields(idx) {
            const fields = document.getElementById('fields-' + idx);
            const toggle = document.getElementById('toggle-' + idx);
            if (fields) fields.classList.toggle('collapsed');
            if (toggle) toggle.classList.toggle('open');
        }

        // ─── Step 4: Services ───
        async function loadServicesForDevices() {
            show('step4Loading');
            hide('serviceDeviceEntries');
            hide('step4Actions');

            const mode = state.config?.booking?.publicBookingMode || 'ungrouped';
            const promises = state.selectedDevices.map(async (dev) => {
                if (state.servicesCache[dev.device_id]) return;
                // Virtual "Other" devices have no catalog services — skip API call
                if (typeof dev.device_id === 'string' && dev.device_id.startsWith('__other_')) {
                    state.servicesCache[dev.device_id] = { mode, services: [], groups: [] };
                    return;
                }
                try {
                    const data = await apiFetch('/services?deviceId=' + dev.device_id + '&mode=' + mode);
                    state.servicesCache[dev.device_id] = data;
                } catch (err) {
                    console.error('Failed to load services for device', dev.device_id, err);
                    state.servicesCache[dev.device_id] = { mode, services: [], groups: [] };
                }
            });
            await Promise.all(promises);

            renderServiceEntries();
            hide('step4Loading');
            show('serviceDeviceEntries');
            show('step4Actions');

            // Show appointment section if settings available
            if (state.appointmentSettings.length > 0) {
                renderAppointmentTypes();
                show('appointmentSection');
            }

            // Other service inputs are rendered per-device inside renderServiceEntries()
        }

        function renderServiceEntries() {
            const container = el('serviceDeviceEntries');
            const showPrice = !state.config?.booking?.turnOffServicePrice;
            const mode = state.config?.booking?.publicBookingMode || 'ungrouped';

            container.innerHTML = state.selectedDevices.map((dev, devIdx) => {
                const cache = state.servicesCache[dev.device_id] || {};
                let servicesHtml = '';

                if (mode === 'grouped' || mode === 'warranty') {
                    const groups = cache.groups || [];
                    if (groups.length === 0) {
                        servicesHtml = '<p class="rb-empty-state" style="margin:12px 0">No services available for this device.</p>';
                    } else {
                        servicesHtml = groups.map(g => {
                            const activeServices = (g.services || []).filter(s => s.is_active);
                            if (activeServices.length === 0) return '';
                            const typeName = g.service_type ? g.service_type.name : 'Uncategorized';
                            return '<div class="service-category">'
                                + '<div class="service-category-title">' + esc(typeName) + ' <span class="badge">' + activeServices.length + '</span></div>'
                                + '<div class="service-grid">'
                                + activeServices.map(s => renderServiceCard(s, devIdx, showPrice)).join('')
                                + '</div></div>';
                        }).join('');
                    }
                } else {
                    const services = (cache.services || []).filter(s => s.is_active);
                    if (services.length === 0) {
                        servicesHtml = '<p class="rb-empty-state" style="margin:12px 0">No services available for this device.</p>';
                    } else {
                        servicesHtml = '<div class="service-grid">'
                            + services.map(s => renderServiceCard(s, devIdx, showPrice)).join('')
                            + '</div>';
                    }
                }

                const showOtherSvc = !state.config?.booking?.turnOffOtherService;
                const otherSvcHtml = showOtherSvc
                    ? '<div class="other-service-row">'
                    + '<label style="font-size:13px;color:var(--rb-text-2);display:block;margin-bottom:4px">Or describe a service not listed</label>'
                    + '<input type="text" class="rb-other-svc-input" placeholder="e.g. Water damage repair" maxlength="255"'
                    + ' value="' + esc(dev.other_service || '') + '"'
                    + ' oninput="RB.updateDeviceOtherService(' + devIdx + ',this.value)" />'
                    + '</div>'
                    : '';

                const selectedCount = dev.services.length + (dev.other_service ? 1 : 0);
                const badgeHtml = selectedCount > 0
                    ? ' <span class="device-entry-badge">' + selectedCount + ' selected</span>'
                    : '';

                return '<div class="device-entry">'
                    + '<div class="device-entry-header" onclick="this.parentElement.querySelector(\'.device-entry-body\').classList.toggle(\'collapsed\')">'
                    + '<div class="device-entry-name" id="deviceEntryName-' + devIdx + '">📱 ' + esc(dev.model) + badgeHtml + '</div>'
                    + '<span style="color:var(--rb-text-3)">▼</span></div>'
                    + '<div class="device-entry-body">' + servicesHtml + otherSvcHtml + '</div>'
                    + '</div>';
            }).join('');
        }

        function renderServiceCard(service, devIdx, showPrice) {
            const dev = state.selectedDevices[devIdx];
            const isSelected = dev.services.some(s => s.service_id === service.id);
            const selClass = isSelected ? ' selected' : '';
            const priceHtml = showPrice && service.price
                ? '<div class="service-card-price">' + formatPrice(service.price.amount_cents, service.price.currency) + '</div>'
                : '';

            return '<div class="service-card' + selClass + '" data-service-id="' + service.id + '" onclick="RB.toggleService(' + devIdx + ',' + service.id + ',this)">'
                + '<div class="service-card-check">✓</div>'
                + '<div class="service-card-body"><div class="service-card-name">' + esc(service.name) + '</div>'
                + (service.description ? '<div class="service-card-desc">' + esc(service.description) + '</div>' : '')
                + '</div>' + priceHtml + '</div>';
        }

        function toggleService(devIdx, serviceId, cardEl) {
            const dev = state.selectedDevices[devIdx];
            if (!dev) return;
            const existIdx = dev.services.findIndex(s => s.service_id === serviceId);
            if (existIdx >= 0) {
                dev.services.splice(existIdx, 1);
                if (cardEl) cardEl.classList.remove('selected');
            } else {
                // Find service data
                const cache = state.servicesCache[dev.device_id] || {};
                let svc = null;
                if (cache.services) svc = cache.services.find(s => s.id === serviceId);
                if (!svc && cache.groups) {
                    for (const g of cache.groups) {
                        svc = (g.services || []).find(s => s.id === serviceId);
                        if (svc) break;
                    }
                }
                if (svc) {
                    dev.services.push({
                        service_id: svc.id,
                        name: svc.name,
                        price: svc.price,
                    });
                }
                if (cardEl) cardEl.classList.add('selected');
            }
            // Update badge
            const nameEl = el('deviceEntryName-' + devIdx);
            if (nameEl) {
                const count = dev.services.length;
                const badge = count > 0 ? ' <span class="device-entry-badge">' + count + ' selected</span>' : '';
                nameEl.innerHTML = '📱 ' + esc(dev.model) + badge;
            }
        }

        // ─── Appointment ───
        function renderAppointmentTypes() {
            const container = el('appointmentTypes');
            container.innerHTML = state.appointmentSettings.map(s =>
                '<div class="appt-option" data-setting-id="' + s.id + '" onclick="RB.selectAppointmentType(' + s.id + ',this)">'
                + esc(s.title) + (s.slot_duration_minutes ? ' (' + s.slot_duration_minutes + ' min)' : '')
                + '</div>'
            ).join('');
        }

        function selectAppointmentType(settingId, cardEl) {
            document.querySelectorAll('.appt-option').forEach(e => e.classList.remove('selected'));
            if (state.selectedAppointment.setting_id === settingId) {
                state.selectedAppointment = { setting_id: null, date: null, time_slot: null };
                hide('appointmentDateTime');
                return;
            }
            if (cardEl) cardEl.classList.add('selected');
            state.selectedAppointment.setting_id = settingId;
            state.selectedAppointment.date = null;
            state.selectedAppointment.time_slot = null;

            // Set min date to today
            const dateInput = el('appointmentDate');
            const today = new Date().toISOString().split('T')[0];
            dateInput.min = today;
            dateInput.value = '';
            el('appointmentTimeSlots').innerHTML = '';
            show('appointmentDateTime');
        }

        function onAppointmentDateChange() {
            const date = el('appointmentDate').value;
            state.selectedAppointment.date = date || null;
            state.selectedAppointment.time_slot = null;

            const setting = state.appointmentSettings.find(s => s.id === state.selectedAppointment.setting_id);
            if (!setting || !date) { el('appointmentTimeSlots').innerHTML = ''; return; }

            const slots = setting.time_slots || [];
            if (slots.length === 0) {
                el('appointmentTimeSlots').innerHTML = '<div style="font-size:13px;color:var(--rb-text-3)">No time slots configured.</div>';
                return;
            }

            // Get day of week from selected date (e.g., 'monday', 'tuesday')
            const dateObj = new Date(date + 'T00:00:00');
            const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            const dayOfWeek = dayNames[dateObj.getDay()];

            // Find the slot configuration for this day
            const daySlot = slots.find(s => s && s.day === dayOfWeek && s.enabled !== false);
            if (!daySlot || !daySlot.start || !daySlot.end) {
                el('appointmentTimeSlots').innerHTML = '<div style="font-size:13px;color:var(--rb-text-3)">No availability for this day.</div>';
                return;
            }

            // Generate time slots from start to end
            const duration = setting.slot_duration_minutes || 30;
            const buffer = setting.buffer_minutes || 0;
            const startParts = daySlot.start.split(':');
            const endParts = daySlot.end.split(':');
            let currentMinutes = parseInt(startParts[0], 10) * 60 + parseInt(startParts[1], 10);
            const endMinutes = parseInt(endParts[0], 10) * 60 + parseInt(endParts[1], 10);

            const timeSlotButtons = [];
            while (currentMinutes + duration <= endMinutes) {
                const h = String(Math.floor(currentMinutes / 60)).padStart(2, '0');
                const m = String(currentMinutes % 60).padStart(2, '0');
                const endH = String(Math.floor((currentMinutes + duration) / 60)).padStart(2, '0');
                const endM = String((currentMinutes + duration) % 60).padStart(2, '0');
                const slotValue = h + ':' + m;
                const slotLabel = h + ':' + m + ' - ' + endH + ':' + endM;

                timeSlotButtons.push('<div class="time-slot" data-slot="' + esc(slotValue) + '" onclick="RB.selectTimeSlot(\'' + esc(slotValue) + '\',this)">' + esc(slotLabel) + '</div>');
                currentMinutes += duration + buffer;
            }

            el('appointmentTimeSlots').innerHTML = timeSlotButtons.length === 0
                ? '<div style="font-size:13px;color:var(--rb-text-3)">No available time slots.</div>'
                : timeSlotButtons.join('');
        }

        function selectTimeSlot(slot, cardEl) {
            document.querySelectorAll('.time-slot').forEach(e => e.classList.remove('selected'));
            if (cardEl) cardEl.classList.add('selected');
            state.selectedAppointment.time_slot = slot;
        }

        // ─── Step 4 → 5 Validation ───
        function validateAndGoToStep5() {
            // Check each device has at least one service or other_service filled in
            for (const dev of state.selectedDevices) {
                if (dev.services.length === 0 && (!dev.other_service || dev.other_service.trim() === '')) {
                    showAlert('Please select at least one service for ' + dev.model + ', or describe the service you need.');
                    return;
                }
                if (dev.is_other && (!dev.device_label || dev.device_label.trim() === '')) {
                    showAlert('Please enter a device name for your "Other" device.');
                    return;
                }
            }
            renderSummary();
            goToStep(5);
        }

        // ─── Step 5: Summary ───
        function renderSummary() {
            const showPrice = !state.config?.booking?.turnOffServicePrice;
            let totalCents = 0;
            let currency = null;

            el('summaryItems').innerHTML = state.selectedDevices.map(dev => {
                const svcNames = dev.services.map(s => {
                    if (showPrice && s.price) {
                        totalCents += s.price.amount_cents || 0;
                        currency = currency || s.price.currency;
                    }
                    return s.name;
                });
                if (dev.other_service) svcNames.push(dev.other_service);

                return '<div class="summary-item"><span class="summary-device">📱 ' + esc(dev.model) + '</span>'
                    + '<span class="summary-service">' + esc(svcNames.join(', ') || 'No service selected') + '</span></div>';
            }).join('');

            if (showPrice && totalCents > 0 && currency) {
                el('summaryTotal').innerHTML = '<div class="summary-item" style="font-weight:700"><span>Estimated Total</span><span>' + formatPrice(totalCents, currency) + '</span></div>';
                show('summaryTotal');
            } else {
                hide('summaryTotal');
            }
        }

        // ─── Submit ───
        async function submitBooking() {
            if (state.submitting) return;

            // Validate
            const errors = [];
            const firstName = el('customerFirstName').value.trim();
            const lastName = el('customerLastName').value.trim();
            const email = el('customerEmail').value.trim();
            const jobDetailsVal = el('jobDetails').value.trim();

            if (!firstName) errors.push('First name is required.');
            if (!lastName) errors.push('Last name is required.');
            if (!email) errors.push('Email is required.');
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Please enter a valid email address.');
            if (!jobDetailsVal) errors.push('Job details are required.');

            if (state.config?.booking?.publicBookingMode === 'warranty') {
                if (!el('warrantyDate').value) errors.push('Date of purchase is required for warranty bookings.');
            }

            const gdprRequired = state.config?.general?.gdprAcceptanceText && state.config.general.gdprAcceptanceText.trim() !== '';
            if (gdprRequired && !el('gdprCheckbox').checked) {
                errors.push('You must accept the privacy policy to continue.');
            }

            if (state.selectedDevices.length === 0) errors.push('Please select at least one device.');

            for (const dev of state.selectedDevices) {
                if (dev.services.length === 0 && !dev.other_service) {
                    errors.push('Select at least one service for ' + dev.model + '.');
                }
                if (dev.is_other && (!dev.device_label || dev.device_label.trim() === '')) {
                    errors.push('Enter a name for your "Other" device.');
                }
            }

            if (errors.length > 0) {
                showErrors(errors);
                return;
            }

            // Build payload
            const payload = {
                mode: state.config?.booking?.publicBookingMode || 'ungrouped',
                gdprAccepted: el('gdprCheckbox')?.checked || false,
                jobDetails: jobDetailsVal,
                customer: {
                    firstName: firstName,
                    lastName: lastName,
                    userEmail: email,
                    phone: el('customerPhone').value.trim() || null,
                    company: el('customerCompany').value.trim() || null,
                    taxId: el('customerTaxId').value.trim() || null,
                    addressLine1: el('customerAddress').value.trim() || null,
                    city: el('customerCity').value.trim() || null,
                    postalCode: el('customerPostal').value.trim() || null,
                },
                devices: state.selectedDevices.map(dev => ({
                    device_id: dev.is_other ? null : dev.device_id,
                    device_label: dev.device_label || dev.model,
                    serial: dev.serial || null,
                    pin: dev.pin || null,
                    notes: dev.notes || null,
                    extra_fields: dev.extra_fields.filter(ef => ef.value_text && ef.value_text.trim() !== ''),
                    services: dev.services.map(s => ({ service_id: s.service_id, qty: 1 })),
                    other_service: dev.other_service || null,
                })),
            };

            if (state.config?.booking?.publicBookingMode === 'warranty') {
                payload.warranty = { dateOfPurchase: el('warrantyDate').value || null };
            }

            if (state.selectedAppointment.setting_id && state.selectedAppointment.date && state.selectedAppointment.time_slot) {
                payload.appointment = {
                    appointment_setting_id: state.selectedAppointment.setting_id,
                    date: state.selectedAppointment.date,
                    time_slot: state.selectedAppointment.time_slot,
                };
            }

            // Submit
            state.submitting = true;
            hide('submitErrors');
            el('submitBtn').disabled = true;
            hide('submitBtnText');
            show('submitBtnSpinner');

            try {
                const formData = new FormData();
                formData.append('payload_json', JSON.stringify(payload));

                // Attachments
                const fileInput = el('attachments');
                if (fileInput && fileInput.files) {
                    const files = Array.from(fileInput.files).slice(0, 5);
                    files.forEach(f => formData.append('attachments[]', f));
                }

                const res = await fetch(API_BASE + '/submit', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: formData,
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    if (res.status === 422 && data.errors) {
                        const msgs = [];
                        for (const key of Object.keys(data.errors)) {
                            msgs.push(...data.errors[key]);
                        }
                        showErrors(msgs);
                    } else {
                        showErrors([data.message || 'Something went wrong. Please try again.']);
                    }
                    return;
                }

                // Success
                el('successCaseNumber').textContent = data.case_number || '';
                el('statusCheckLink').href = data.status_check_url || '#';
                document.querySelectorAll('#bookingMain .step-card').forEach(el => el.style.display = 'none');
                show('stepSuccess');
                el('progressBar').style.display = 'none';
                window.scrollTo({ top: 0, behavior: 'smooth' });

            } catch (err) {
                console.error('Submit error:', err);
                showErrors(['Network error. Please check your connection and try again.']);
            } finally {
                state.submitting = false;
                el('submitBtn').disabled = false;
                show('submitBtnText');
                hide('submitBtnSpinner');
            }
        }

        function showErrors(errors) {
            const box = el('submitErrors');
            box.innerHTML = errors.map(e => '<div>' + esc(e) + '</div>').join('');
            show('submitErrors');
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function showAlert(msg) {
            const box = el('submitErrors');
            if (box) {
                box.innerHTML = '<div>' + esc(msg) + '</div>';
                show('submitErrors');
                box.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => hide('submitErrors'), 5000);
            }
        }

        // ─── Step transitions with data loading ───
        const originalGoToStep = goToStep;
        function stepAwareGoToStep(step) {
            // In ungrouped mode, prevent navigating to step 1
            if (step === 1 && state.config?.booking?.publicBookingMode === 'ungrouped') {
                step = 2;
            }
            if (step === 4 && state.selectedDevices.length > 0) {
                originalGoToStep(step);
                loadServicesForDevices();
                return;
            }
            originalGoToStep(step);
        }

        // ─── Attachment preview ───
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = el('attachments');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const list = el('attachmentList');
                    const files = Array.from(this.files).slice(0, 5);
                    if (files.length === 0) { list.innerHTML = ''; return; }
                    list.innerHTML = files.map(f => esc(f.name) + ' (' + (f.size / 1024).toFixed(1) + ' KB)').join('<br>');
                    if (this.files.length > 5) {
                        list.innerHTML += '<br><span style="color:var(--rb-orange)">Only the first 5 files will be uploaded.</span>';
                    }
                });
            }
        });

        // ─── Public API ───
        window.RB = {
            goToStep: stepAwareGoToStep,
            selectType,
            selectBrand,
            toggleDevice,
            addOtherDevice,
            removeDevice,
            toggleDeviceFields,
            updateDeviceField,
            updateDeviceOtherService,
            updateExtraField,
            filterDevices,
            toggleService,
            selectAppointmentType,
            onAppointmentDateChange,
            selectTimeSlot,
            validateAndGoToStep5,
            submitBooking,
        };

        // Boot
        init();
    })();
    </script>
</body>
</html>
