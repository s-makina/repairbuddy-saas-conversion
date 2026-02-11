jQuery(document).ready(function($) {
    let calendar;
    let currentFilter = 'all';
    let currentDateField = 'pickup_date';
    
    // Initialize calendar
    function initCalendar() {
        const calendarEl = document.getElementById('frontend-calendar');
        
        if (!calendarEl) return;
        
        // Destroy existing calendar if any
        if (calendar) {
            calendar.destroy();
        }
        
        // Get locale from PHP variable
        let locale = typeof calendar_frontend_vars !== 'undefined' ? calendar_frontend_vars.locale || 'en-US' : 'en-US';
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            expandRows: true,
            slotMinTime: '07:00',
            slotMaxTime: '21:00',
            buttonText: {
                today: typeof calendar_frontend_vars !== 'undefined' && calendar_frontend_vars.today ? calendar_frontend_vars.today : 'Today',
                month: typeof calendar_frontend_vars !== 'undefined' && calendar_frontend_vars.month ? calendar_frontend_vars.month : 'Month',
                week: typeof calendar_frontend_vars !== 'undefined' && calendar_frontend_vars.week ? calendar_frontend_vars.week : 'Week',
                day: typeof calendar_frontend_vars !== 'undefined' && calendar_frontend_vars.day ? calendar_frontend_vars.day : 'Day',
                list: typeof calendar_frontend_vars !== 'undefined' && calendar_frontend_vars.list ? calendar_frontend_vars.list : 'List'
            },
            headerToolbar: {
                left: 'prevYear,prev,next,nextYear today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            height: 'auto',
            initialView: 'dayGridMonth',
            initialDate: new Date(),
            locale: locale,
            navLinks: true,
            editable: false,
            selectable: true,
            nowIndicator: true,
            dayMaxEvents: 10,
            events: function(fetchInfo, successCallback, failureCallback) {
                $('#calendar-loading').removeClass('d-none').addClass('d-flex');
                
                // Format dates to YYYY-MM-DD (strip timezone info)
                var startDate = fetchInfo.startStr.split('T')[0];
                var endDate = fetchInfo.endStr.split('T')[0];
                
                // Use localized ajax_url or fallback
                var ajaxUrl = typeof calendar_frontend_vars !== 'undefined' ? 
                    calendar_frontend_vars.ajax_url : 
                    (typeof ajax_obj !== 'undefined' ? ajax_obj.ajax_url : '/wp-admin/admin-ajax.php');
                
                var nonce = typeof calendar_frontend_vars !== 'undefined' ? 
                    calendar_frontend_vars.nonce : '';
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wcrb_get_frontend_calendar_events',
                        security: nonce,
                        start: startDate,
                        end: endDate,
                        filter: currentFilter,
                        date_field: currentDateField
                    },
                    success: function(response) {
                        $('#calendar-loading').removeClass('d-flex').addClass('d-none');
                        if (response.success) {
                            successCallback(response.data);
                            updateStats(response.data);
                        } else {
                            successCallback([]);
                        }
                    }
                });
            },
            eventDidMount: function(info) {
                // Add Bootstrap tooltip with full extendedProps.tooltip content
                if (info.event.extendedProps && info.event.extendedProps.tooltip) {
                    // Create a custom tooltip container
                    $(info.el).attr({
                        'data-bs-toggle': 'tooltip',
                        'data-bs-html': 'true',
                        'data-bs-custom-class': 'calendar-tooltip',
                        'data-bs-title': info.event.extendedProps.tooltip,
                        'data-bs-placement': 'top'
                    });
                    
                    // Initialize Bootstrap tooltip if available
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        new bootstrap.Tooltip(info.el, {
                            html: true,
                            customClass: 'calendar-tooltip',
                            placement: 'top'
                        });
                    } else {
                        // Fallback to native title attribute
                        info.el.title = info.event.extendedProps.tooltip;
                    }
                }
                
                // Add status class if available
                if (info.event.extendedProps && info.event.extendedProps.status) {
                    const statusClass = 'status-' + info.event.extendedProps.status.toLowerCase().replace(/\s+/g, '');
                    info.el.classList.add(statusClass);
                }
            },
            eventClick: function(info) {
                if (info.event.url) {
                    info.jsEvent.preventDefault();
                    window.open(info.event.url, "_blank");
                }
            },
            eventContent: function(arg) {
                // Custom event content with Bootstrap-like styling
                var container = document.createElement('div');
                container.className = 'd-flex flex-column';
                
                var title = document.createElement('div');
                title.className = 'fc-event-title fw-medium';
                title.innerText = arg.event.title || 'No title';
                title.style.fontSize = '0.875rem';
                title.style.lineHeight = '1.2';
                
                var status = document.createElement('div');
                status.className = 'fc-event-status text-muted';
                status.style.fontSize = '0.8125rem';
                status.style.opacity = '0.8';
                status.style.marginTop = '1px';
                
                if (arg.event.extendedProps && arg.event.extendedProps.status) {
                    status.innerText = arg.event.extendedProps.status;
                }
                
                container.appendChild(title);
                if (arg.event.extendedProps && arg.event.extendedProps.status) {
                    container.appendChild(status);
                }
                
                return { domNodes: [container] };
            }
        });
        
        calendar.render();
    }
    
    // Update statistics - only count jobs and estimates
    function updateStats(events) {
        let totalJobs = 0;
        let totalEstimates = 0;
        
        events.forEach(function(event) {
            if (event.extendedProps && event.extendedProps.type === 'job') {
                totalJobs++;
            } else if (event.extendedProps && event.extendedProps.type === 'estimate') {
                totalEstimates++;
            }
        });
        
        $('#total-jobs').text(totalJobs);
        $('#total-estimates').text(totalEstimates);
    }
    
    // Event listeners
    $('#calendar-filter').change(function() {
        currentFilter = $(this).val();
        if (calendar) {
            calendar.refetchEvents();
        }
    });
    
    // Date field button handlers
    $('.date-field-btn').click(function() {
        $('.date-field-btn').removeClass('active').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('active btn-primary');
        currentDateField = $(this).data('field');
        $('#date-field').val(currentDateField);
        
        if (calendar) {
            calendar.refetchEvents();
        }
    });
    
    $('#refresh-calendar').click(function() {
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
        if (calendar) {
            calendar.refetchEvents();
            setTimeout(() => {
                $(this).prop('disabled', false).text('Refresh');
            }, 1000);
        }
    });
    
    // Check if FullCalendar is loaded
    if (typeof FullCalendar === 'undefined') {
        $('#frontend-calendar').html(
            '<div class="alert alert-danger">' +
            'Calendar library failed to load. Please refresh the page.' +
            '</div>'
        );
        return;
    }
    
    // Check if user is logged in (if needed)
    if (typeof calendar_frontend_vars !== 'undefined' && !calendar_frontend_vars.is_user_logged_in) {
        $('#frontend-calendar').html(
            '<div class="alert alert-warning">' +
            'Please log in to view the calendar.' +
            '</div>'
        );
        return;
    }
    
    // Initialize calendar
    initCalendar();
    
    // Handle window resize
    $(window).on('resize', function() {
        if (calendar) {
            setTimeout(() => {
                calendar.updateSize();
            }, 250);
        }
    });
});