/**
 * RepairBuddy Timelog — Timer & Quick Entry
 *
 * Works with the technician dashboard blade (tenant.timelog).
 * Communicates with the API at /api/v1/time-logs.
 */
(function () {
    'use strict';

    /* ─── State ──────────────────────────────────── */
    let timerInterval = null;
    let timerStartTime = null;
    let timerPausedTime = null;
    let timerElapsed = 0; // seconds of accumulated time
    let timerRunning = false;

    /* ─── DOM refs ───────────────────────────────── */
    const $ = (sel) => document.querySelector(sel);
    const display = $('#currentTimer');
    const statusBadge = $('#timerStatus');
    const btnStart = $('#startTimer');
    const btnPause = $('#pauseTimer');
    const btnStop = $('#stopTimer');
    const startTimeEl = $('#startTime');

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

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
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

    /* ─── Timer Controls ─────────────────────────── */
    function startTimer() {
        if (timerRunning) return;

        const sel = getSelectedJobDevice();
        if (!sel.jobId) {
            showAlert('Please select a job/device before starting the timer.', 'warning');
            return;
        }

        timerRunning = true;
        timerStartTime = timerStartTime || new Date();
        timerPausedTime = null;

        if (startTimeEl) {
            startTimeEl.textContent = timerStartTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        if (statusBadge) {
            statusBadge.textContent = 'Running';
            statusBadge.className = 'badge bg-success float-end';
        }
        if (btnStart) btnStart.disabled = true;
        if (btnPause) btnPause.disabled = false;
        if (btnStop) btnStop.disabled = false;

        timerInterval = setInterval(function () {
            timerElapsed++;
            if (display) display.textContent = formatTimer(timerElapsed);
        }, 1000);
    }

    function pauseTimer() {
        if (!timerRunning) return;
        timerRunning = false;
        timerPausedTime = new Date();
        clearInterval(timerInterval);

        if (statusBadge) {
            statusBadge.textContent = 'Paused';
            statusBadge.className = 'badge bg-warning text-dark float-end';
        }
        if (btnStart) btnStart.disabled = false;
        if (btnPause) btnPause.disabled = true;
    }

    function stopTimer() {
        if (!timerStartTime) return;
        clearInterval(timerInterval);
        timerRunning = false;

        const endTime = new Date();
        const durationMinutes = Math.max(1, Math.round(timerElapsed / 60));

        const sel = getSelectedJobDevice();
        const description = ($('#workDescription') || {}).value || '';
        const activity = ($('#activityType') || {}).value || '';
        const isBillable = ($('#isBillable') || {}).value === '1';
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
        });

        // Reset
        timerStartTime = null;
        timerPausedTime = null;
        timerElapsed = 0;

        if (display) display.textContent = '00:00:00';
        if (startTimeEl) startTimeEl.textContent = '--:--';
        if (statusBadge) {
            statusBadge.textContent = 'Stopped';
            statusBadge.className = 'badge bg-secondary float-end';
        }
        if (btnStart) btnStart.disabled = false;
        if (btnPause) btnPause.disabled = true;
        if (btnStop) btnStop.disabled = true;
    }

    /* ─── Quick Time Entry ───────────────────────── */
    function handleQuickTimeSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);

        const startStr = fd.get('manual_start_time');
        const endStr = fd.get('manual_end_time');

        if (!startStr || !endStr) {
            showAlert('Please enter both start and end times.', 'warning');
            return;
        }

        const startDt = new Date(startStr);
        const endDt = new Date(endStr);

        if (endDt <= startDt) {
            showAlert('End time must be after start time.', 'warning');
            return;
        }

        const durationMinutes = Math.max(1, Math.round((endDt - startDt) / 60000));
        const sel = getSelectedJobDevice();

        if (!sel.jobId) {
            const jobIdHidden = fd.get('jobId');
            if (jobIdHidden) sel.jobId = parseInt(jobIdHidden, 10);
        }

        saveTimeEntry({
            job_id: sel.jobId || parseInt(fd.get('jobId'), 10) || null,
            technician_id: parseInt(fd.get('technicianId'), 10) || null,
            start_time: startDt.toISOString(),
            end_time: endDt.toISOString(),
            total_minutes: durationMinutes,
            activity: fd.get('timelog_activity_type') || '',
            work_description: fd.get('manual_entry_description') || '',
            is_billable: fd.get('isBillable_manual') === '1',
            time_type: 'manual',
            log_state: 'pending',
        });

        form.reset();
    }

    /* ─── API Call ────────────────────────────────── */
    function saveTimeEntry(data) {
        const apiBase = getApiBase();

        fetch(apiBase + '/time-logs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(data),
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Save failed: ' + res.status);
                return res.json();
            })
            .then(function (json) {
                showAlert('Time entry saved successfully!', 'success');
                // Reload after short delay to refresh stats & recent logs
                setTimeout(() => window.location.reload(), 1200);
            })
            .catch(function (err) {
                console.error('Time entry save error:', err);
                showAlert('Failed to save time entry. Please try again.', 'danger');
            });
    }

    /* ─── Job/Device Selection ────────────────────── */
    function handleJobDeviceChange() {
        if (!jobDeviceSelect) return;
        const val = jobDeviceSelect.value;
        if (!val) return;

        // Reload page with the selected job in query string
        const url = new URL(window.location.href);
        url.searchParams.set('job', val);
        window.location.href = url.toString();
    }

    /* ─── Init ───────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        if (btnStart) btnStart.addEventListener('click', startTimer);
        if (btnPause) btnPause.addEventListener('click', pauseTimer);
        if (btnStop) btnStop.addEventListener('click', stopTimer);

        if (jobDeviceSelect) {
            if (typeof jQuery !== 'undefined') {
                jQuery(jobDeviceSelect).on('change', handleJobDeviceChange);
            } else {
                jobDeviceSelect.addEventListener('change', handleJobDeviceChange);
            }
        }

        const quickForm = $('#quickTimeForm');
        if (quickForm) {
            quickForm.addEventListener('submit', handleQuickTimeSubmit);
        }
    });
})();
