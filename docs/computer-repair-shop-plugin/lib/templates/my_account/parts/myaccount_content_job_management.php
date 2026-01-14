<?php
    defined( 'ABSPATH' ) || exit;

    // Get current user data
    $current_user = wp_get_current_user();
    
    $_user_role = $current_user->roles[0] ?? 'guest';
    if ( $_user_role != 'technician' && $_user_role != 'administrator' && $_user_role != 'store_manager' ) {
        echo esc_html__( "You do not have sufficient permissions to access this page.", "computer-repair-shop" );
        exit;
    }
?>

<!-- Job Detail Content -->
<main class="dashboard-content container-fluid py-4">
    <div class="row">
        <!-- Left Column - Job Details -->
        <div class="col-lg-8">
            <!-- Job Information Widget -->
            <div class="card job-widget">
                <div class="widget-header">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        Job Information
                        <button class="btn btn-sm btn-outline-primary float-end" data-bs-toggle="modal" data-bs-target="#editJobModal">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </h5>
                </div>
                <div class="widget-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Job Title</label>
                            <input type="text" class="form-control" value="MacBook Pro Screen Replacement" id="jobTitle">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Job Status</label>
                            <select class="form-select" id="jobStatus">
                                <option value="pending">Pending</option>
                                <option value="in-progress" selected>In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="on-hold">On Hold</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Priority</label>
                            <select class="form-select" id="jobPriority">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high" selected>High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Created Date</label>
                            <input type="date" class="form-control" value="2024-01-15" id="createdDate">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Due Date</label>
                            <input type="date" class="form-control" value="2024-01-22" id="dueDate">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Issue Description</label>
                            <textarea class="form-control" rows="3" id="issueDescription">Screen is cracked and unresponsive. Customer reported accidental drop.</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Technician Notes</label>
                            <textarea class="form-control" rows="3" id="technicianNotes" placeholder="Add technical observations and repair notes...">Diagnosed screen damage. LCD replacement required. Check for additional internal damage.</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information Widget -->
            <div class="card job-widget">
                <div class="widget-header">
                    <h5 class="mb-0">
                        <i class="bi bi-person me-2 text-primary"></i>
                        Customer Information
                        <button class="btn btn-sm btn-outline-primary float-end" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </h5>
                </div>
                <div class="widget-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer Name</label>
                            <input type="text" class="form-control" value="John Smith" id="customerName">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" value="john.smith@email.com" id="customerEmail">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="tel" class="form-control" value="+1 (555) 123-4567" id="customerPhone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Address</label>
                            <input type="text" class="form-control" value="123 Main St, City, State 12345" id="customerAddress">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Device Information Widget -->
            <div class="card job-widget">
                <div class="widget-header">
                    <h5 class="mb-0">
                        <i class="bi bi-laptop me-2 text-primary"></i>
                        Device Information
                        <button class="btn btn-sm btn-outline-primary float-end" data-bs-toggle="modal" data-bs-target="#editDeviceModal">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </h5>
                </div>
                <div class="widget-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Device Type</label>
                            <select class="form-select" id="deviceType">
                                <option value="laptop" selected>Laptop</option>
                                <option value="phone">Phone</option>
                                <option value="tablet">Tablet</option>
                                <option value="desktop">Desktop</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Brand</label>
                            <input type="text" class="form-control" value="Apple" id="deviceBrand">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Model</label>
                            <input type="text" class="form-control" value="MacBook Pro 14-inch" id="deviceModel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Serial Number</label>
                            <input type="text" class="form-control" value="C02XYZ123ABC" id="deviceSerial">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Color</label>
                            <input type="text" class="form-control" value="Space Gray" id="deviceColor">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Device Condition</label>
                            <textarea class="form-control" rows="2" id="deviceCondition">Good condition except for cracked screen. Minor scratches on casing.</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parts & Services Widget -->
            <div class="card job-widget">
                <div class="widget-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-gear me-2 text-primary"></i>
                        Parts & Services
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#addPartModal">
                            <i class="bi bi-plus-circle"></i> Add Part
                        </button>
                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                            <i class="bi bi-plus-circle"></i> Add Service
                        </button>
                    </div>
                </div>
                <div class="widget-body">
                    <!-- Parts List -->
                    <h6 class="fw-semibold mb-3">Parts Used</h6>
                    <div class="parts-list" id="partsList">
                        <div class="part-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">MacBook Pro 14" LCD Screen</h6>
                                    <small class="text-muted">Part #: APP-MBP14-LCD-001 | Qty: 1</small>
                                </div>
                                <div class="text-end">
                                    <strong>$249.00</strong>
                                    <div class="btn-group btn-group-sm ms-2">
                                        <button class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="part-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Screen Adhesive Kit</h6>
                                    <small class="text-muted">Part #: ADH-KIT-001 | Qty: 1</small>
                                </div>
                                <div class="text-end">
                                    <strong>$12.50</strong>
                                    <div class="btn-group btn-group-sm ms-2">
                                        <button class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Services List -->
                    <h6 class="fw-semibold mb-3 mt-4">Services</h6>
                    <div class="services-list" id="servicesList">
                        <div class="service-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Screen Replacement Service</h6>
                                    <small class="text-muted">Includes labor and testing</small>
                                </div>
                                <div class="text-end">
                                    <strong>$99.00</strong>
                                    <div class="btn-group btn-group-sm ms-2">
                                        <button class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="service-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Diagnostic Fee</h6>
                                    <small class="text-muted">Initial device assessment</small>
                                </div>
                                <div class="text-end">
                                    <strong>$49.00</strong>
                                    <div class="btn-group btn-group-sm ms-2">
                                        <button class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Log Widget -->
            <div class="card job-widget">
                <div class="widget-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2 text-primary"></i>
                        Job Log & Activity
                    </h5>
                </div>
                <div class="widget-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1">Job Completed</h6>
                                <small class="text-muted">Just now</small>
                            </div>
                            <p class="mb-1 text-muted">All repairs completed and device tested successfully</p>
                            <small>By: Technician Mike</small>
                        </div>
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1">Parts Installed</h6>
                                <small class="text-muted">2 hours ago</small>
                            </div>
                            <p class="mb-1 text-muted">New LCD screen and adhesive installed</p>
                            <small>By: Technician Mike</small>
                        </div>
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1">Diagnosis Completed</h6>
                                <small class="text-muted">4 hours ago</small>
                            </div>
                            <p class="mb-1 text-muted">Confirmed screen damage, no additional issues found</p>
                            <small>By: Technician Sarah</small>
                        </div>
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1">Job Created</h6>
                                <small class="text-muted">2024-01-15 09:30 AM</small>
                            </div>
                            <p class="mb-1 text-muted">New job created for MacBook Pro screen replacement</p>
                            <small>By: Front Desk</small>
                        </div>
                    </div>
                    
                    <!-- Add Log Entry -->
                    <div class="mt-4">
                        <label class="form-label fw-semibold">Add Log Entry</label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Enter activity description..." id="newLogEntry">
                            <button class="btn btn-primary" id="addLogEntry">
                                <i class="bi bi-plus-circle"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Files & Messages Widget -->
            <div class="card job-widget">
                <div class="widget-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-paperclip me-2 text-primary"></i>
                        Files & Messages
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary me-2" id="uploadFileBtn">
                            <i class="bi bi-upload"></i> Upload File
                        </button>
                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                            <i class="bi bi-send"></i> Send Message
                        </button>
                    </div>
                </div>
                <div class="widget-body">
                    <!-- File Attachments -->
                    <h6 class="fw-semibold mb-3">Attached Files</h6>
                    <div class="file-attachments" id="fileAttachments">
                        <div class="file-attachment">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-image text-primary me-3 fs-4"></i>
                                    <div>
                                        <div class="fw-semibold">device_photos.zip</div>
                                        <small class="text-muted">2.4 MB • Added 2 hours ago</small>
                                    </div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary"><i class="bi bi-download"></i></button>
                                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="file-attachment">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-pdf text-danger me-3 fs-4"></i>
                                    <div>
                                        <div class="fw-semibold">diagnostic_report.pdf</div>
                                        <small class="text-muted">1.1 MB • Added 4 hours ago</small>
                                    </div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary"><i class="bi bi-download"></i></button>
                                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <h6 class="fw-semibold mb-3 mt-4">Messages</h6>
                    <div class="messages" id="messages">
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between">
                                <strong>To Customer:</strong>
                                <small>2 hours ago</small>
                            </div>
                            <p class="mb-0 mt-2">Your MacBook Pro repair is complete and ready for pickup. Total cost is $409.50.</p>
                        </div>
                        <div class="alert alert-light">
                            <div class="d-flex justify-content-between">
                                <strong>From Customer:</strong>
                                <small>1 hour ago</small>
                            </div>
                            <p class="mb-0 mt-2">Great! I'll come by tomorrow morning to pick it up. Thanks!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Summary & Actions -->
        <div class="col-lg-4">
            <!-- Job Totals Widget -->
            <div class="card job-totals">
                <div class="card-body text-center">
                    <h4 class="text-white mb-3">Job Totals</h4>
                    <div class="row text-white mb-3">
                        <div class="col-6">
                            <div class="fw-light">Parts</div>
                            <div class="h5">$261.50</div>
                        </div>
                        <div class="col-6">
                            <div class="fw-light">Services</div>
                            <div class="h5">$148.00</div>
                        </div>
                    </div>
                    <div class="row text-white mb-3">
                        <div class="col-6">
                            <div class="fw-light">Tax</div>
                            <div class="h5">$32.60</div>
                        </div>
                        <div class="col-6">
                            <div class="fw-light">Discount</div>
                            <div class="h5">-$32.60</div>
                        </div>
                    </div>
                    <hr class="my-3 bg-white">
                    <div class="row text-white">
                        <div class="col-12">
                            <div class="fw-semibold">Total</div>
                            <div class="h2">$409.50</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="card job-widget">
                <div class="widget-header">
                    <h5 class="mb-0">
                        <i class="bi bi-credit-card me-2 text-primary"></i>
                        Payment Information
                    </h5>
                </div>
                <div class="widget-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Status</label>
                        <select class="form-select" id="paymentStatus">
                            <option value="pending">Pending</option>
                            <option value="partial">Partial Payment</option>
                            <option value="paid" selected>Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount Paid</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" value="409.50" id="amountPaid">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method</label>
                        <select class="form-select" id="paymentMethod">
                            <option value="cash">Cash</option>
                            <option value="card" selected>Credit Card</option>
                            <option value="transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                    <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                        <i class="bi bi-credit-card me-2"></i>Record Payment
                    </button>
                </div>
            </div>

            <!-- Technician Assignment -->
            <div class="card job-widget">
                <div class="widget-header">
                    <h5 class="mb-0">
                        <i class="bi bi-person-gear me-2 text-primary"></i>
                        Technician Assignment
                    </h5>
                </div>
                <div class="widget-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assigned Technician</label>
                        <select class="form-select" id="assignedTechnician">
                            <option value="1">Mike Johnson (Senior)</option>
                            <option value="2" selected>Sarah Williams</option>
                            <option value="3">David Brown</option>
                            <option value="4">Lisa Garcia</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Work Hours</label>
                        <div class="input-group">
                            <input type="number" class="form-control" value="2.5" id="workHours">
                            <span class="input-group-text">hours</span>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="qualityCheck" checked>
                        <label class="form-check-label" for="qualityCheck">Quality Check Completed</label>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card job-widget">
                <div class="widget-header">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning me-2 text-primary"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="widget-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#emailCustomerModal">
                            <i class="bi bi-envelope me-2"></i>Email Customer
                        </button>
                        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#smsCustomerModal">
                            <i class="bi bi-chat-dots me-2"></i>SMS Customer
                        </button>
                        <button class="btn btn-outline-warning" id="createInvoiceBtn">
                            <i class="bi bi-receipt me-2"></i>Create Invoice
                        </button>
                        <button class="btn btn-outline-info" id="duplicateJobBtn">
                            <i class="bi bi-files me-2"></i>Duplicate Job
                        </button>
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelJobModal">
                            <i class="bi bi-x-circle me-2"></i>Cancel Job
                        </button>
                    </div>
                </div>
            </div>

            <!-- Job Extras -->
            <div class="card job-widget">
                <div class="widget-header">
                    <h5 class="mb-0">
                        <i class="bi bi-plus-circle me-2 text-primary"></i>
                        Job Extras
                    </h5>
                </div>
                <div class="widget-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Warranty Period</label>
                        <select class="form-select" id="warrantyPeriod">
                            <option value="30">30 Days</option>
                            <option value="90" selected>90 Days</option>
                            <option value="180">180 Days</option>
                            <option value="365">1 Year</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pickup Location</label>
                        <input type="text" class="form-control" value="Front Counter" id="pickupLocation">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="dataBackup" checked>
                        <label class="form-check-label" for="dataBackup">Data Backup Service</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="cleaningService">
                        <label class="form-check-label" for="cleaningService">Device Cleaning</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="protectiveCase">
                        <label class="form-check-label" for="protectiveCase">Protective Case</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>