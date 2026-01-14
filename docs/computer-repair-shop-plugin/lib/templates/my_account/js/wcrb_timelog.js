/**
 * RepairBuddy's Time log JavaScript File
 * 
 * @package RepairBuddy - WordPress Plugin
 * @version 3.8224
 */

(function( $ ) {
    'use strict';

    // Global variables
    let currentTimer = {
        running: false,
        startTime: null,
        elapsedTime: 0,
        interval: null
    };

    let timeLogs = [];

    const WCRBTIMELOG = {
        init: function() {
            this.initializeTimeLogs();
            this.bindEvents();
            this.startClock();
            this.initializeCharts();
            this.initializeQuickTimeForm();
        },

        initializeTimeLogs: function() {
            this.updateStats();
            this.renderTodayEntries();
            this.updateQuickTimeDefaults();
        },

        initializeCharts: function() {
            this.initializeWeeklyChart();
        },

        initializeWeeklyChart: function() {
            const weeklyCtx = document.getElementById('weeklyTimeChart');
            if (!weeklyCtx) return;
            
            // Check if Chart.js is available
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            // Destroy existing chart if it exists
            if (weeklyCtx.chart) {
                weeklyCtx.chart.destroy();
            }
            
            // Get chart data from localized variable or AJAX
            const chartData = wcrb_timelog_i18n.weekly_chart_data || {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                data: [0, 0, 0, 0, 0, 0, 0]
            };
            
            weeklyCtx.chart = new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: wcrb_timelog_i18n.hours_worked || 'Hours Worked',
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
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: wcrb_timelog_i18n.weekly_hours_chart || 'Weekly Hours Distribution'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y} hours`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: wcrb_timelog_i18n.hours || 'Hours'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + 'h';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        refreshChartData: function(period = 'week') {
            jQuery.ajax({
                url: wcrb_timelog_i18n.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcrb_get_chart_data_tl',
                    nonce: wcrb_timelog_i18n.wcrb_timelog_nonce_field,
                    chart_type: 'weekly_hours',
                    period: period
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.updateChartData(response.data);
                    }
                },
                error: (error) => {
                    console.error('Failed to load chart data:', error);
                }
            });
        },

        updateChartData: function(chartData) {
            const weeklyCtx = document.getElementById('weeklyTimeChart');
            if (!weeklyCtx || !weeklyCtx.chart) return;
            
            weeklyCtx.chart.data.labels = chartData.labels;
            weeklyCtx.chart.data.datasets[0].data = chartData.data;
            weeklyCtx.chart.update();
        },

        bindEvents: function() {
            // Timer controls
            const startTimerBtn = document.getElementById('startTimer');
            const pauseTimerBtn = document.getElementById('pauseTimer');
            const stopTimerBtn  = document.getElementById('stopTimer');

            if (startTimerBtn) {
                startTimerBtn.addEventListener('click', this.startTimer.bind(this));
            }
            if (stopTimerBtn) {
                stopTimerBtn.addEventListener('click', this.stopTimer.bind(this));
            }
            if (pauseTimerBtn) {
                pauseTimerBtn.addEventListener('click', this.pauseTimer.bind(this));
            }

            const jobDeviceSelect = $('#timeLogJobDeviceSelect');
            if (jobDeviceSelect.length) {
                jobDeviceSelect.on('change', function() {
                    const selectedValue = $(this).val();
                    if (selectedValue) {
                        WCRBTIMELOG.redirectUserToTimeLogPage(selectedValue);
                    }
                });
            }

            // Chart period switcher
            const periodButtons = document.querySelectorAll('[data-chart-period]');
            periodButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const period = this.getAttribute('data-chart-period');
                    WCRBTIMELOG.refreshChartData(period);
                    
                    // Update active button
                    periodButtons.forEach(btn => {
                        btn.classList.remove('active');
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline-primary');
                    });
                    this.classList.add('active');
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-primary');
                });
            });

            // Real-time validation for timer fields
            const workDescription = document.getElementById('workDescription');
            const activityType = document.getElementById('activityType');
            
            if (workDescription && activityType && startTimerBtn) {
                // Validation function
                const validateTimerFields = () => {
                    const hasDescription = workDescription.value.trim() !== '';
                    const hasActivity = activityType.value !== '';
                    startTimerBtn.disabled = !(hasDescription && hasActivity);
                    
                    // Add visual feedback using Bootstrap classes
                    if (!hasDescription) {
                        workDescription.classList.add('is-invalid');
                    } else {
                        workDescription.classList.remove('is-invalid');
                        workDescription.classList.add('is-valid');
                    }
                    
                    if (!hasActivity) {
                        activityType.classList.add('is-invalid');
                    } else {
                        activityType.classList.remove('is-invalid');
                        activityType.classList.add('is-valid');
                    }
                };
                
                // Attach event listeners
                workDescription.addEventListener('input', validateTimerFields);
                activityType.addEventListener('change', validateTimerFields);
                
                // Initial validation on page load
                validateTimerFields();
            }

            // Chart period dropdown
            const periodDropdownItems = document.querySelectorAll('[data-chart-period]');
            const periodDropdownButton = document.querySelector('.dropdown-toggle[data-bs-toggle="dropdown"]');
            
            periodDropdownItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const period = this.getAttribute('data-chart-period');
                    const periodText = this.textContent.trim();
                    
                    // Update dropdown button text
                    if (periodDropdownButton) {
                        // Remove icon if you want to keep just text
                        periodDropdownButton.innerHTML = periodText;
                    }
                    
                    // Update active state
                    periodDropdownItems.forEach(dropdownItem => {
                        dropdownItem.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Refresh chart data
                    WCRBTIMELOG.refreshChartData(period);
                });
            });
        },

        startTimer: function() {
            // Validate required fields before starting timer
            const workDescription = document.getElementById('workDescription');
            const activityType = document.getElementById('activityType');
            
            if ( ! currentTimer.running ) {
                // Validation checks
                if (!workDescription || !workDescription.value.trim()) {
                    this.showAlert(wcrb_timelog_i18n.work_description_required, 'danger');
                    
                    // Add visual feedback if fields exist
                    if (workDescription) {
                        workDescription.classList.add('is-invalid');
                        workDescription.focus();
                    }
                    return;
                }
                
                if (!activityType || !activityType.value) {
                    this.showAlert(wcrb_timelog_i18n.activity_type_required, 'danger');
                    
                    // Add visual feedback if field exists
                    if (activityType) {
                        activityType.classList.add('is-invalid');
                        activityType.focus();
                    }
                    return;
                }
                
                // Remove validation classes since we passed validation
                if (workDescription) {
                    workDescription.classList.remove('is-invalid');
                    workDescription.classList.add('is-valid');
                }
                
                if (activityType) {
                    activityType.classList.remove('is-invalid');
                    activityType.classList.add('is-valid');
                }
                
                currentTimer.running = true;
                
                // If we have elapsed time (from pause), adjust startTime to account for it
                if (currentTimer.elapsedTime > 0) {
                    currentTimer.startTime = new Date(Date.now() - currentTimer.elapsedTime);
                } else {
                    currentTimer.startTime = new Date();
                }
                
                currentTimer.interval = setInterval(this.updateTimerDisplay.bind(this), 1000);

                // Update UI
                document.getElementById('startTimer').disabled      = true;
                document.getElementById('workDescription').disabled = true;
                document.getElementById('activityType').disabled    = true;
                document.getElementById('isBillable').disabled      = true;
                document.getElementById('pauseTimer').disabled      = false;
                document.getElementById('stopTimer').disabled       = false;
                document.getElementById('timerStatus').textContent  = wcrb_timelog_i18n.timer_running;
                document.getElementById('timerStatus').className    = 'badge bg-success float-end';
                
                document.getElementById('startTime').textContent    = currentTimer.startTime.toLocaleTimeString();
                document.getElementById('currentTimeEntry').classList.add('active');
                document.getElementById('currentTimeEntry').classList.remove('paused');
                
                // Properly disable Select2 dropdown
                const $jobDeviceSelect = $('#timeLogJobDeviceSelect');
                if ($jobDeviceSelect.length) {
                    $jobDeviceSelect.prop('disabled', true).trigger('change.select2');
                }
                
                this.showAlert(wcrb_timelog_i18n.timer_started, 'success');
            }
        },

        pauseTimer: function() {
            if (currentTimer.running) {
                currentTimer.running = false;
                clearInterval(currentTimer.interval);
                
                // Calculate and store elapsed time when pausing
                if (currentTimer.startTime) {
                    currentTimer.elapsedTime = Date.now() - currentTimer.startTime.getTime();
                }
                
                // Update UI
                document.getElementById('startTimer').disabled = false;
                document.getElementById('pauseTimer').disabled = true;
                document.getElementById('timerStatus').textContent = wcrb_timelog_i18n.timer_paused;
                document.getElementById('timerStatus').className = 'badge bg-warning float-end';
                document.getElementById('currentTimeEntry').classList.remove('active');
                document.getElementById('currentTimeEntry').classList.add('paused');
                
                this.showAlert(wcrb_timelog_i18n.timer_paused, 'warning');
            }
        },

        stopTimer: function() {
            // Validate required fields before stopping timer
            const workDescription = document.getElementById('workDescription');
            const activityType = document.getElementById('activityType');
            const isBillableCheckbox = document.getElementById('isBillable');
            
            if (!workDescription || !workDescription.value.trim()) {
                this.showAlert(wcrb_timelog_i18n.work_description_required, 'danger');
                
                if (workDescription) {
                    workDescription.classList.add('is-invalid');
                    workDescription.focus();
                }
                return;
            }
            
            if (!activityType || !activityType.value) {
                this.showAlert(wcrb_timelog_i18n.activity_type_required, 'danger');
                
                if (activityType) {
                    activityType.classList.add('is-invalid');
                    activityType.focus();
                }
                return;
            }
            
            if (currentTimer.startTime) {
                const endTime = new Date();
                const duration = Math.floor((endTime - currentTimer.startTime) / 1000 / 60); // minutes
                
                // Get selected job/device from dropdown
                const jobDeviceValue = $('#timeLogJobDeviceSelect').val();
                let jobId = '';
                let deviceId = '';
                let deviceSerial = '';
                let deviceIndex = '0';
                
                if (jobDeviceValue) {
                    const parts = jobDeviceValue.split('|');
                    jobId = parts[0] || '';
                    
                    if (parts[1] && parts[1] !== '0') {
                        if (parts[1].includes('-')) {
                            const deviceParts = parts[1].split('-');
                            deviceId = deviceParts[0] || '';
                            deviceSerial = deviceParts[1] || '';
                        } else {
                            deviceId = parts[1];
                        }
                    }
                    deviceIndex = parts[2] || '0';
                }
                
                // Get is_billable value correctly
                const isBillable = isBillableCheckbox.value;
                
                // Show loading state
                const stopBtn = document.getElementById('stopTimer');
                const originalText = stopBtn.innerHTML;
                stopBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                stopBtn.disabled = true;
                
                // Send data via AJAX
                jQuery.ajax({
                    url: wcrb_timelog_i18n.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wcrb_save_time_entry',
                        nonce: wcrb_timelog_i18n.wcrb_timelog_nonce_field,
                        // Send as nested array like PHP expects
                        time_log_data: {
                            start_time: currentTimer.startTime.toISOString(),
                            end_time: endTime.toISOString(),
                            activity: String(activityType.value),
                            priority: 'medium',
                            work_description: String(workDescription.value.trim()),
                            technician_id: wcrb_timelog_i18n.current_user_id || 0, // Add this
                            job_id: Number(jobId) || 0,
                            device_id: deviceId,
                            device_serial: deviceSerial,
                            total_minutes: Number(duration),
                            is_billable: isBillable
                        }
                    },
                    success: (response) => {
                        // Restore button state
                        stopBtn.innerHTML = originalText;
                        stopBtn.disabled = false;
                        
                        if (response.success) {
                            // Show success message
                            this.showAlert(response.data.message || wcrb_timelog_i18n.time_entry_saved.replace('%d', duration), 'success');
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                            
                        } else {
                            // Restore button state already done above
                            this.showAlert(response.data || wcrb_timelog_i18n.save_error, 'danger');
                            this.resetTimer();

                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        }
                    },
                });
            }
        },

        resetTimer: function() {
            // Just clear the timer interval and reset variables
            currentTimer.running = false;
            currentTimer.startTime = null;
            currentTimer.elapsedTime = 0;
            clearInterval(currentTimer.interval);
            
            // Optionally reset the timer display
            document.getElementById('currentTimer').textContent = '00:00:00';
            document.getElementById('startTime').textContent = '--:--';
            document.getElementById('timerStatus').textContent = wcrb_timelog_i18n.timer_stopped;
            document.getElementById('timerStatus').className = 'badge bg-secondary float-end';
            
            // Clear the form fields if you want
            document.getElementById('workDescription').value = '';
            document.getElementById('activityType').value = '';
            
            // Re-enable job/device select
            const $jobDeviceSelect = $('#timeLogJobDeviceSelect');
            if ($jobDeviceSelect.length) {
                $jobDeviceSelect.prop('disabled', false).trigger('change.select2');
            }
        },

        updateTimerDisplay: function() {
            if (currentTimer.running && currentTimer.startTime) {
                const now = new Date();
                const diff = now - currentTimer.startTime;
                
                const hours = Math.floor(diff / 3600000);
                const minutes = Math.floor((diff % 3600000) / 60000);
                const seconds = Math.floor((diff % 60000) / 1000);
                
                document.getElementById('currentTimer').textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        },

        initializeQuickTimeForm: function() {
            const quickTimeForm = document.getElementById('quickTimeForm');
            if (!quickTimeForm) return;
            
            quickTimeForm.addEventListener('submit', this.handleQuickTimeSubmit.bind(this));
            
            // Set default times
            this.setQuickTimeDefaults();
        },

        /**
         * Set default times for quick time form
         */
        setQuickTimeDefaults: function() {
            const now = new Date();
            const oneHourLater = new Date(now.getTime() + (60 * 60 * 1000));
            
            const quickStartTime = document.getElementById('quickStartTime');
            const quickEndTime = document.getElementById('quickEndTime');
            
            if (quickStartTime) {
                quickStartTime.value = this.formatDateTimeLocal(now);
            }
            if (quickEndTime) {
                quickEndTime.value = this.formatDateTimeLocal(oneHourLater);
            }
        },

        handleQuickTimeSubmit: function(e) {
            e.preventDefault();
            
            const form = e.target;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Show loading state
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + 
                                    (wcrb_timelog_i18n.saving_time_entry || 'Saving...');
            submitButton.disabled = true;
            
            // Get form data
            const formData = new FormData(form);
            const formDataObj = {};
            
            // Convert FormData to object
            for (let [key, value] of formData.entries()) {
                formDataObj[key] = value;
            }
            
            // Get and convert datetime-local values to ISO strings
            const startTimeInput = formDataObj.manual_start_time;
            const endTimeInput = formDataObj.manual_end_time;
            
            if (!startTimeInput || !endTimeInput) {
                this.showAlert(wcrb_timelog_i18n.enter_start_end_times || 'Please enter both start and end times.', 'danger');
                this.resetQuickTimeButton(submitButton, originalButtonText);
                return;
            }
            
            // Convert datetime-local to Date objects
            const start = new Date(startTimeInput);
            const end = new Date(endTimeInput);
            const diffMs = end - start;
            const totalMinutes = Math.round(diffMs / (1000 * 60));
            
            if (totalMinutes <= 0) {
                this.showAlert(wcrb_timelog_i18n.end_time_after_start || 'End time must be after start time.', 'danger');
                this.resetQuickTimeButton(submitButton, originalButtonText);
                return;
            }
            
            // Convert to ISO strings like stopTimer does
            const startTimeISO = start.toISOString();
            const endTimeISO = end.toISOString();
            
            const timeLogData = {
                start_time: startTimeISO, // Use ISO string
                end_time: endTimeISO, // Use ISO string
                total_minutes: totalMinutes,
                activity: formDataObj.timelog_activity_type || '',
                work_description: formDataObj.manual_entry_description || '',
                job_id: parseInt(formDataObj.jobId) || 0,
                device_id: formDataObj.deviceId || '',
                device_serial: formDataObj.deviceSerial || '',
                device_index: formDataObj.deviceIndex || '0',
                is_billable: formDataObj.isBillable_manual || '1',
                priority: 'medium',
                work_description: formDataObj.manual_entry_description || '',
                time_type: 'manual_entry',
                technician_id: wcrb_timelog_i18n.current_user_id || 0
            };
            
            // Validate required fields
            const requiredFields = ['job_id', 'activity', 'work_description'];
            for (const field of requiredFields) {
                if (!timeLogData[field]) {
                    const fieldNames = {
                        'job_id': 'Job ID',
                        'activity': 'Activity Type',
                        'work_description': 'Description'
                    };
                    this.showAlert(`${fieldNames[field] || field} is required.`, 'danger');
                    this.resetQuickTimeButton(submitButton, originalButtonText);
                    return;
                }
            }
            
            // Send AJAX request - SAME FORMAT AS stopTimer
            jQuery.ajax({
                url: wcrb_timelog_i18n.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcrb_save_time_entry',
                    nonce: formDataObj.wcrb_timelog_nonce_field,
                    time_log_data: timeLogData
                },
                success: (response) => {
                    if (response.success) {
                        // Show success message briefly then reload page
                        this.showAlert(
                            response.data.message || 
                            `Time entry saved! (${totalMinutes} minutes)`, 
                            'success'
                        );
                        
                        // Reload page after 1.5 seconds
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                        
                    } else {
                        this.showAlert(response.data || 'Failed to save time entry.', 'danger');
                        this.resetQuickTimeButton(submitButton, originalButtonText);
                    }
                },
                error: (xhr, status, error) => {
                    this.showAlert('An error occurred. Please try again.', 'danger');
                    this.resetQuickTimeButton(submitButton, originalButtonText);
                }
            });
        },

        /**
         * Reset quick time form button state
         * @param {HTMLElement} button - Submit button
         * @param {string} originalText - Original button HTML
         */
        resetQuickTimeButton: function(button, originalText) {
            if (button) {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        },


        renderTodayEntries: function() {
            //Will update today entries on submissions
        },

        updateStats: function() {
            //States update will work differently based on actual data source
        },

        updateQuickTimeDefaults: function() {
            const now = new Date();
            const oneHourAgo = new Date(now.getTime() - 60 * 60 * 1000);
            
            const quickStartTime = document.getElementById('quickStartTime');
            const quickEndTime = document.getElementById('quickEndTime');
            
            if (quickStartTime) quickStartTime.value = this.formatDateTimeLocal(oneHourAgo);
            if (quickEndTime) quickEndTime.value = this.formatDateTimeLocal(now);
        },

        formatDateTimeLocal: function(date) {
            // Handle timezone offset properly
            const offset = date.getTimezoneOffset() * 60000;
            const localDate = new Date(date.getTime() - offset);
            return localDate.toISOString().slice(0, 16);
        },

        redirectUserToTimeLogPage: function(selectedValue) {
            // Parse the selected value: "job_id|device_id-device_serial|device_index"
            const parts = selectedValue.split('|');
            const job_id = parts[0];
            
            let device_id = '';
            let device_serial = '';
            const device_index = parts[2] || '0';
            
            // Parse device part which could be "device_id" or "device_id-device_serial"
            const devicePart = parts[1];
            if (devicePart && devicePart !== '0') {
                if (devicePart.includes('-')) {
                    const deviceParts = devicePart.split('-');
                    device_id = deviceParts[0];
                    device_serial = deviceParts[1];
                } else {
                    device_id = devicePart;
                }
            }
            
            const baseUrl = wcrb_timelog_i18n.timelog_page_url;
            const url = new URL(baseUrl, window.location.origin);
            
            // Add screen parameter
            url.searchParams.set('screen', 'timelog');
            
            // Add job parameters
            url.searchParams.set('job_id', job_id);
            if (device_id) {
                url.searchParams.set('device_id', device_id);
            }
            if (device_serial) {
                url.searchParams.set('device_serial', device_serial);
            }
            if (device_index && device_index !== '0') {
                url.searchParams.set('device_index', device_index);
            }
            
            window.location.href = url.toString();
        },

        startClock: function() {
            setInterval(() => {
                const now = new Date();
                const currentTimeEl = document.getElementById('currentTime');
                if (currentTimeEl) {
                    currentTimeEl.textContent = now.toLocaleTimeString();
                }
            }, 1000);
        },

        escapeHtml: function(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return unsafe.toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },

        showAlert: function(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                ${this.escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alert);
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
    };

    // Initialize when DOM is ready
    document.addEventListener( 'DOMContentLoaded', function() {
        WCRBTIMELOG.init();
    } );

    // Export for global access
    window.WCRBTIMELOG = WCRBTIMELOG;

})( jQuery );