/**
 * WordPress Dashboard Functionality
 * 
 * @package RepairBuddy - WordPress Plugin
 * @version 3.8230
 */

(function( $ ) {
    'use strict';

    const WordPressDashboard = {

        /**
         * Chart instances storage
         */
        charts: {
            revenue: null,
            jobStatus: null,
            deviceType: null,
            performance: null,
            customerJobs: null,
            customerStatus: null
        },

        /**
         * Current chart period
         */
        currentPeriod: 'weekly',

        /**
         * Track if already initialized
         */
        initialized: false,

        /**
         * Initialize dashboard functionality
         */
        init: function() {
            // Prevent double initialization
            if (this.initialized) {
                return;
            }
            
            // Mark as initialized
            this.initialized = true;
            
            // First, destroy any existing charts
            this.destroyAllCharts();
            
            // Then load real data and initialize charts
            this.loadRealData().then(() => {
                this.initializeCharts();
                this.initializeEventListeners();
                this.loadUserPreferences();
                this.bindWordPressHooks();
                this.startBootstrapTooltips();
            }).catch(error => {
                // Initialize with empty charts
                this.initializeCharts();
                this.initializeEventListeners();
                this.loadUserPreferences();
                this.bindWordPressHooks();
                this.startBootstrapTooltips();
            });
        },

        /**
         * Destroy all existing charts
         */
        destroyAllCharts: function() {
            Object.keys(this.charts).forEach(chartName => {
                if (this.charts[chartName] !== null && this.charts[chartName] !== undefined) {
                    try {
                        this.charts[chartName].destroy();
                        this.charts[chartName] = null;
                    } catch (error) {
                        // Silent error
                    }
                }
            });
        },

        /**
         * Load real data from server
         */
        loadRealData: function() {
            return new Promise((resolve, reject) => {
                // Use wcrbAjax if available, otherwise wcrb_ajax
                const ajaxObject = window.wcrbAjax || window.wcrb_ajax;
                
                if (!ajaxObject || !ajaxObject.ajax_url) {
                    resolve();
                    return;
                }

                $.ajax({
                    url: ajaxObject.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wcrb_get_chart_data',
                        period: this.currentPeriod,
                        nonce: ajaxObject.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            // Store only dynamic chart data (revenue and customerJobs)
                            window.myPluginChartData = {
                                revenue: response.data.revenue || null,
                                customerJobs: response.data.customerJobs || null
                            };
                            
                            // Store static chart data separately
                            window.staticChartData = {
                                jobStatus: response.data.jobStatus || null,
                                deviceType: response.data.deviceType || null,
                                performance: response.data.performance || null,
                                customerStatus: response.data.customerStatus || null
                            };
                            resolve();
                        } else {
                            reject(new Error('Server returned error'));
                        }
                    }
                });
            });
        },

        /**
         * Safe translation function
         */
        __: function( text ) {
            if ( typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ) {
                return wp.i18n.__( text, 'computer-repair-shop' );
            }
            return text;
        },

        /**
         * Initialize all charts
         */
        initializeCharts: function() {
            const revenueChart = document.getElementById('revenueChart');
            const customerJobsChart = document.getElementById('customerJobsChart');
            
            // Check if we're on staff or customer dashboard
            if (revenueChart) {
                // Staff dashboard
                this.initializeRevenueChart();
                this.initializeJobStatusChart();
            } else if (customerJobsChart) {
                // Customer dashboard
                this.initializeCustomerJobsChart();
                this.initializeCustomerStatusChart();
            }
            
            // These charts exist on both dashboards
            if (document.getElementById('deviceTypeChart')) {
                this.initializeDeviceTypeChart();
            }
            
            if (document.getElementById('performanceChart')) {
                this.initializePerformanceChart();
            }
        },

        /**
         * Initialize Revenue Chart
         */
        initializeRevenueChart: function() {
            const revenueCtx = document.getElementById('revenueChart');
            
            if (!revenueCtx) {
                return;
            }

            // Get data from WordPress
            const chartData = this.getRevenueChartData();

            try {
                // Destroy existing chart if it exists
                if (this.charts.revenue !== null && this.charts.revenue !== undefined) {
                    try {
                        this.charts.revenue.destroy();
                    } catch (error) {
                        // Silent error
                    }
                }

                // Check if Chart.js is available
                if (typeof Chart === 'undefined') {
                    return;
                }

                // Ensure we have arrays
                const labels = Array.isArray(chartData.labels) ? chartData.labels : [];
                const revenue = Array.isArray(chartData.revenue) ? chartData.revenue : [];
                const jobs = Array.isArray(chartData.jobs) ? chartData.jobs : [];
                
                // If no data, show placeholder
                if (labels.length === 0 || (revenue.length === 0 && jobs.length === 0)) {
                    labels.push('No Data');
                    revenue.push(0);
                    jobs.push(0);
                }

                this.charts.revenue = new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: this.__('Revenue'),
                            data: revenue,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#0d6efd',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }, {
                            label: this.__('Jobs Completed'),
                            data: jobs,
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1',
                            pointBackgroundColor: '#198754',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: {
                                    size: 14
                                },
                                bodyFont: {
                                    size: 13
                                },
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            if (context.datasetIndex === 0) {
                                                // Revenue - format as currency
                                                return label + new Intl.NumberFormat('en-US', {
                                                    style: 'currency',
                                                    currency: 'USD'
                                                }).format(context.parsed.y);
                                            } else {
                                                // Jobs - format as integer
                                                return label + Math.round(context.parsed.y) + ' jobs';
                                            }
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: this.__('Revenue'),
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('en-US', {
                                            style: 'currency',
                                            currency: 'USD',
                                            minimumFractionDigits: 0,
                                            maximumFractionDigits: 0
                                        }).format(value);
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: this.__('Jobs Completed'),
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                },
                                grid: {
                                    drawOnChartArea: false
                                },
                                ticks: {
                                    callback: function(value) {
                                        return Math.round(value) + '';
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    maxRotation: 0
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                this.charts.revenue = null;
            }
        },

        /**
         * Initialize Job Status Chart
         */
        initializeJobStatusChart: function() {
            const jobStatusCtx = document.getElementById( 'jobStatusChart' );
            
            if ( ! jobStatusCtx ) {
                return;
            }

            const chartData = this.getJobStatusData();

            try {
                // Destroy existing chart if it exists
                if (this.charts.jobStatus !== null && this.charts.jobStatus !== undefined) {
                    try {
                        this.charts.jobStatus.destroy();
                    } catch (error) {
                        // Silent error
                    }
                }

                this.charts.jobStatus = new Chart( jobStatusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: [
                            this.__( 'Completed' ),
                            this.__( 'In Progress' ),
                            this.__( 'Pending' ),
                            this.__( 'Cancelled' )
                        ],
                        datasets: [{
                            data: chartData,
                            backgroundColor: [
                                '#198754',
                                '#0dcaf0',
                                '#ffc107',
                                '#dc3545'
                            ],
                            borderWidth: 3,
                            borderColor: '#fff',
                            hoverBorderWidth: 4,
                            hoverBorderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: {
                                        size: 13
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: {
                                    size: 14
                                },
                                bodyFont: {
                                    size: 13
                                },
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${label}: ${value} jobs (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '65%'
                    }
                } );
            } catch (error) {
                this.charts.jobStatus = null;
            }
        },

        /**
         * Initialize Device Type Chart
         */
        initializeDeviceTypeChart: function() {
            const deviceTypeCtx = document.getElementById( 'deviceTypeChart' );
            
            if ( ! deviceTypeCtx ) {
                return;
            }

            const chartData = this.getDeviceTypeData();

            try {
                // Destroy existing chart if it exists
                if (this.charts.deviceType !== null && this.charts.deviceType !== undefined) {
                    try {
                        this.charts.deviceType.destroy();
                    } catch (error) {
                        // Silent error
                    }
                }

                this.charts.deviceType = new Chart( deviceTypeCtx, {
                    type: 'pie',
                    data: {
                        labels: chartData.labels || ['No Data'],
                        datasets: [{
                            data: chartData.data || [1],
                            backgroundColor: [
                                '#0d6efd',
                                '#6f42c1',
                                '#d63384',
                                '#fd7e14',
                                '#20c997',
                                '#6610f2',
                                '#6c757d',
                                '#198754'
                            ],
                            borderWidth: 3,
                            borderColor: '#fff',
                            hoverBorderWidth: 4,
                            hoverBorderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: {
                                        size: 13
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: {
                                    size: 14
                                },
                                bodyFont: {
                                    size: 13
                                },
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${label}: ${value} jobs (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                } );
            } catch (error) {
                this.charts.deviceType = null;
            }
        },

        /**
         * Initialize Performance Chart
         */
        initializePerformanceChart: function() {
            const performanceCtx = document.getElementById('performanceChart');
            
            if (!performanceCtx) {
                return;
            }

            const chartData = this.getPerformanceData();
            
            // Check if we have valid data
            if (!chartData.data || !Array.isArray(chartData.data) || chartData.data.length === 0) {
                // Use dummy data for testing
                chartData.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
                chartData.data = [3.2, 2.8, 2.5, 2.1, 1.9, 1.7];
            }

            try {
                // Destroy existing chart if it exists
                if (this.charts.performance !== null && this.charts.performance !== undefined) {
                    try {
                        this.charts.performance.destroy();
                    } catch (error) {
                        // Silent error
                    }
                }

                this.charts.performance = new Chart(performanceCtx, {
                    type: 'bar',
                    data: {
                        labels: chartData.labels || [],
                        datasets: [{
                            label: this.__('Average Repair Time'),
                            data: chartData.data || [],
                            backgroundColor: 'rgba(13, 202, 240, 0.8)',
                            borderColor: 'rgba(13, 202, 240, 1)',
                            borderWidth: 2,
                            borderRadius: 4,
                            hoverBackgroundColor: 'rgba(13, 202, 240, 1)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: {
                                    size: 14
                                },
                                bodyFont: {
                                    size: 13
                                },
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        const value = context.parsed.y;
                                        if (value === null || value === undefined) {
                                            return 'No data';
                                        }
                                        return `Average: ${value.toFixed(1)} days`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: this.__('Average Repair Time (Days)'),
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value.toFixed(1) + ' days';
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                // Silent error
            }
        },

        /**
         * Initialize Customer Jobs Chart
         */
        initializeCustomerJobsChart: function() {
            const customerJobsCtx = document.getElementById('customerJobsChart');
            
            if (!customerJobsCtx) {
                return;
            }

            const chartData = this.getCustomerChartData();

            try {
                // Destroy existing chart if it exists
                if (this.charts.customerJobs) {
                    try {
                        this.charts.customerJobs.destroy();
                    } catch (error) {
                        console.warn('Error destroying existing customer jobs chart:', error);
                    }
                }

                this.charts.customerJobs = new Chart(customerJobsCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels || [],
                        datasets: [{
                            label: 'My Jobs',
                            data: chartData.jobCounts || [],
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }, {
                            label: 'Jobs Completed',
                            data: chartData.completedJobs || [],
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += context.parsed.y + ' jobs';
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Jobs Created'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value + ' jobs';
                                    }
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Jobs Completed'
                                },
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                // Silent error
            }
        },

        /**
         * Initialize Customer Status Chart
         */
        initializeCustomerStatusChart: function() {
            const customerStatusCtx = document.getElementById('customerStatusChart');
            
            if (!customerStatusCtx) {
                return;
            }

            const chartData = this.getCustomerStatusData();

            try {
                // Destroy existing chart if it exists
                if (this.charts.customerStatus) {
                    try {
                        this.charts.customerStatus.destroy();
                    } catch (error) {
                        console.warn('Error destroying existing customer status chart:', error);
                    }
                }

                this.charts.customerStatus = new Chart(customerStatusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: [
                            this.__('Completed'),
                            this.__('In Progress'),
                            this.__('Pending Estimates'),
                            this.__('Cancelled')
                        ],
                        datasets: [{
                            data: chartData,
                            backgroundColor: [
                                '#198754', // Completed - green
                                '#0dcaf0', // In Progress - cyan
                                '#ffc107', // Pending Estimates - yellow
                                '#dc3545'  // Cancelled - red
                            ],
                            borderWidth: 3,
                            borderColor: '#fff',
                            hoverBorderWidth: 4,
                            hoverBorderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: {
                                        size: 13
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: {
                                    size: 14
                                },
                                bodyFont: {
                                    size: 13
                                },
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${label}: ${value} jobs (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '65%'
                    }
                });
            } catch (error) {
                console.error('Error initializing customer status chart:', error);
            }
        },

        /**
         * Get customer chart data
         */
        getCustomerChartData: function() {
            if (window.myPluginChartData && window.myPluginChartData.customerJobs) {
                return window.myPluginChartData.customerJobs;
            }
            
            return {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                jobCounts: [1, 2, 0, 1, 3, 0, 1],
                completedJobs: [0, 1, 0, 0, 1, 0, 0]
            };
        },

        /**
         * Get revenue chart data from server or use defaults
         */
        getRevenueChartData: function() {
            // Check if we have server data
            if (window.myPluginChartData && window.myPluginChartData.revenue) {
                return window.myPluginChartData.revenue;
            }
            // Default empty data structure
            return {
                labels: [],
                revenue: [],
                jobs: []
            };
        },

        /**
         * Get job status data
         */
        getJobStatusData: function() {
            if (window.staticChartData && window.staticChartData.jobStatus) {
                return window.staticChartData.jobStatus;
            }
            return [0, 0, 0, 0];
        },

        /**
         * Get device type data
         */
        getDeviceTypeData: function() {
            if (window.staticChartData && window.staticChartData.deviceType) {
                return window.staticChartData.deviceType;
            }
            return {
                labels: ['No Data'],
                data: [1]
            };
        },

        /**
         * Get performance data
         */
        getPerformanceData: function() {
            if (window.staticChartData && window.staticChartData.performance) {
                return window.staticChartData.performance;
            }
            return {
                labels: [],
                data: []
            };
        },

        /**
         * Get customer status data
         */
        getCustomerStatusData: function() {
            if (window.staticChartData && window.staticChartData.customerStatus) {
                return window.staticChartData.customerStatus;
            }
            
            return [0, 0, 0, 0];
        },

        /**
         * Initialize event listeners
         */
        initializeEventListeners: function() {
            // Initialize methods if they exist
            if (typeof this.initializeSidebarToggle === 'function') {
                this.initializeSidebarToggle();
            }
            
            if (typeof this.initializeFullscreenToggle === 'function') {
                this.initializeFullscreenToggle();
            }
            
            if (typeof this.initializeThemeSwitcher === 'function') {
                this.initializeThemeSwitcher();
            }
            
            if (typeof this.initializeChartPeriodButtons === 'function') {
                this.initializeChartPeriodButtons();
            }
            
            if (typeof this.initializeOutsideClickHandler === 'function') {
                this.initializeOutsideClickHandler();
            }
            
            if (typeof this.select2initialize === 'function') {
                this.select2initialize();
            }
        },

        /**
         * Initialize chart period buttons
         */
        initializeChartPeriodButtons: function() {
            // For staff dashboard: check revenue chart
            let chartElement = document.getElementById('revenueChart');
            let isCustomerDashboard = false;
            
            // For customer dashboard: check customer jobs chart
            if (!chartElement) {
                chartElement = document.getElementById('customerJobsChart');
                isCustomerDashboard = true;
            }
            
            // If no chart exists on this page, exit
            if (!chartElement) {
                return;
            }
            
            // Find the button group
            const chartCard = chartElement.closest('.card');
            if (!chartCard) {
                return;
            }
            
            const btnGroup = chartCard.querySelector('.btn-group');
            if (!btnGroup) {
                return;
            }
            
            // Use event delegation - listen on the group, not individual buttons
            btnGroup.addEventListener('click', (e) => {
                // Check if a button was clicked
                const button = e.target.closest('.btn');
                if (!button || !btnGroup.contains(button)) {
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                // Remove active class from all buttons in this group
                btnGroup.querySelectorAll('.btn').forEach(b => {
                    b.classList.remove('active');
                });
                
                // Add active class to clicked button
                button.classList.add('active');
                
                // Get period from button text - handle both customer and staff labels
                let period = button.textContent.trim().toLowerCase();
                
                // Map customer labels to period values
                if (isCustomerDashboard) {
                    if (period.includes('last 7 days') || period.includes('7 days')) {
                        this.currentPeriod = 'weekly';
                    } else if (period.includes('last 30 days') || period.includes('30 days')) {
                        this.currentPeriod = 'monthly';
                    } else if (period.includes('last 90 days') || period.includes('90 days')) {
                        this.currentPeriod = 'yearly'; // Using yearly for last 90 days
                    } else {
                        this.currentPeriod = 'yearly'; // Default for customer
                    }
                } else {
                    // Staff dashboard
                    if (period === 'weekly') {
                        this.currentPeriod = 'weekly';
                    } else if (period === 'monthly') {
                        this.currentPeriod = 'monthly';
                    } else if (period === 'yearly') {
                        this.currentPeriod = 'yearly';
                    } else {
                        this.currentPeriod = 'weekly';
                    }
                }
                
                // Reload only dynamic chart data
                this.reloadDynamicChartData(this.currentPeriod);
            });
            
            // Set default active button based on current period
            this.setActiveButtonByPeriod(btnGroup, isCustomerDashboard);
        },

        /**
         * Set active button based on current period
         */
        setActiveButtonByPeriod: function(btnGroup, isCustomerDashboard) {
            const buttons = btnGroup.querySelectorAll('.btn');
            buttons.forEach(button => {
                const text = button.textContent.trim().toLowerCase();
                let matchesPeriod = false;
                
                if (isCustomerDashboard) {
                    // Customer dashboard button matching
                    if (this.currentPeriod === 'weekly' && (text.includes('last 7 days') || text.includes('7 days'))) {
                        matchesPeriod = true;
                    } else if (this.currentPeriod === 'monthly' && (text.includes('last 30 days') || text.includes('30 days'))) {
                        matchesPeriod = true;
                    } else if (this.currentPeriod === 'yearly' && (text.includes('last 90 days') || text.includes('90 days'))) {
                        matchesPeriod = true;
                    }
                } else {
                    // Staff dashboard button matching
                    if (this.currentPeriod === 'weekly' && text === 'weekly') {
                        matchesPeriod = true;
                    } else if (this.currentPeriod === 'monthly' && text === 'monthly') {
                        matchesPeriod = true;
                    } else if (this.currentPeriod === 'yearly' && text === 'yearly') {
                        matchesPeriod = true;
                    }
                }
                
                if (matchesPeriod) {
                    button.classList.add('active');
                } else {
                    button.classList.remove('active');
                }
            });
        },

        /**
         * Update only dynamic charts with new data
         */
        updateDynamicCharts: function() {
            // Update revenue chart (staff dashboard)
            if (this.charts.revenue) {
                const revenueData = this.getRevenueChartData();
                this.charts.revenue.data.labels = revenueData.labels;
                this.charts.revenue.data.datasets[0].data = revenueData.revenue;
                this.charts.revenue.data.datasets[1].data = revenueData.jobs;
                this.charts.revenue.update();
            }

            // Update customer jobs chart (customer dashboard)
            if (this.charts.customerJobs) {
                const customerJobsData = this.getCustomerChartData();
                this.charts.customerJobs.data.labels = customerJobsData.labels;
                this.charts.customerJobs.data.datasets[0].data = customerJobsData.jobCounts;
                this.charts.customerJobs.data.datasets[1].data = customerJobsData.completedJobs;
                this.charts.customerJobs.update();
            }
            
            // IMPORTANT: DO NOT update static charts
            // Job Status, Device Type, Performance, and Customer Status charts
            // remain static with total data
        },

        /**
         * Reload only dynamic chart data based on period
         */
        reloadDynamicChartData: function(period) {
            // Use wcrbAjax if available, otherwise wcrb_ajax
            const ajaxObject = window.wcrbAjax || window.wcrb_ajax;
            
            if (!ajaxObject || !ajaxObject.ajax_url) {
                return;
            }

            // Show loading state
            this.showChartLoading();

            $.ajax({
                url: ajaxObject.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcrb_get_chart_data',
                    period: period,
                    nonce: ajaxObject.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Store only dynamic chart data
                        window.myPluginChartData = {
                            revenue: response.data.revenue || null,
                            customerJobs: response.data.customerJobs || null
                        };
                        
                        // Initialize static data only once
                        if (!window.staticChartData) {
                            window.staticChartData = {
                                jobStatus: response.data.jobStatus || null,
                                deviceType: response.data.deviceType || null,
                                performance: response.data.performance || null,
                                customerStatus: response.data.customerStatus || null
                            };
                        }
                        
                        // Update only dynamic charts
                        this.updateDynamicCharts();
                    } else {
                        this.showChartError('Failed to load data');
                    }
                }
            });
        },

        /**
         * Show loading state on charts
         */
        showChartLoading: function() {
            // You can add a loading spinner here if needed
        },

        /**
         * Show error state on charts
         */
        showChartError: function(message) {
            // You can add an error display here if needed
        },

        // ... rest of the methods remain the same (unchanged) ...

        /**
         * Initialize sidebar toggle for mobile
         */
        initializeSidebarToggle: function() {
            const sidebarToggle = document.getElementById( 'sidebarToggle' );
            const sidebar = document.getElementById( 'sidebar' );
            
            if ( sidebarToggle && sidebar ) {
                sidebarToggle.addEventListener( 'click', function( e ) {
                    e.preventDefault();
                    sidebar.classList.toggle( 'show' );
                } );
            }
        },

        /**
         * Initialize fullscreen toggle
         */
        initializeFullscreenToggle: function() {
            const fullscreenToggle = document.getElementById( 'fullscreenToggle' );
            if ( fullscreenToggle ) {
                fullscreenToggle.addEventListener( 'click', this.toggleFullscreen.bind( this ) );
            }
        },

        /**
         * Fullscreen functionality
         */
        toggleFullscreen: function() {
            if ( ! document.fullscreenElement ) {
                document.documentElement.requestFullscreen().catch( err => {
                    // Silent error
                } );
                document.body.classList.add( 'fullscreen' );
            } else {
                if ( document.exitFullscreen ) {
                    document.exitFullscreen();
                    document.body.classList.remove( 'fullscreen' );
                }
            }
        },

        /**
         * Initialize theme switcher
         */
        initializeThemeSwitcher: function() {
            const themeOptions = document.querySelectorAll( '.theme-option' );
            if (themeOptions.length > 0) {
                themeOptions.forEach( option => {
                    option.addEventListener( 'click', function( e ) {
                        e.preventDefault();
                        const theme = this.getAttribute( 'data-theme' );
                        WordPressDashboard.setTheme( theme );
                    } );
                } );
            }
        },

        /**
         * Initialize outside click handler for mobile sidebar
         */
        initializeOutsideClickHandler: function() {
            const sidebar = document.getElementById( 'sidebar' );
            const sidebarToggle = document.getElementById( 'sidebarToggle' );

            if (sidebar && sidebarToggle) {
                document.addEventListener( 'click', function( e ) {
                    if ( window.innerWidth < 768 && sidebar.classList.contains( 'show' ) ) {
                        if ( ! sidebar.contains( e.target ) && ! sidebarToggle.contains( e.target ) ) {
                            sidebar.classList.remove( 'show' );
                        }
                    }
                } );
            }
        },

        /**
         * Theme management
         */
        setTheme: function( theme ) {
            const html = document.documentElement;
            const darkModeCSS = document.getElementById( 'dark-mode-css' );
            
            // Save theme preference
            localStorage.setItem( 'wcrb_theme', theme );
            
            switch( theme ) {
                case 'dark':
                    html.setAttribute( 'data-bs-theme', 'dark' );
                    if ( darkModeCSS ) darkModeCSS.disabled = false;
                    break;
                case 'light':
                    html.setAttribute( 'data-bs-theme', 'light' );
                    if ( darkModeCSS ) darkModeCSS.disabled = true;
                    break;
                case 'auto':
                    if ( window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches ) {
                        html.setAttribute( 'data-bs-theme', 'dark' );
                        if ( darkModeCSS ) darkModeCSS.disabled = false;
                    } else {
                        html.setAttribute( 'data-bs-theme', 'light' );
                        if ( darkModeCSS ) darkModeCSS.disabled = true;
                    }
                    break;
            }

            this.updateThemeDropdown( theme );
            this.saveThemePreference( theme );
        },

        /**
         * Update theme dropdown to show current selection
         */
        updateThemeDropdown: function( theme ) {
            const currentThemeOption = document.querySelector( `.theme-option[data-theme="${theme}"]` );
            if ( currentThemeOption ) {
                const dropdownButton = document.querySelector( '.dropdown-toggle' );
                const icon = currentThemeOption.querySelector( 'i' ).className;
                const text = currentThemeOption.textContent.trim();
                if ( dropdownButton ) {
                    dropdownButton.innerHTML = `<i class="${icon}"></i> ${text}`;
                }
            }
        },

        /**
         * Save theme preference to server via AJAX
         */
        saveThemePreference: function(theme) {
            const ajaxObject = window.wcrbAjax || window.wcrb_ajax;
            
            if (ajaxObject && ajaxObject.ajax_url) {
                
                $.post(ajaxObject.ajax_url, {
                    action: 'wcrb_save_theme',
                    theme: theme,
                    nonce: ajaxObject.nonce
                }).done(function(response) {
                    if (response.success) {
                        // Success
                    } else {
                        // Error
                    }
                });
            }
        },

        /**
         * Load user preferences
         */
        loadUserPreferences: function() {
            const ajaxObject = window.wcrbAjax || window.wcrb_ajax;
            
            // First, try to get server preference if user is logged in
            if (ajaxObject && ajaxObject.ajax_url) {
                $.post(ajaxObject.ajax_url, {
                    action: 'wcrb_get_theme',
                    nonce: ajaxObject.nonce
                }).done((response) => {
                    if (response.success && response.data.theme) {
                        this.setTheme(response.data.theme);
                    } else {
                        // Fall back to localStorage
                        const savedTheme = localStorage.getItem('wcrb_theme') || 'light';
                        this.setTheme(savedTheme);
                    }
                });
            } else {
                // Fall back to localStorage
                const savedTheme = localStorage.getItem('wcrb_theme') || 'light';
                this.setTheme(savedTheme);
            }
        },

        /**
         * Bind WordPress hooks
         */
        bindWordPressHooks: function() {
            // Listen for system theme changes
            if ( window.matchMedia ) {
                window.matchMedia( '(prefers-color-scheme: dark)' ).addEventListener( 'change', e => {
                    const currentTheme = localStorage.getItem( 'wcrb_theme' );
                    if ( currentTheme === 'auto' ) {
                        this.setTheme( 'auto' );
                    }
                } );
            }
        },

        startBootstrapTooltips: function() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipTriggerList = [].slice.call( document.querySelectorAll( '[data-bs-toggle="tooltip"]' ) );
                tooltipTriggerList.map( function ( tooltipTriggerEl ) {
                    return new bootstrap.Tooltip( tooltipTriggerEl );
                } );
            }
        },

        select2initialize: function() {
            if ( $.fn.select2 ) {
                $( '#rep_devices, #timeLogJobDeviceSelect' ).select2();
            }
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        WordPressDashboard.init();
    });
    
    // Export for global access
    window.WordPressDashboard = WordPressDashboard;
    
})( jQuery );

jQuery(document).ready(function($) {
    // Handle sidebar accordion
    $('.wcrb-sidebar-nav .wcrb-nav-parent a[data-bs-toggle="collapse"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).data('bs-target');
        
        // Toggle chevron icon
        var chevron = $(this).find('.wcrb-chevron');
        if ($(target).hasClass('show')) {
            chevron.removeClass('bi-chevron-down').addClass('bi-chevron-right');
        } else {
            chevron.removeClass('bi-chevron-right').addClass('bi-chevron-down');
        }
        
        // If this is not a link to a page (just a toggle), prevent navigation
        if ($(this).attr('href').indexOf('#') === 0 || $(this).attr('href') === 'javascript:void(0)') {
            return false;
        }
    });
    
    // Update chevron icons on page load
    $('.wcrb-sidebar-nav .collapse').each(function() {
        var parentLink = $('a[data-bs-target="#' + $(this).attr('id') + '"]');
        var chevron = parentLink.find('.wcrb-chevron');
        if ($(this).hasClass('show')) {
            chevron.removeClass('bi-chevron-right').addClass('bi-chevron-down');
        } else {
            chevron.removeClass('bi-chevron-down').addClass('bi-chevron-right');
        }
    });
});