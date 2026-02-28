/**
 * RepairBuddy Timelog — Timer & Quick Entry
 *
 * Works with the technician dashboard blade (tenant.timelog).
 * Communicates with the API at /api/v1/time-logs.
 *
 * Features:
 * - Timer with start/pause/stop
 * - Quick manual time entry
 * - Weekly hours chart
 * - Form validation
 * - Timer persistence (resumes after page refresh)
 */
(function () {
    'use strict';

    /* ─── State ──────────────────────────────────── */
    let timerInterval = null;
    let timerStartTime = null;
    let timerPausedTime = null;
    let timerElapsed = 0; // seconds of accumulated time
    let timerRunning = false;
    let activeTimeLogId = null; // For timer persistence
    let weeklyChart = null;

    /* ─── DOM refs ───────────────────────────────── */
    const $ = (sel) => document.querySelector(sel);
    const $all = (sel) => document.querySelectorAll(sel);
    const display = $('#currentTimer');
    const statusBadge = $('#timerStatus');
    const btnStart = $('#startTimer');
    const btnPause = $('#pauseTimer');
    const btnStop = $('#stopTimer');
    const startTimeEl = $('#startTime');
    const workDescriptionEl = $('#workDescription');
    const activityTypeEl = $('#activityType');
    const isBillableEl = $('#isBillable');

    const jobDeviceSelect = $('#timeLogJobDeviceSelect');

    /* ─── Helpers ────────────────────────────────── */
    function pad(n) { return String(n).padStart(2, '0'); }

    function formatTimer(secs) {
        const h = Math.floor(secs / 3600);
        const m = Math.floor((secs % 3600) / 60);
        const s = secs % 60;
        return pad(h) + ':' + pad(m) + ':' + pad(s);
    }

    function showAlert(msg, type) {
        const container = document.querySelector('.dashboard-content') || document.body;
        const alertEl = document.createElement('div');
        alertEl.className = 'alert alert-' + (type || 'info') + ' alert-dismissible fade show';
        alertEl.setAttribute('role', 'alert');
        alertEl.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        container.prepend(alertEl);
        setTimeout(() => { if (alertEl.parentNode) alertEl.remove(); }, 5000);
    }

    function getApiBase() {
        const meta = document.querySelector('meta[name="api-base-url"]');
        if (meta) return meta.getAttribute('content');
        // Fallback: derive from window location
        return '/api/v1';
    }

    function getWebBase() {
        // Get the base URL for web routes (session auth)
        const path = window.location.pathname;
        const match = path.match(/^\/(t\/[^\/]+|[^\/]+)/);
        if (match) return '/' + match[1];
        return '';
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Fetch Sanctum CSRF cookie for SPA authentication
    function ensureCsrfCookie() {
        return fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin'
        }).catch(function(err) {
            // Ignore errors - might not be needed if already set
            console.warn('CSRF cookie fetch failed (may be okay):', err);
        });
    }

    function getSelectedJobDevice() {
        if (!jobDeviceSelect) return { jobId: null, deviceId: '', deviceSerial: '', deviceIndex: 0 };
        const val = jobDeviceSelect.value || '';
        const parts = val.split('|');
        return {
            jobId: parts[0] ? parseInt(parts[0], 10) : null,
            deviceId: parts[1] || '',
            deviceSerial: parts[2] || '',
            deviceIndex: parseInt(parts[3] || '0', 10),
        };
    }

    /* ─── Localization ───────────────────────────── */
    function getText(key, fallback) {
        if (window.timelog_i18n && window.timelog_i18n[key]) {
            return window.timelog_i18n[key];
        }
        return fallback;
    }

    /* ─── Validation ──────────────────────────────── */
    function validateTimerFields() {
        const description = workDescriptionEl ? workDescriptionEl.value.trim() : '';
        const activity = activityTypeEl ? activityTypeEl.value : '';
        const isValid = description !== '' && activity !== '';

        // Visual feedback
        if (workDescriptionEl) {
            workDescriptionEl.classList.toggle('is-invalid', description === '');
            workDescriptionEl.classList.toggle('is-valid', description !== '');
        }
        if (activityTypeEl) {
            activityTypeEl.classList.toggle('is-invalid', activity === '');
            activityTypeEl.classList.toggle('is-valid', activity !== '');
        }

        // Disable/enable start button
        if (btnStart) {
            btnStart.disabled = !isValid;
        }

        return isValid;
    }

    /* ─── Chart ───────────────────────────────────── */
    function initWeeklyChart() {
        const canvas = document.getElementById('weeklyTimeChart');
        if (!canvas || typeof Chart === 'undefined') return;

        const chartData = window.timelog_chart_data || {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            data: [0, 0, 0, 0, 0, 0, 0]
        };

        weeklyChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: getText('hours_worked', 'Hours Worked'),
                    data: chartData.data,
                    backgroundColor: 'rgba(13, 110, 253, 0.8)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: getText('weekly_hours', 'Weekly Hours Distribution')
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + 'h';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: getText('hours', 'Hours') },
                        ticks: {
                            callback: function(value) { return value + 'h'; }
                        }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function updateChart(period) {
        const apiBase = getApiBase();
        fetch(apiBase + '/time-logs/chart?period=' + (period || 'week'), {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(function(res) {
            if (!res.ok) {
                throw new Error('Network error: ' + res.status);
            }
            return res.json();
        })
        .then(function(data) {
            if (data.error || data.message) {
                throw new Error(data.error || data.message);
            }
            if (weeklyChart && data.labels && data.data) {
                weeklyChart.data.labels = data.labels;
                weeklyChart.data.datasets[0].data = data.data;
                weeklyChart.update();
            }
        })
        .catch(function(err) {
            console.error('Chart update failed:', err);
            showAlert(getText('chart_update_failed', 'Could not update chart. Please try again.'), 'warning');
        });
    }

    /* ─── Timer Controls ─────────────────────────── */
    function startTimer() {
        if (timerRunning) return;

        // Validate required fields
        if (!validateTimerFields()) {
            showAlert(getText('description_activity_required', 'Please enter a work description and select an activity type.'), 'warning');
            return;
        }

        const sel = getSelectedJobDevice();
        if (!sel.jobId) {
            showAlert(getText('select_job_first', 'Please select a job/device before starting the timer.'), 'warning');
            return;
        }

        timerRunning = true;
        timerStartTime = timerStartTime || new Date();
        timerPausedTime = null;

        if (startTimeEl) {
            startTimeEl.textContent = timerStartTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        if (statusBadge) {
            statusBadge.textContent = getText('running', 'Running');
            statusBadge.className = 'badge bg-success float-end';
        }
        if (btnStart) btnStart.disabled = true;
        if (btnPause) btnPause.disabled = false;
        if (btnStop) btnStop.disabled = false;

        // Disable form fields while timer is running
        if (workDescriptionEl) workDescriptionEl.disabled = true;
        if (activityTypeEl) activityTypeEl.disabled = true;
        if (isBillableEl) isBillableEl.disabled = true;

        // Create running time log entry for persistence
        createRunningTimeLog(sel);

        timerInterval = setInterval(function () {
            timerElapsed++;
            if (display) display.textContent = formatTimer(timerElapsed);
        }, 1000);
    }

    function createRunningTimeLog(sel) {
        const webBase = getWebBase();
        const data = {
            job_id: sel.jobId,
            start_time: timerStartTime.toISOString(),
            activity: activityTypeEl ? activityTypeEl.value : '',
            work_description: workDescriptionEl ? workDescriptionEl.value.trim() : '',
            is_billable: isBillableEl ? isBillableEl.value === '1' : true,
            device_id: sel.deviceId,
            device_serial: sel.deviceSerial,
            device_index: sel.deviceIndex
        };

        fetch(webBase + '/time-log/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        })
        .then(function(res) {
            if (!res.ok) {
                return res.json().then(function(errData) {
                    throw { status: res.status, message: errData.message || 'Unknown error', data: errData };
                }).catch(function() {
                    throw { status: res.status, message: 'Network error (' + res.status + ')' };
                });
            }
            return res.json();
        })
        .then(function(json) {
            if (json.time_log && json.time_log.id) {
                activeTimeLogId = json.time_log.id;
            }
        })
        .catch(function(err) {
            console.error('Failed to create running time log:', err);
            // Show specific error message from API
            var errorMsg = err.message || getText('save_error', 'Failed to save time entry. Please try again.');
            if (err.status === 403) {
                errorMsg = getText('not_assigned', 'You are not assigned to this job.');
                if (err.data && err.data.debug) {
                    errorMsg += ' (debug: ' + JSON.stringify(err.data.debug) + ')';
                }
            } else if (err.status === 404) {
                errorMsg = getText('job_not_found', 'Job not found.');
            }
            showAlert(errorMsg, 'danger');
            // Reset timer state on failure
            timerRunning = false;
            clearInterval(timerInterval);
            if (btnStart) btnStart.disabled = false;
            if (btnPause) btnPause.disabled = true;
            if (btnStop) btnStop.disabled = true;
            if (workDescriptionEl) workDescriptionEl.disabled = false;
            if (activityTypeEl) activityTypeEl.disabled = false;
            if (isBillableEl) isBillableEl.disabled = false;
        });
    }

    function pauseTimer() {
        if (!timerRunning) return;

        timerRunning = false;
        timerPausedTime = new Date();
        clearInterval(timerInterval);

        if (display) {
            display.textContent = formatTimer(timerElapsed);
        }
        if (statusBadge) {
            statusBadge.textContent = getText('paused', 'Paused');
            statusBadge.className = 'badge bg-warning text-dark float-end';
        }
        if (btnStart) btnStart.disabled = false;
        if (btnPause) btnPause.disabled = true;

        // Update running time log with current elapsed time
        const webBase = getWebBase();
        fetch(webBase + '/time-log/pause', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ time_log_id: activeTimeLogId })
        })
        .then(function(res) {
            if (!res.ok) {
                console.error('Pause time log failed with status:', res.status);
            }
            return res.json();
        })
        .catch(function(err) {
            console.error('Failed to pause time log:', err);
            // Non-critical - don't show alert for pause updates
        });
    }

    function stopTimer() {
        if (!timerStartTime) return;
        clearInterval(timerInterval);
        timerRunning = false;

        const endTime = new Date();
        const durationMinutes = Math.max(1, Math.round(timerElapsed / 60));

        const sel = getSelectedJobDevice();

        // If we have an active time log ID, update it; otherwise create new
        if (activeTimeLogId) {
            completeTimeLog(activeTimeLogId, endTime, durationMinutes);
        } else {
            // Fallback: create new entry (shouldn't normally happen)
            const description = workDescriptionEl ? workDescriptionEl.value : '';
            const activity = activityTypeEl ? activityTypeEl.value : '';
            const isBillable = isBillableEl ? isBillableEl.value === '1' : true;
            const techId = ($('#technicianId') || {}).value || '';

            saveTimeEntry({
                job_id: sel.jobId,
                technician_id: parseInt(techId, 10) || null,
                start_time: timerStartTime.toISOString(),
                end_time: endTime.toISOString(),
                total_minutes: durationMinutes,
                activity: activity,
                work_description: description,
                is_billable: isBillable,
                time_type: 'timer',
                log_state: 'pending',
                device_data: {
                    device_id: sel.deviceId,
                    device_serial: sel.deviceSerial,
                    device_index: sel.deviceIndex
                }
            });
        }

        // Reset
        timerStartTime = null;
        timerPausedTime = null;
        timerElapsed = 0;
        activeTimeLogId = null;

        if (display) display.textContent = '00:00:00';
        if (startTimeEl) startTimeEl.textContent = '--:--';
        if (statusBadge) {
            statusBadge.textContent = getText('stopped', 'Stopped');
            statusBadge.className = 'badge bg-secondary float-end';
        }
        if (btnStart) btnStart.disabled = false;
        if (btnPause) btnPause.disabled = true;
        if (btnStop) btnStop.disabled = true;

        // Re-enable form fields
        if (workDescriptionEl) {
            workDescriptionEl.disabled = false;
            workDescriptionEl.value = '';
        }
        if (activityTypeEl) {
            activityTypeEl.disabled = false;
            activityTypeEl.value = '';
        }
        if (isBillableEl) isBillableEl.disabled = false;
    }

    function updateTimeLog(timeLogId, updates) {
        const apiBase = getApiBase();
        fetch(apiBase + '/time-logs/' + timeLogId, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(updates)
        })
        .then(function(res) {
            if (!res.ok) {
                console.error('Update time log failed with status:', res.status);
            }
            return res.json();
        })
        .catch(function(err) {
            console.error('Failed to update time log:', err);
            // Non-critical - don't show alert for pause updates
        });
    }

    function completeTimeLog(timeLogId, endTime, durationMinutes) {
        const webBase = getWebBase();
        fetch(webBase + '/time-log/stop', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                time_log_id: timeLogId,
                end_time: endTime.toISOString(),
                total_minutes: durationMinutes
            })
        })
        .then(function(res) {
            if (!res.ok) {
                return res.json().then(function(errData) {
                    throw { status: res.status, message: errData.message || 'Unknown error' };
                }).catch(function() {
                    throw { status: res.status, message: 'Network error (' + res.status + ')' };
                });
            }
            return res.json();
        })
        .then(function(json) {
            showAlert(getText('time_entry_saved', 'Time entry saved successfully!'), 'success');
            // Refresh stats without full page reload
            refreshStats();
            refreshRecentLogs();
            updateChart('week');
        })
        .catch(function(err) {
            console.error('Failed to complete time log:', err);
            var errorMsg = err.message || getText('save_error', 'Failed to save time entry. Please try again.');
            showAlert(errorMsg, 'danger');
        });
    }

    /* ─── Quick Time Entry ───────────────────────── */
    function handleQuickTimeSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);

        const startStr = fd.get('manual_start_time');
        const endStr = fd.get('manual_end_time');

        if (!startStr || !endStr) {
            showAlert(getText('enter_start_end_times', 'Please enter both start and end times.'), 'warning');
            return;
        }

        const startDt = new Date(startStr);
        const endDt = new Date(endStr);

        if (endDt <= startDt) {
            showAlert(getText('end_time_after_start', 'End time must be after start time.'), 'warning');
            return;
        }

        const durationMinutes = Math.max(1, Math.round((endDt - startDt) / 60000));
        const sel = getSelectedJobDevice();

        if (!sel.jobId) {
            const jobIdHidden = fd.get('jobId');
            if (jobIdHidden) sel.jobId = parseInt(jobIdHidden, 10);
        }

        // Validate required fields
        const activity = fd.get('timelog_activity_type') || '';
        const description = fd.get('manual_entry_description') || '';

        if (!activity) {
            showAlert(getText('activity_required', 'Please select an activity type.'), 'warning');
            return;
        }
        if (!description.trim()) {
            showAlert(getText('description_required', 'Please enter a work description.'), 'warning');
            return;
        }

        saveTimeEntry({
            job_id: sel.jobId || parseInt(fd.get('jobId'), 10) || null,
            technician_id: parseInt(fd.get('technicianId'), 10) || null,
            start_time: startDt.toISOString(),
            end_time: endDt.toISOString(),
            total_minutes: durationMinutes,
            activity: activity,
            work_description: description,
            is_billable: fd.get('isBillable_manual') === '1',
            time_type: 'manual',
            log_state: 'pending',
            device_data: {
                device_id: fd.get('deviceId') || sel.deviceId,
                device_serial: fd.get('deviceSerial') || sel.deviceSerial,
                device_index: parseInt(fd.get('deviceIndex') || sel.deviceIndex, 10)
            }
        });

        form.reset();
    }

    /* ─── Dynamic Updates (no page reload) ──────── */
    function refreshStats() {
        const apiBase = getApiBase();
        fetch(apiBase + '/time-logs?per_page=1', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.summary) {
                // Update stats cards if they exist
                const todayHours = $('#todayHours');
                const weekHours = $('#weekHours');
                const billableRate = $('#billableRate');
                const monthEarnings = $('#monthEarnings');

                if (todayHours && data.summary.today_hours !== undefined) {
                    todayHours.textContent = data.summary.today_hours + 'h';
                }
                if (weekHours && data.summary.week_hours !== undefined) {
                    weekHours.textContent = data.summary.week_hours + 'h';
                }
                if (billableRate && data.summary.billable_rate !== undefined) {
                    billableRate.textContent = data.summary.billable_rate + '%';
                }
                if (monthEarnings && data.summary.month_earnings_formatted) {
                    monthEarnings.textContent = data.summary.month_earnings_formatted;
                }
            }
        })
        .catch(function(err) { console.error('Stats refresh failed:', err); });
    }

    function refreshRecentLogs() {
        const apiBase = getApiBase();
        fetch(apiBase + '/time-logs?per_page=5', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.time_logs && data.time_logs.length > 0) {
                const tbody = $('#todayLogsTable');
                if (!tbody) return;

                // Prepend new entries
                data.time_logs.forEach(function(log) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (log.job ? '#' + log.job.case_number : '-') + '</td>' +
                        '<td><span class="badge bg-info">' + escapeHtml(log.activity || '-') + '</span></td>' +
                        '<td>' + formatTime(log.start_time) + '</td>' +
                        '<td>' + Math.floor(log.total_minutes / 60) + 'h ' + (log.total_minutes % 60) + 'm</td>' +
                        '<td class="text-end">' + formatCurrency(log.charged_amount) + '</td>';
                    tbody.insertBefore(tr, tbody.firstChild);
                });
            }
        })
        .catch(function(err) { console.error('Logs refresh failed:', err); });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function formatTime(isoString) {
        if (!isoString) return '-';
        const d = new Date(isoString);
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function formatCurrency(amount) {
        if (!amount) return '-';
        const cents = amount.amount_cents || 0;
        const currency = amount.currency || 'USD';
        return (cents / 100).toLocaleString(undefined, { style: 'currency', currency: currency });
    }

    /* ─── API Call ────────────────────────────────── */
    function saveTimeEntry(data) {
        const webBase = getWebBase();

        fetch(webBase + '/time-log/entry', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(data),
        })
        .then(function (res) {
            if (!res.ok) {
                return res.json().then(function(errData) {
                    throw { status: res.status, message: errData.message || 'Unknown error', errors: errData.errors || {} };
                }).catch(function() {
                    throw { status: res.status, message: 'Network error (' + res.status + ')' };
                });
            }
            return res.json();
        })
        .then(function (json) {
            showAlert(getText('time_entry_saved', 'Time entry saved successfully!'), 'success');
            // Refresh stats and logs without page reload
            refreshStats();
            refreshRecentLogs();
            updateChart('week');
        })
        .catch(function (err) {
            console.error('Time entry save error:', err);
            var errorMsg = err.message || getText('save_error', 'Failed to save time entry. Please try again.');
            if (err.status === 403) {
                errorMsg = getText('not_assigned', 'You are not assigned to this job.');
            } else if (err.status === 404) {
                errorMsg = getText('job_not_found', 'Job not found.');
            } else if (err.status === 422 && err.errors) {
                // Validation errors - show first one
                var firstError = Object.values(err.errors)[0];
                if (Array.isArray(firstError)) {
                    errorMsg = firstError[0];
                } else {
                    errorMsg = firstError;
                }
            }
            showAlert(errorMsg, 'danger');
        });
    }

    /* ─── Timer Restoration (after page refresh) ─── */
    function restoreRunningTimer() {
        // Use data passed from backend instead of API call (avoids SPA auth issues)
        const data = window.timelog_running_timer;
        if (!data) return;
        
        activeTimeLogId = data.id;
        timerStartTime = new Date(data.start_time);

        // Calculate elapsed time
        const now = new Date();
        timerElapsed = Math.floor((now - timerStartTime) / 1000);

        // Update UI
        if (startTimeEl) {
            startTimeEl.textContent = timerStartTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        if (display) {
            display.textContent = formatTimer(timerElapsed);
        }
        if (statusBadge) {
            statusBadge.textContent = getText('running', 'Running');
            statusBadge.className = 'badge bg-success float-end';
        }

        // Disable form fields
        if (workDescriptionEl) {
            workDescriptionEl.disabled = true;
            workDescriptionEl.value = data.work_description || '';
        }
        if (activityTypeEl) {
            activityTypeEl.disabled = true;
            if (data.activity) {
                activityTypeEl.value = data.activity.toLowerCase();
            }
        }
        if (isBillableEl) {
            isBillableEl.disabled = true;
            isBillableEl.value = data.is_billable ? '1' : '0';
        }

        // Set timer as running
        timerRunning = true;
        if (btnStart) btnStart.disabled = true;
        if (btnPause) btnPause.disabled = false;
        if (btnStop) btnStop.disabled = false;

        // Start the interval
        timerInterval = setInterval(function () {
            timerElapsed++;
            if (display) display.textContent = formatTimer(timerElapsed);
        }, 1000);
    }

    /* ─── Job/Device Selection ────────────────────── */
    function handleJobDeviceChange() {
        if (!jobDeviceSelect) return;
        const val = jobDeviceSelect.value;
        
        // If no selection, hide the timer section and show alert
        if (!val) {
            const timerSection = $('#currentTimeEntry');
            const quickEntrySection = $('#quickTimeEntrySection');
            const noJobAlert = $('#noJobSelectedAlert');
            const quickNoJobAlert = $('#quickTimeNoJobAlert');
            
            if (timerSection) timerSection.style.display = 'none';
            if (quickEntrySection) quickEntrySection.style.display = 'none';
            if (noJobAlert) noJobAlert.style.display = 'block';
            if (quickNoJobAlert) quickNoJobAlert.style.display = 'block';
            return;
        }

        const parts = val.split('|');
        const jobId = parts[0] ? parseInt(parts[0], 10) : null;
        const deviceId = parts[1] || '';
        const deviceSerial = parts[2] || '';
        const deviceIndex = parseInt(parts[3] || '0', 10);

        // Update hidden fields
        const jobIdInput = $('#jobId');
        const deviceIdInput = $('#deviceId');
        const deviceSerialInput = $('#deviceSerial');
        const deviceIndexInput = $('#deviceIndex');
        const quickJobIdInput = document.querySelector('#quickTimeForm input[name="jobId"]');
        const quickDeviceIdInput = document.querySelector('#quickTimeForm input[name="deviceId"]');
        const quickDeviceSerialInput = document.querySelector('#quickTimeForm input[name="deviceSerial"]');
        const quickDeviceIndexInput = document.querySelector('#quickTimeForm input[name="deviceIndex"]');

        if (jobIdInput) jobIdInput.value = jobId || '';
        if (deviceIdInput) deviceIdInput.value = deviceId;
        if (deviceSerialInput) deviceSerialInput.value = deviceSerial;
        if (deviceIndexInput) deviceIndexInput.value = deviceIndex;
        if (quickJobIdInput) quickJobIdInput.value = jobId || '';
        if (quickDeviceIdInput) quickDeviceIdInput.value = deviceId;
        if (quickDeviceSerialInput) quickDeviceSerialInput.value = deviceSerial;
        if (quickDeviceIndexInput) quickDeviceIndexInput.value = deviceIndex;

        // Hide alerts
        const noJobAlert = $('#noJobSelectedAlert');
        const quickNoJobAlert = $('#quickTimeNoJobAlert');
        if (noJobAlert) noJobAlert.style.display = 'none';
        if (quickNoJobAlert) quickNoJobAlert.style.display = 'none';

        // Reload page with job parameter (simpler approach that works with session auth)
        const url = new URL(window.location.href);
        url.searchParams.set('job', val);
        window.location.href = url.toString();
    }

    /* ─── Chart Period Buttons ───────────────────── */
    function initChartPeriodButtons() {
        const buttons = $all('[data-chart-period]');
        buttons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const period = btn.getAttribute('data-chart-period');
                updateChart(period);

                // Update active state
                buttons.forEach(function(b) {
                    b.classList.remove('active');
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-outline-primary');
                });
                btn.classList.add('active');
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-primary');
            });
        });
    }

    /* ─── Init ───────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize chart
        initWeeklyChart();
        initChartPeriodButtons();

        // Timer buttons
        if (btnStart) btnStart.addEventListener('click', startTimer);
        if (btnPause) btnPause.addEventListener('click', pauseTimer);
        if (btnStop) btnStop.addEventListener('click', stopTimer);

        // Validation on input
        if (workDescriptionEl) {
            workDescriptionEl.addEventListener('input', validateTimerFields);
        }
        if (activityTypeEl) {
            activityTypeEl.addEventListener('change', validateTimerFields);
        }

        // Initial validation state
        validateTimerFields();

        // Job/device selection
        if (jobDeviceSelect) {
            if (typeof jQuery !== 'undefined') {
                jQuery(jobDeviceSelect).on('change', handleJobDeviceChange);
            } else {
                jobDeviceSelect.addEventListener('change', handleJobDeviceChange);
            }
        }

        // Quick time form
        const quickForm = $('#quickTimeForm');
        if (quickForm) {
            quickForm.addEventListener('submit', handleQuickTimeSubmit);
        }

        // Restore running timer if exists
        restoreRunningTimer();
    });
})();
