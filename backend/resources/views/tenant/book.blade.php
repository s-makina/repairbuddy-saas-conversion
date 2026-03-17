@php
    $tenantSlug = $tenantSlug ?? $business ?? null;
    $tenant = $tenant ?? null;
    $activePage = 'book';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $tenant->name ?? 'RepairBuddy' }} — Book a Repair</title>
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

    <!-- BOOKING -->
    <div class="booking-page">
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
            <div class="selection-grid">
                <div class="selection-card" onclick="selectCard(this);goToStep(2)">
                    <div class="selection-card-icon" style="background:rgba(253,103,66,.08);color:var(--rb-orange)">📱</div>
                    <div class="selection-card-name">Smartphone</div><div class="selection-card-sub">iPhone, Samsung, etc.</div>
                </div>
                <div class="selection-card" onclick="selectCard(this);goToStep(2)">
                    <div class="selection-card-icon" style="background:rgba(6,62,112,.06);color:var(--rb-blue)">📋</div>
                    <div class="selection-card-name">Tablet</div><div class="selection-card-sub">iPad, Galaxy Tab, etc.</div>
                </div>
                <div class="selection-card" onclick="selectCard(this);goToStep(2)">
                    <div class="selection-card-icon" style="background:rgba(43,138,62,.06);color:#2b8a3e">💻</div>
                    <div class="selection-card-name">Laptop</div><div class="selection-card-sub">MacBook, Dell, HP, etc.</div>
                </div>
                <div class="selection-card" onclick="selectCard(this);goToStep(2)">
                    <div class="selection-card-icon" style="background:rgba(112,72,232,.06);color:#7048e8">🖥️</div>
                    <div class="selection-card-name">Desktop</div><div class="selection-card-sub">PC, iMac, etc.</div>
                </div>
                <div class="selection-card" onclick="selectCard(this);goToStep(2)">
                    <div class="selection-card-icon" style="background:rgba(25,113,194,.06);color:#1971c2">⌚</div>
                    <div class="selection-card-name">Smartwatch</div><div class="selection-card-sub">Apple Watch, etc.</div>
                </div>
                <div class="selection-card" onclick="selectCard(this);goToStep(2)">
                    <div class="selection-card-icon" style="background:rgba(230,119,0,.06);color:#e67700">🎮</div>
                    <div class="selection-card-name">Game Console</div><div class="selection-card-sub">PS5, Xbox, Switch</div>
                </div>
            </div>
        </div>

        <!-- STEP 2: Brand -->
        <div class="step-card" id="step2" style="display:none">
            <div class="step-header"><h2>Select Brand</h2><p>Which manufacturer or brand?</p></div>
            <button class="btn-back" onclick="goToStep(1)">← Back to Device Types</button>
            <div class="selection-grid" style="margin-top:20px">
                <div class="selection-card" onclick="selectCard(this);goToStep(3)">
                    <div class="selection-card-icon" style="background:rgba(0,0,0,.04);color:#333">🍎</div>
                    <div class="selection-card-name">Apple</div>
                </div>
                <div class="selection-card" onclick="selectCard(this);goToStep(3)">
                    <div class="selection-card-icon" style="background:rgba(6,62,112,.06);color:var(--rb-blue)">📱</div>
                    <div class="selection-card-name">Samsung</div>
                </div>
                <div class="selection-card" onclick="selectCard(this);goToStep(3)">
                    <div class="selection-card-icon" style="background:rgba(43,138,62,.06);color:#2b8a3e">📱</div>
                    <div class="selection-card-name">Google</div>
                </div>
                <div class="selection-card" onclick="selectCard(this);goToStep(3)">
                    <div class="selection-card-icon" style="background:rgba(253,103,66,.08);color:var(--rb-orange)">📱</div>
                    <div class="selection-card-name">Huawei</div>
                </div>
                <div class="selection-card" onclick="selectCard(this);goToStep(3)">
                    <div class="selection-card-icon" style="background:rgba(112,72,232,.06);color:#7048e8">📱</div>
                    <div class="selection-card-name">OnePlus</div>
                </div>
                <div class="selection-card" onclick="selectCard(this);goToStep(3)">
                    <div class="selection-card-icon" style="background:rgba(148,163,184,.1);color:var(--rb-text-3)">❓</div>
                    <div class="selection-card-name">Other</div>
                </div>
            </div>
        </div>

        <!-- STEP 3: Device -->
        <div class="step-card" id="step3" style="display:none">
            <div class="step-header"><h2>Select Device</h2><p>Search and select the device(s) that need repair.</p></div>
            <button class="btn-back" onclick="goToStep(2)">← Back to Brands</button>
            <div class="search-wrapper" style="margin-top:20px">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" placeholder="Search devices..." />
            </div>
            <div class="selection-grid selection-grid-compact">
                <div class="selection-card" onclick="addDevice(this,'iPhone 15 Pro')">
                    <div class="selection-card-icon" style="background:rgba(0,0,0,.04);color:#333;font-size:16px">📱</div>
                    <div class="selection-card-name">iPhone 15 Pro</div>
                </div>
                <div class="selection-card" onclick="addDevice(this,'iPhone 15')">
                    <div class="selection-card-icon" style="background:rgba(0,0,0,.04);color:#333;font-size:16px">📱</div>
                    <div class="selection-card-name">iPhone 15</div>
                </div>
                <div class="selection-card" onclick="addDevice(this,'iPhone 14 Pro')">
                    <div class="selection-card-icon" style="background:rgba(0,0,0,.04);color:#333;font-size:16px">📱</div>
                    <div class="selection-card-name">iPhone 14 Pro</div>
                </div>
                <div class="selection-card" onclick="addDevice(this,'iPhone 14')">
                    <div class="selection-card-icon" style="background:rgba(0,0,0,.04);color:#333;font-size:16px">📱</div>
                    <div class="selection-card-name">iPhone 14</div>
                </div>
                <div class="selection-card" onclick="addDevice(this,'iPhone 13 Pro')">
                    <div class="selection-card-icon" style="background:rgba(0,0,0,.04);color:#333;font-size:16px">📱</div>
                    <div class="selection-card-name">iPhone 13 Pro</div>
                </div>
                <div class="selection-card" onclick="addDevice(this,'iPhone 13')">
                    <div class="selection-card-icon" style="background:rgba(0,0,0,.04);color:#333;font-size:16px">📱</div>
                    <div class="selection-card-name">iPhone 13</div>
                </div>
                <div class="selection-card" onclick="addDevice(this,'iPhone SE')">
                    <div class="selection-card-icon" style="background:rgba(0,0,0,.04);color:#333;font-size:16px">📱</div>
                    <div class="selection-card-name">iPhone SE</div>
                </div>
                <div class="selection-card" onclick="addDevice(this,'Other')" style="border-style:dashed">
                    <div class="selection-card-icon" style="background:rgba(148,163,184,.1);color:var(--rb-text-3);font-size:16px">❓</div>
                    <div class="selection-card-name">Other</div>
                </div>
            </div>

            <!-- Selected Devices -->
            <div class="selected-devices" id="selectedDevices" style="display:none">
                <div class="selected-devices-title">✅ Selected Devices <span class="count" id="deviceCount">0</span></div>
                <div id="deviceList"></div>
                <div class="step-actions">
                    <div></div>
                    <button class="btn btn-primary btn-lg" onclick="goToStep(4)">Continue to Services →</button>
                </div>
            </div>
        </div>

        <!-- STEP 4: Services -->
        <div class="step-card" id="step4" style="display:none">
            <div class="step-header"><h2>Select Services</h2><p>Choose one or more services for each device.</p></div>
            <button class="btn-back" onclick="goToStep(3)">← Back to Devices</button>

            <div class="device-entry" style="margin-top:20px">
                <div class="device-entry-header">
                    <div class="device-entry-name">📱 iPhone 15 Pro <span class="device-entry-badge">2 selected</span></div>
                    <span style="color:var(--rb-text-3)">▼</span>
                </div>
                <div class="device-entry-body">
                    <div class="service-category">
                        <div class="service-category-title">Screen Repairs <span class="badge">3</span></div>
                        <div class="service-grid">
                            <div class="service-card selected" onclick="this.classList.toggle('selected')">
                                <div class="service-card-check">✓</div>
                                <div class="service-card-body"><div class="service-card-name">Screen Replacement</div><div class="service-card-desc">Full OLED screen replacement</div></div>
                                <div class="service-card-price">$189</div>
                            </div>
                            <div class="service-card" onclick="this.classList.toggle('selected')">
                                <div class="service-card-check">✓</div>
                                <div class="service-card-body"><div class="service-card-name">Glass Only Repair</div><div class="service-card-desc">Front glass replacement</div></div>
                                <div class="service-card-price">$89</div>
                            </div>
                            <div class="service-card" onclick="this.classList.toggle('selected')">
                                <div class="service-card-check">✓</div>
                                <div class="service-card-body"><div class="service-card-name">Screen Protector</div><div class="service-card-desc">Premium tempered glass</div></div>
                                <div class="service-card-price">$29</div>
                            </div>
                        </div>
                    </div>
                    <div class="service-category">
                        <div class="service-category-title">Battery & Power <span class="badge">2</span></div>
                        <div class="service-grid">
                            <div class="service-card selected" onclick="this.classList.toggle('selected')">
                                <div class="service-card-check">✓</div>
                                <div class="service-card-body"><div class="service-card-name">Battery Replacement</div><div class="service-card-desc">Genuine OEM battery</div></div>
                                <div class="service-card-price">$69</div>
                            </div>
                            <div class="service-card" onclick="this.classList.toggle('selected')">
                                <div class="service-card-check">✓</div>
                                <div class="service-card-body"><div class="service-card-name">Charging Port</div><div class="service-card-desc">Lightning port repair</div></div>
                                <div class="service-card-price">$49</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointment -->
            <div class="appointment-section">
                <h5>📅 Schedule an Appointment <span class="opt-badge">Optional</span></h5>
                <p>Select a preferred date and time for your visit.</p>
                <div class="appt-options">
                    <div class="appt-option selected" onclick="document.querySelectorAll('.appt-option').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">Walk-in (30 min)</div>
                    <div class="appt-option" onclick="document.querySelectorAll('.appt-option').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">Express (15 min)</div>
                </div>
                <div class="appt-datetime">
                    <div><label>Preferred Date</label><input type="date" /></div>
                    <div>
                        <label>Preferred Time</label>
                        <div class="time-slots">
                            <div class="time-slot" onclick="document.querySelectorAll('.time-slot').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">09:00</div>
                            <div class="time-slot" onclick="document.querySelectorAll('.time-slot').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">09:30</div>
                            <div class="time-slot selected" onclick="document.querySelectorAll('.time-slot').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">10:00</div>
                            <div class="time-slot" onclick="document.querySelectorAll('.time-slot').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">10:30</div>
                            <div class="time-slot" onclick="document.querySelectorAll('.time-slot').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">11:00</div>
                            <div class="time-slot" onclick="document.querySelectorAll('.time-slot').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">11:30</div>
                            <div class="time-slot" onclick="document.querySelectorAll('.time-slot').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">14:00</div>
                            <div class="time-slot" onclick="document.querySelectorAll('.time-slot').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">14:30</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="step-actions">
                <button class="btn btn-outline" onclick="goToStep(3)">+ Add Another Device</button>
                <button class="btn btn-primary btn-lg" onclick="goToStep(5)">Continue →</button>
            </div>
        </div>

        <!-- STEP 5: Your Details -->
        <div class="step-card" id="step5" style="display:none">
            <div class="step-header"><h2>Your Details</h2><p>Tell us how to reach you.</p></div>
            <button class="btn-back" onclick="goToStep(4)">← Back to Services</button>

            <!-- Summary -->
            <div class="booking-summary" style="margin-top:20px">
                <h6>🛒 Your Devices</h6>
                <div class="summary-item"><span class="summary-device">📱 iPhone 15 Pro</span><span class="summary-service">Screen Replacement, Battery Replacement</span></div>
            </div>

            <!-- Contact -->
            <div class="form-section">
                <div class="form-section-title"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Contact Information</div>
                <div class="form-grid">
                    <div class="form-group"><label>First Name <span class="req">*</span></label><input type="text" placeholder="John" /></div>
                    <div class="form-group"><label>Last Name <span class="req">*</span></label><input type="text" placeholder="Doe" /></div>
                    <div class="form-group"><label>Email <span class="req">*</span></label><input type="email" placeholder="john@example.com" /></div>
                    <div class="form-group"><label>Phone</label><input type="tel" placeholder="+1 (555) 000-0000" /></div>
                    <div class="form-group"><label>Company</label><input type="text" placeholder="Company name" /></div>
                    <div class="form-group"><label>Tax ID</label><input type="text" placeholder="Tax ID" /></div>
                </div>
            </div>

            <!-- Address -->
            <div class="form-section">
                <div class="form-section-title"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Address <span style="color:var(--rb-text-3);font-weight:400;font-size:12px">(Optional)</span></div>
                <div class="form-grid">
                    <div class="form-group full"><label>Street Address</label><input type="text" placeholder="123 Main St" /></div>
                    <div class="form-group"><label>City</label><input type="text" placeholder="Portland" /></div>
                    <div class="form-group"><label>Postal Code</label><input type="text" placeholder="97201" /></div>
                </div>
            </div>

            <!-- Job Details -->
            <div class="form-section">
                <div class="form-section-title"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Booking Details</div>
                <div class="form-grid">
                    <div class="form-group full"><label>Job Details <span class="req">*</span></label><textarea rows="4" placeholder="Describe the issue or service needed..."></textarea></div>
                </div>
            </div>

            <div class="gdpr-check">
                <input type="checkbox" id="gdpr" />
                <label for="gdpr">I agree to the processing of my personal data in accordance with the <a href="#">Privacy Policy</a>.</label>
            </div>

            <div class="step-actions">
                <div></div>
                <button class="btn btn-orange btn-lg" onclick="showSuccess()">📨 Submit Booking</button>
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
                    <span class="value">QF-2026-00487</span>
                </div>
                <p style="margin-top:20px;font-size:13px">You will receive an email confirmation shortly.</p>
                @php
                    $isSubdomain = request()->routeIs('tenant.subdomain.*');
                    $tenantRoutePrefix = $isSubdomain ? 'tenant.subdomain' : 'tenant';
                    $homeRoute = $tenantRoutePrefix . '.welcome';
                @endphp
                <a href="{{ route($homeRoute, ['business' => $tenantSlug]) }}" class="btn btn-primary btn-lg" style="margin-top:24px">← Back to Home</a>
            </div>
        </div>
    </div>

    @include('tenant.partials.tenant-footer', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant
    ])

    <script>
        let currentStep = 1;
        const devices = [];

        function goToStep(step) {
            document.querySelectorAll('.step-card').forEach(el => el.style.display = 'none');
            document.getElementById('step' + step).style.display = 'block';
            currentStep = step;
            updateProgress(step);
            window.scrollTo({ top: 200, behavior: 'smooth' });
        }

        function updateProgress(step) {
            const steps = document.querySelectorAll('.progress-step');
            const connectors = document.querySelectorAll('.progress-connector');
            steps.forEach((el, i) => {
                const n = i + 1;
                el.classList.remove('active', 'current', 'done');
                if (n < step) { el.classList.add('active', 'done'); el.querySelector('.progress-step-circle').textContent = '✓'; }
                else if (n === step) { el.classList.add('active', 'current'); el.querySelector('.progress-step-circle').textContent = n; }
                else { el.querySelector('.progress-step-circle').textContent = n; }
            });
            connectors.forEach((el, i) => {
                el.classList.toggle('active', i < step - 1);
            });
        }

        function selectCard(el) {
            el.closest('.selection-grid').querySelectorAll('.selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
        }

        function addDevice(el, name) {
            el.classList.add('selected');
            if (!devices.includes(name)) {
                devices.push(name);
                renderDevices();
            }
        }

        function removeDevice(name) {
            const idx = devices.indexOf(name);
            if (idx > -1) devices.splice(idx, 1);
            renderDevices();
        }

        function renderDevices() {
            const container = document.getElementById('selectedDevices');
            const list = document.getElementById('deviceList');
            const count = document.getElementById('deviceCount');
            if (devices.length === 0) { container.style.display = 'none'; return; }
            container.style.display = 'block';
            count.textContent = devices.length;
            list.innerHTML = devices.map((d, i) => `
                <div class="selected-device">
                    <div class="selected-device-header" onclick="toggleDeviceFields(${i})">
                        <div class="selected-device-name">📱 ${d}</div>
                        <div class="selected-device-actions">
                            <span class="selected-device-toggle open" id="toggle-${i}">▼</span>
                            <button class="selected-device-remove" onclick="event.stopPropagation();removeDevice('${d}')">✕</button>
                        </div>
                    </div>
                    <div class="selected-device-fields" id="fields-${i}">
                        <div class="sd-field">
                            <label>IMEI / Serial Number</label>
                            <input type="text" placeholder="e.g. 353456789012345" />
                        </div>
                        <div class="sd-field">
                            <label>Pin / Passcode</label>
                            <input type="text" placeholder="Device unlock code" />
                        </div>
                        <div class="sd-field sd-field-dynamic">
                            <label>Color</label>
                            <input type="text" placeholder="e.g. Space Black" />
                        </div>
                        <div class="sd-field sd-field-dynamic">
                            <label>Storage</label>
                            <input type="text" placeholder="e.g. 256GB" />
                        </div>
                        <div class="sd-field sd-field-dynamic">
                            <label>Warranty Status</label>
                            <input type="text" placeholder="e.g. In warranty, Out of warranty" />
                        </div>
                        <div class="sd-field sd-field-dynamic">
                            <label>Purchase Date</label>
                            <input type="date" />
                        </div>
                        <div class="sd-field full">
                            <label>Device Notes</label>
                            <textarea rows="2" placeholder="Any additional notes about this device..."></textarea>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function toggleDeviceFields(idx) {
            const fields = document.getElementById('fields-' + idx);
            const toggle = document.getElementById('toggle-' + idx);
            fields.classList.toggle('collapsed');
            toggle.classList.toggle('open');
        }

        function showSuccess() {
            document.querySelectorAll('.step-card').forEach(el => el.style.display = 'none');
            document.getElementById('stepSuccess').style.display = 'block';
            document.getElementById('progressBar').style.display = 'none';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>
