@php
    $tenantSlug = $tenantSlug ?? $business ?? null;
    $tenant = $tenant ?? null;
    $activePage = 'account';
    $isSubdomain = request()->routeIs('tenant.subdomain.*');
    $tenantRoutePrefix = $isSubdomain ? 'tenant.subdomain' : 'tenant';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $tenant->name ?? 'RepairBuddy' }} — My Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/tenant-public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tenant-my-account.css') }}">
</head>
<body>
    @include('tenant.partials.tenant-nav', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant,
        'activePage' => $activePage
    ])

    <div class="page-wrapper">
        <!-- VIEW TOGGLE (mockup preview) -->
        <div class="view-toggle">
            <button class="view-toggle-btn active" onclick="switchView(this,'guest')">🔑 Sign In</button>
            <button class="view-toggle-btn" onclick="switchView(this,'dashboard')">📊 Dashboard Preview</button>
        </div>

        <!-- GUEST / LOGIN VIEW -->
        <div class="guest-view" id="guestView">
            <div class="login-view">
                <div class="login-card">
                    <h2>Welcome Back</h2>
                    <p class="sub">Sign in to view your repairs and manage your account.</p>
                    <div class="form-group">
                        <label>Email Address <span class="req">*</span></label>
                        <input type="email" placeholder="you@example.com" />
                    </div>
                    <div class="form-group">
                        <label>Password <span class="req">*</span></label>
                        <input type="password" placeholder="Enter your password" />
                    </div>
                    <div class="login-actions">
                        <button class="btn btn-primary btn-lg" onclick="switchView(document.querySelectorAll('.view-toggle-btn')[1],'dashboard')">Sign In →</button>
                    </div>
                    <div class="login-link">
                        <a href="#">Forgot your password?</a>
                    </div>
                    <div class="login-divider">or</div>
                    <div class="login-link">
                        Don't have an account? <a href="#">Create one</a>
                    </div>
                </div>
                <div class="login-extra">
                    <a href="{{ route($tenantRoutePrefix . '.status.show', ['business' => $tenantSlug]) }}">🔍 Track a repair without signing in →</a>
                </div>
            </div>
        </div>

        <!-- DASHBOARD VIEW -->
        <div class="dashboard-view" id="dashboardView">
            <div class="dash-header">
                <div class="dash-user">
                    <div class="dash-avatar">JD</div>
                    <div>
                        <div class="dash-name">John Doe</div>
                        <div class="dash-email">john.doe@example.com</div>
                    </div>
                </div>
                <div style="display:flex;gap:10px">
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="btn btn-orange">📱 New Booking</a>
                    <button class="btn btn-outline" onclick="switchView(document.querySelectorAll('.view-toggle-btn')[0],'guest')">Sign Out</button>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-val">3</div><div class="stat-label">Active Repairs</div></div>
                <div class="stat-card"><div class="stat-val">12</div><div class="stat-label">Completed</div></div>
                <div class="stat-card"><div class="stat-val">1</div><div class="stat-label">Upcoming Appt.</div></div>
                <div class="stat-card"><div class="stat-val">4.9★</div><div class="stat-label">Avg. Rating</div></div>
            </div>

            <!-- Tabs -->
            <div class="dash-tabs">
                <button class="dash-tab active" onclick="switchTab(this,'repairs')">My Repairs <span class="tab-count">3</span></button>
                <button class="dash-tab" onclick="switchTab(this,'settings')">Account Settings</button>
            </div>

            <!-- TAB: Repairs -->
            <div class="tab-content active" id="tab-repairs">
                <div class="section-title">🔧 Active Repairs <span class="badge">3</span></div>
                <div class="repair-list">
                    <a href="#" class="repair-card">
                        <div class="repair-info">
                            <div class="repair-case">#QF-2026-00487</div>
                            <div class="repair-device">iPhone 15 Pro</div>
                            <div class="repair-service">Screen Replacement + Battery Replacement</div>
                        </div>
                        <div class="repair-meta">
                            <div class="repair-date">📅 Mar 15, 2026</div>
                            <span class="status-badge in-progress">In Progress</span>
                            <span class="repair-arrow">→</span>
                        </div>
                    </a>
                    <a href="#" class="repair-card">
                        <div class="repair-info">
                            <div class="repair-case">#QF-2026-00485</div>
                            <div class="repair-device">MacBook Pro 14"</div>
                            <div class="repair-service">Keyboard Replacement</div>
                        </div>
                        <div class="repair-meta">
                            <div class="repair-date">📅 Mar 14, 2026</div>
                            <span class="status-badge waiting">Waiting for Parts</span>
                            <span class="repair-arrow">→</span>
                        </div>
                    </a>
                    <a href="#" class="repair-card">
                        <div class="repair-info">
                            <div class="repair-case">#QF-2026-00482</div>
                            <div class="repair-device">iPad Air 5th Gen</div>
                            <div class="repair-service">Water Damage Treatment</div>
                        </div>
                        <div class="repair-meta">
                            <div class="repair-date">📅 Mar 12, 2026</div>
                            <span class="status-badge ready">Ready for Pickup</span>
                            <span class="repair-arrow">→</span>
                        </div>
                    </a>
                </div>

                <div class="section-title">📋 Past Repairs</div>
                <div class="repair-list">
                    <a href="#" class="repair-card">
                        <div class="repair-info">
                            <div class="repair-case">#QF-2026-00401</div>
                            <div class="repair-device">Samsung Galaxy S24</div>
                            <div class="repair-service">Screen Replacement</div>
                        </div>
                        <div class="repair-meta">
                            <div class="repair-date">📅 Feb 28, 2026</div>
                            <span class="status-badge completed">Completed</span>
                            <span class="repair-arrow">→</span>
                        </div>
                    </a>
                    <a href="#" class="repair-card">
                        <div class="repair-info">
                            <div class="repair-case">#QF-2026-00356</div>
                            <div class="repair-device">iPhone 14</div>
                            <div class="repair-service">Battery Replacement</div>
                        </div>
                        <div class="repair-meta">
                            <div class="repair-date">📅 Feb 10, 2026</div>
                            <span class="status-badge completed">Completed</span>
                            <span class="repair-arrow">→</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- TAB: Account Settings -->
            <div class="tab-content" id="tab-settings">
                <div class="settings-grid">
                    <div class="settings-card">
                        <h4>👤 Personal Information</h4>
                        <div class="settings-row"><span class="settings-label">Full Name</span><span class="settings-value">John Doe</span></div>
                        <div class="settings-row"><span class="settings-label">Email</span><span class="settings-value">john.doe@example.com</span></div>
                        <div class="settings-row"><span class="settings-label">Phone</span><span class="settings-value">+1 (555) 123-4567</span></div>
                        <div class="settings-row"><span class="settings-label">Company</span><span class="settings-value">—</span></div>
                        <div style="margin-top:12px"><button class="settings-action">✏️ Edit Profile</button></div>
                    </div>
                    <div class="settings-card">
                        <h4>📍 Default Address</h4>
                        <div class="settings-row"><span class="settings-label">Street</span><span class="settings-value">742 Maple Ave</span></div>
                        <div class="settings-row"><span class="settings-label">City</span><span class="settings-value">Portland</span></div>
                        <div class="settings-row"><span class="settings-label">Postal Code</span><span class="settings-value">97201</span></div>
                        <div class="settings-row"><span class="settings-label">Country</span><span class="settings-value">United States</span></div>
                        <div style="margin-top:12px"><button class="settings-action">✏️ Edit Address</button></div>
                    </div>
                    <div class="settings-card">
                        <h4>🔒 Security</h4>
                        <div class="settings-row"><span class="settings-label">Password</span><span class="settings-value">••••••••</span></div>
                        <div class="settings-row"><span class="settings-label">Last Changed</span><span class="settings-value">Jan 5, 2026</span></div>
                        <div style="margin-top:12px"><button class="settings-action">🔑 Change Password</button></div>
                    </div>
                    <div class="settings-card">
                        <h4>🔔 Notifications</h4>
                        <div class="settings-row"><span class="settings-label">Email Updates</span><span class="settings-value" style="color:var(--rb-success)">✓ Enabled</span></div>
                        <div class="settings-row"><span class="settings-label">SMS Alerts</span><span class="settings-value" style="color:var(--rb-text-3)">✕ Disabled</span></div>
                        <div class="settings-row"><span class="settings-label">Marketing</span><span class="settings-value" style="color:var(--rb-text-3)">✕ Disabled</span></div>
                        <div style="margin-top:12px"><button class="settings-action">⚙️ Manage Preferences</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('tenant.partials.tenant-footer', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant
    ])

    <script>
        function switchView(el, view) {
            document.querySelectorAll('.view-toggle-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('guestView').style.display = view === 'guest' ? '' : 'none';
            const dashView = document.getElementById('dashboardView');
            dashView.style.display = view === 'dashboard' ? '' : 'none';
            if (view === 'dashboard') {
                dashView.classList.add('show');
            } else {
                dashView.classList.remove('show');
            }
        }
        function switchTab(el, tab) {
            document.querySelectorAll('.dash-tab').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
        }
    </script>
</body>
</html>
