{{--
  ┌──────────────────────────────────────────────────────────────────┐
  │  DATATABLE MOCKUPS — Design Showcase                             │
  │                                                                  │
  │  Demonstrates 3 DataTable variants:                              │
  │   1. Jobs Table     — Full-featured (bulk, badges, dropdowns)   │
  │   2. Invoices Table — With stats hero, status filters, export   │
  │   3. Inventory Table — Dense, filterable columns, inline edits  │
  │                                                                  │
  │  All using <x-ui.datatable> component + Bootstrap 5 design      │
  └──────────────────────────────────────────────────────────────────┘
--}}
@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'DataTable Designs'])

@php
    // ── Action dropdown helper ──
    $actionMenu = function (array $items = ['view', 'edit', 'delete']) {
        $li = '';
        if (in_array('view', $items))   $li .= '<li><a class="dropdown-item" href="#"><i class="bi bi-eye me-2"></i>View</a></li>';
        if (in_array('edit', $items))   $li .= '<li><a class="dropdown-item" href="#"><i class="bi bi-pencil me-2"></i>Edit</a></li>';
        if (in_array('delete', $items)) $li .= '<li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash me-2"></i>Delete</a></li>';
        return '<div class="dropdown"><button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end">' . $li . '</ul></div>';
    };
    $viewBtn = '<a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>';
    $editBtn = '<a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>';

    // ═══════ TABLE 1 DATA: JOBS ═══════
    $jobColumns = [
        ['key' => 'id',         'label' => 'Job #',      'width' => '80px',  'sortable' => true,  'filter' => true],
        ['key' => 'customer',   'label' => 'Customer',   'width' => '180px', 'sortable' => true,  'filter' => true],
        ['key' => 'device',     'label' => 'Device',     'width' => '160px', 'sortable' => true,  'filter' => true],
        ['key' => 'technician', 'label' => 'Technician', 'width' => '140px', 'sortable' => true,  'filter' => true],
        ['key' => 'status',     'label' => 'Status',     'width' => '120px', 'sortable' => true,  'badge' => true, 'filter' => true],
        ['key' => 'priority',   'label' => 'Priority',   'width' => '100px', 'sortable' => true,  'badge' => true],
        ['key' => 'due_date',   'label' => 'Due Date',   'width' => '110px', 'sortable' => true,  'nowrap' => true],
        ['key' => 'total',      'label' => 'Total',      'width' => '100px', 'sortable' => true,  'align' => 'text-end'],
        ['key' => 'actions',    'label' => 'Actions',    'width' => '100px', 'sortable' => false, 'align' => 'text-end', 'html' => true],
    ];

    $jobRows = [
        ['id' => 'JOB-1041', 'customer' => 'Sarah Mitchell',  'device' => 'iPhone 15 Pro',      'technician' => 'Mike Torres',  'status' => 'In Progress',  '_badgeClass_status' => 'wcrb-pill--progress', 'priority' => 'High',   '_badgeClass_priority' => 'wcrb-pill--high',   'due_date' => '2026-02-25', 'total' => '$185.00', 'actions' => $actionMenu(['view','edit','delete'])],
        ['id' => 'JOB-1040', 'customer' => 'James Wilson',    'device' => 'MacBook Air M2',     'technician' => 'Lisa Chen',    'status' => 'Pending',      '_badgeClass_status' => 'wcrb-pill--pending',  'priority' => 'Medium', '_badgeClass_priority' => 'wcrb-pill--medium', 'due_date' => '2026-02-26', 'total' => '$320.00', 'actions' => $actionMenu(['view','edit'])],
        ['id' => 'JOB-1039', 'customer' => 'Emily Johnson',   'device' => 'Samsung Galaxy S24', 'technician' => 'Mike Torres',  'status' => 'Completed',    '_badgeClass_status' => 'wcrb-pill--active',   'priority' => 'Low',    '_badgeClass_priority' => 'wcrb-pill--low',    'due_date' => '2026-02-22', 'total' => '$95.00',  'actions' => $actionMenu(['view','edit'])],
        ['id' => 'JOB-1038', 'customer' => 'David Lee',       'device' => 'iPad Pro 12.9',      'technician' => 'Alex Rivera',  'status' => 'Waiting Parts','_badgeClass_status' => 'wcrb-pill--warning',  'priority' => 'High',   '_badgeClass_priority' => 'wcrb-pill--high',   'due_date' => '2026-02-28', 'total' => '$275.00', 'actions' => $actionMenu(['view','edit'])],
        ['id' => 'JOB-1037', 'customer' => 'Rachel Green',    'device' => 'Dell XPS 15',        'technician' => 'Lisa Chen',    'status' => 'In Progress',  '_badgeClass_status' => 'wcrb-pill--progress', 'priority' => 'Medium', '_badgeClass_priority' => 'wcrb-pill--medium', 'due_date' => '2026-02-24', 'total' => '$450.00', 'actions' => $actionMenu(['view','edit'])],
        ['id' => 'JOB-1036', 'customer' => 'Tom Anderson',    'device' => 'Nintendo Switch',    'technician' => 'Mike Torres',  'status' => 'Completed',    '_badgeClass_status' => 'wcrb-pill--active',   'priority' => 'Low',    '_badgeClass_priority' => 'wcrb-pill--low',    'due_date' => '2026-02-20', 'total' => '$65.00',  'actions' => $actionMenu(['view','edit'])],
        ['id' => 'JOB-1035', 'customer' => 'Nina Patel',      'device' => 'Pixel 8 Pro',        'technician' => 'Alex Rivera',  'status' => 'Pending',      '_badgeClass_status' => 'wcrb-pill--pending',  'priority' => 'High',   '_badgeClass_priority' => 'wcrb-pill--high',   'due_date' => '2026-02-27', 'total' => '$140.00', 'actions' => $actionMenu(['view','edit'])],
        ['id' => 'JOB-1034', 'customer' => 'Chris Brown',     'device' => 'PS5 Controller',     'technician' => 'Lisa Chen',    'status' => 'Completed',    '_badgeClass_status' => 'wcrb-pill--active',   'priority' => 'Low',    '_badgeClass_priority' => 'wcrb-pill--low',    'due_date' => '2026-02-18', 'total' => '$45.00',  'actions' => $actionMenu(['view','edit'])],
        ['id' => 'JOB-1033', 'customer' => 'Amy Zhang',       'device' => 'iMac 24',            'technician' => 'Mike Torres',  'status' => 'In Progress',  '_badgeClass_status' => 'wcrb-pill--progress', 'priority' => 'High',   '_badgeClass_priority' => 'wcrb-pill--high',   'due_date' => '2026-03-01', 'total' => '$550.00', 'actions' => $actionMenu(['view','edit'])],
        ['id' => 'JOB-1032', 'customer' => 'Ben Costa',       'device' => 'Surface Pro 9',      'technician' => 'Alex Rivera',  'status' => 'Pending',      '_badgeClass_status' => 'wcrb-pill--pending',  'priority' => 'Medium', '_badgeClass_priority' => 'wcrb-pill--medium', 'due_date' => '2026-02-26', 'total' => '$210.00', 'actions' => $actionMenu(['view','edit'])],
        ['id' => 'JOB-1031', 'customer' => 'Diana Ross',      'device' => 'Apple Watch Ultra',  'technician' => 'Lisa Chen',    'status' => 'Waiting Parts','_badgeClass_status' => 'wcrb-pill--warning',  'priority' => 'Low',    '_badgeClass_priority' => 'wcrb-pill--low',    'due_date' => '2026-03-02', 'total' => '$120.00', 'actions' => $actionMenu(['view','edit'])],
        ['id' => 'JOB-1030', 'customer' => 'Frank Miller',    'device' => 'ThinkPad X1 Carbon', 'technician' => 'Mike Torres',  'status' => 'Completed',    '_badgeClass_status' => 'wcrb-pill--active',   'priority' => 'Medium', '_badgeClass_priority' => 'wcrb-pill--medium', 'due_date' => '2026-02-15', 'total' => '$380.00', 'actions' => $actionMenu(['view','edit'])],
    ];

    // ═══════ TABLE 2 DATA: INVOICES ═══════
    $invoiceColumns = [
        ['key' => 'invoice_no', 'label' => 'Invoice #',  'width' => '110px', 'sortable' => true],
        ['key' => 'customer',   'label' => 'Customer',   'width' => '180px', 'sortable' => true],
        ['key' => 'job_ref',    'label' => 'Job Ref',    'width' => '100px', 'sortable' => true],
        ['key' => 'issue_date', 'label' => 'Issue Date', 'width' => '110px', 'sortable' => true, 'nowrap' => true],
        ['key' => 'due_date',   'label' => 'Due Date',   'width' => '110px', 'sortable' => true, 'nowrap' => true],
        ['key' => 'amount',     'label' => 'Amount',     'width' => '100px', 'sortable' => true, 'align' => 'text-end'],
        ['key' => 'paid',       'label' => 'Paid',       'width' => '100px', 'sortable' => true, 'align' => 'text-end'],
        ['key' => 'balance',    'label' => 'Balance',    'width' => '100px', 'sortable' => true, 'align' => 'text-end'],
        ['key' => 'status',     'label' => 'Status',     'width' => '110px', 'sortable' => true, 'badge' => true],
        ['key' => 'actions',    'label' => '',            'width' => '60px',  'sortable' => false, 'align' => 'text-end', 'html' => true],
    ];

    $invoiceRows = [
        ['invoice_no' => 'INV-2026-052', 'customer' => 'Sarah Mitchell', 'job_ref' => 'JOB-1041', 'issue_date' => '2026-02-20', 'due_date' => '2026-03-06', 'amount' => '$185.00', 'paid' => '$185.00', 'balance' => '$0.00',   'status' => 'Paid',    '_badgeClass_status' => 'wcrb-pill--active',  'actions' => $viewBtn],
        ['invoice_no' => 'INV-2026-051', 'customer' => 'James Wilson',   'job_ref' => 'JOB-1040', 'issue_date' => '2026-02-19', 'due_date' => '2026-03-05', 'amount' => '$320.00', 'paid' => '$0.00',   'balance' => '$320.00', 'status' => 'Unpaid',  '_badgeClass_status' => 'wcrb-pill--pending', 'actions' => $viewBtn],
        ['invoice_no' => 'INV-2026-050', 'customer' => 'Emily Johnson',  'job_ref' => 'JOB-1039', 'issue_date' => '2026-02-18', 'due_date' => '2026-03-04', 'amount' => '$95.00',  'paid' => '$95.00',  'balance' => '$0.00',   'status' => 'Paid',    '_badgeClass_status' => 'wcrb-pill--active',  'actions' => $viewBtn],
        ['invoice_no' => 'INV-2026-049', 'customer' => 'David Lee',      'job_ref' => 'JOB-1038', 'issue_date' => '2026-02-15', 'due_date' => '2026-03-01', 'amount' => '$275.00', 'paid' => '$100.00', 'balance' => '$175.00', 'status' => 'Partial', '_badgeClass_status' => 'wcrb-pill--warning', 'actions' => $viewBtn],
        ['invoice_no' => 'INV-2026-048', 'customer' => 'Rachel Green',   'job_ref' => 'JOB-1037', 'issue_date' => '2026-02-14', 'due_date' => '2026-02-28', 'amount' => '$450.00', 'paid' => '$450.00', 'balance' => '$0.00',   'status' => 'Paid',    '_badgeClass_status' => 'wcrb-pill--active',  'actions' => $viewBtn],
        ['invoice_no' => 'INV-2026-047', 'customer' => 'Tom Anderson',   'job_ref' => 'JOB-1036', 'issue_date' => '2026-02-10', 'due_date' => '2026-02-24', 'amount' => '$65.00',  'paid' => '$65.00',  'balance' => '$0.00',   'status' => 'Paid',    '_badgeClass_status' => 'wcrb-pill--active',  'actions' => $viewBtn],
        ['invoice_no' => 'INV-2026-046', 'customer' => 'Nina Patel',     'job_ref' => 'JOB-1035', 'issue_date' => '2026-02-08', 'due_date' => '2026-02-22', 'amount' => '$140.00', 'paid' => '$0.00',   'balance' => '$140.00', 'status' => 'Overdue', '_badgeClass_status' => 'wcrb-pill--danger',  'actions' => $viewBtn],
        ['invoice_no' => 'INV-2026-045', 'customer' => 'Chris Brown',    'job_ref' => 'JOB-1034', 'issue_date' => '2026-02-05', 'due_date' => '2026-02-19', 'amount' => '$45.00',  'paid' => '$45.00',  'balance' => '$0.00',   'status' => 'Paid',    '_badgeClass_status' => 'wcrb-pill--active',  'actions' => $viewBtn],
        ['invoice_no' => 'INV-2026-044', 'customer' => 'Amy Zhang',      'job_ref' => 'JOB-1033', 'issue_date' => '2026-02-03', 'due_date' => '2026-02-17', 'amount' => '$550.00', 'paid' => '$200.00', 'balance' => '$350.00', 'status' => 'Overdue', '_badgeClass_status' => 'wcrb-pill--danger',  'actions' => $viewBtn],
        ['invoice_no' => 'INV-2026-043', 'customer' => 'Ben Costa',      'job_ref' => 'JOB-1032', 'issue_date' => '2026-02-01', 'due_date' => '2026-02-15', 'amount' => '$210.00', 'paid' => '$210.00', 'balance' => '$0.00',   'status' => 'Paid',    '_badgeClass_status' => 'wcrb-pill--active',  'actions' => $viewBtn],
        ['invoice_no' => 'INV-2026-042', 'customer' => 'Diana Ross',     'job_ref' => 'JOB-1031', 'issue_date' => '2026-01-28', 'due_date' => '2026-02-11', 'amount' => '$120.00', 'paid' => '$120.00', 'balance' => '$0.00',   'status' => 'Paid',    '_badgeClass_status' => 'wcrb-pill--active',  'actions' => $viewBtn],
        ['invoice_no' => 'INV-2026-041', 'customer' => 'Frank Miller',   'job_ref' => 'JOB-1030', 'issue_date' => '2026-01-25', 'due_date' => '2026-02-08', 'amount' => '$380.00', 'paid' => '$380.00', 'balance' => '$0.00',   'status' => 'Paid',    '_badgeClass_status' => 'wcrb-pill--active',  'actions' => $viewBtn],
    ];

    // ═══════ TABLE 3 DATA: INVENTORY ═══════
    $inventoryColumns = [
        ['key' => 'sku',          'label' => 'SKU',        'width' => '100px', 'sortable' => true,  'filter' => true],
        ['key' => 'name',         'label' => 'Part Name',  'width' => '220px', 'sortable' => true,  'filter' => true],
        ['key' => 'category',     'label' => 'Category',   'width' => '140px', 'sortable' => true,  'filter' => true, 'badge' => true],
        ['key' => 'brand',        'label' => 'Brand',      'width' => '120px', 'sortable' => true,  'filter' => true],
        ['key' => 'location',     'label' => 'Location',   'width' => '110px', 'sortable' => true],
        ['key' => 'qty',          'label' => 'Qty',        'width' => '70px',  'sortable' => true,  'align' => 'text-center'],
        ['key' => 'min_qty',      'label' => 'Min',        'width' => '60px',  'sortable' => true,  'align' => 'text-center'],
        ['key' => 'cost',         'label' => 'Cost',       'width' => '90px',  'sortable' => true,  'align' => 'text-end'],
        ['key' => 'price',        'label' => 'Sell Price', 'width' => '90px',  'sortable' => true,  'align' => 'text-end'],
        ['key' => 'stock_status', 'label' => 'Stock',      'width' => '100px', 'sortable' => true,  'badge' => true],
        ['key' => 'actions',      'label' => '',            'width' => '80px',  'sortable' => false, 'align' => 'text-end', 'html' => true],
    ];

    $inventoryRows = [
        ['sku' => 'PRT-001', 'name' => 'iPhone 15 Pro Screen OLED',       'category' => 'Screens',      '_badgeClass_category' => 'wcrb-pill--cat-screen',    'brand' => 'Apple',     'location' => 'Shelf A1', 'qty' => 12, 'min_qty' => 5,  'cost' => '$85.00',  'price' => '$149.00', 'stock_status' => 'In Stock',      '_badgeClass_stock_status' => 'wcrb-pill--active',  'actions' => $editBtn],
        ['sku' => 'PRT-002', 'name' => 'Samsung Galaxy S24 Battery',      'category' => 'Batteries',    '_badgeClass_category' => 'wcrb-pill--cat-battery',   'brand' => 'Samsung',   'location' => 'Shelf B2', 'qty' => 8,  'min_qty' => 3,  'cost' => '$22.00',  'price' => '$45.00',  'stock_status' => 'In Stock',      '_badgeClass_stock_status' => 'wcrb-pill--active',  'actions' => $editBtn],
        ['sku' => 'PRT-003', 'name' => 'MacBook Air M2 Keyboard Assembly','category' => 'Keyboards',    '_badgeClass_category' => 'wcrb-pill--cat-keyboard',  'brand' => 'Apple',     'location' => 'Shelf C1', 'qty' => 2,  'min_qty' => 3,  'cost' => '$120.00', 'price' => '$199.00', 'stock_status' => 'Low Stock',     '_badgeClass_stock_status' => 'wcrb-pill--warning', 'actions' => $editBtn],
        ['sku' => 'PRT-004', 'name' => 'iPad Pro 12.9 Digitizer Glass',   'category' => 'Screens',      '_badgeClass_category' => 'wcrb-pill--cat-screen',    'brand' => 'Apple',     'location' => 'Shelf A2', 'qty' => 5,  'min_qty' => 2,  'cost' => '$95.00',  'price' => '$165.00', 'stock_status' => 'In Stock',      '_badgeClass_stock_status' => 'wcrb-pill--active',  'actions' => $editBtn],
        ['sku' => 'PRT-005', 'name' => 'Pixel 8 Pro Charging Port',       'category' => 'Connectors',   '_badgeClass_category' => 'wcrb-pill--cat-connector', 'brand' => 'Google',    'location' => 'Shelf D3', 'qty' => 0,  'min_qty' => 5,  'cost' => '$8.00',   'price' => '$25.00',  'stock_status' => 'Out of Stock',  '_badgeClass_stock_status' => 'wcrb-pill--danger',  'actions' => $editBtn],
        ['sku' => 'PRT-006', 'name' => 'Dell XPS 15 Motherboard',         'category' => 'Logic Boards', '_badgeClass_category' => 'wcrb-pill--cat-board',     'brand' => 'Dell',      'location' => 'Shelf E1', 'qty' => 3,  'min_qty' => 2,  'cost' => '$280.00', 'price' => '$420.00', 'stock_status' => 'In Stock',      '_badgeClass_stock_status' => 'wcrb-pill--active',  'actions' => $editBtn],
        ['sku' => 'PRT-007', 'name' => 'Nintendo Switch Joy-Con Rail',    'category' => 'Connectors',   '_badgeClass_category' => 'wcrb-pill--cat-connector', 'brand' => 'Nintendo',  'location' => 'Shelf D1', 'qty' => 15, 'min_qty' => 5,  'cost' => '$5.00',   'price' => '$18.00',  'stock_status' => 'In Stock',      '_badgeClass_stock_status' => 'wcrb-pill--active',  'actions' => $editBtn],
        ['sku' => 'PRT-008', 'name' => 'Surface Pro 9 Display Panel',     'category' => 'Screens',      '_badgeClass_category' => 'wcrb-pill--cat-screen',    'brand' => 'Microsoft', 'location' => 'Shelf A3', 'qty' => 1,  'min_qty' => 2,  'cost' => '$195.00', 'price' => '$310.00', 'stock_status' => 'Low Stock',     '_badgeClass_stock_status' => 'wcrb-pill--warning', 'actions' => $editBtn],
        ['sku' => 'PRT-009', 'name' => 'iPhone 14 Rear Camera Module',    'category' => 'Cameras',      '_badgeClass_category' => 'wcrb-pill--cat-camera',    'brand' => 'Apple',     'location' => 'Shelf F2', 'qty' => 6,  'min_qty' => 3,  'cost' => '$65.00',  'price' => '$110.00', 'stock_status' => 'In Stock',      '_badgeClass_stock_status' => 'wcrb-pill--active',  'actions' => $editBtn],
        ['sku' => 'PRT-010', 'name' => 'PS5 Controller Thumbstick Module','category' => 'Misc',         '_badgeClass_category' => 'wcrb-pill--inactive',      'brand' => 'Sony',      'location' => 'Shelf G1', 'qty' => 25, 'min_qty' => 10, 'cost' => '$3.00',   'price' => '$12.00',  'stock_status' => 'In Stock',      '_badgeClass_stock_status' => 'wcrb-pill--active',  'actions' => $editBtn],
        ['sku' => 'PRT-011', 'name' => 'ThinkPad X1 Fan Assembly',        'category' => 'Cooling',      '_badgeClass_category' => 'wcrb-pill--cat-cooling',   'brand' => 'Lenovo',    'location' => 'Shelf H1', 'qty' => 4,  'min_qty' => 3,  'cost' => '$35.00',  'price' => '$65.00',  'stock_status' => 'In Stock',      '_badgeClass_stock_status' => 'wcrb-pill--active',  'actions' => $editBtn],
        ['sku' => 'PRT-012', 'name' => 'Apple Watch Ultra Screen + Touch','category' => 'Screens',      '_badgeClass_category' => 'wcrb-pill--cat-screen',    'brand' => 'Apple',     'location' => 'Shelf A4', 'qty' => 0,  'min_qty' => 2,  'cost' => '$145.00', 'price' => '$245.00', 'stock_status' => 'Out of Stock',  '_badgeClass_stock_status' => 'wcrb-pill--danger',  'actions' => $editBtn],
        ['sku' => 'PRT-013', 'name' => 'Samsung Galaxy S23 Back Glass',   'category' => 'Housings',     '_badgeClass_category' => 'wcrb-pill--cat-housing',   'brand' => 'Samsung',   'location' => 'Shelf I2', 'qty' => 9,  'min_qty' => 5,  'cost' => '$18.00',  'price' => '$38.00',  'stock_status' => 'In Stock',      '_badgeClass_stock_status' => 'wcrb-pill--active',  'actions' => $editBtn],
        ['sku' => 'PRT-014', 'name' => 'MacBook Pro 14 SSD 512GB',        'category' => 'Storage',      '_badgeClass_category' => 'wcrb-pill--cat-storage',   'brand' => 'Apple',     'location' => 'Shelf J1', 'qty' => 3,  'min_qty' => 2,  'cost' => '$180.00', 'price' => '$290.00', 'stock_status' => 'In Stock',      '_badgeClass_stock_status' => 'wcrb-pill--active',  'actions' => $editBtn],
        ['sku' => 'PRT-015', 'name' => 'iMac 24 Power Supply Unit',       'category' => 'Power',        '_badgeClass_category' => 'wcrb-pill--cat-power',     'brand' => 'Apple',     'location' => 'Shelf K1', 'qty' => 1,  'min_qty' => 2,  'cost' => '$210.00', 'price' => '$340.00', 'stock_status' => 'Low Stock',     '_badgeClass_stock_status' => 'wcrb-pill--warning', 'actions' => $editBtn],
    ];
@endphp

@section('content')
<div class="container-fluid p-3">

    {{-- ════════════════════════════════════════════════════════════════
         Page Header
         ════════════════════════════════════════════════════════════════ --}}
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-table me-2"></i>{{ __('DataTable Design Mockups') }}</h4>
        <p class="text-muted mb-0" style="font-size: 0.85rem;">
            {{ __('Three production-ready table designs with pagination, filtering, sorting, and bulk actions.') }}
        </p>
    </div>

    {{-- ════════════════════════════════════════════════════════════════
         DESIGN 1: Jobs Table — Full Featured
         ════════════════════════════════════════════════════════════════ --}}
    <div class="mb-4">
        <x-ui.datatable
            tableId="jobsTableMockup"
            :title="__('Repair Jobs')"
            :columns="$jobColumns"
            :rows="$jobRows"
            :searchable="true"
            :paginate="true"
            :perPage="5"
            :perPageOptions="[5, 10, 25]"
            :exportable="true"
            :filterable="true"
            createRoute="#"
            :createLabel="__('New Job')"
        >
            <x-slot:actions>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary active" data-bs-toggle="button"><i class="bi bi-list-ul"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="button"><i class="bi bi-grid-3x3"></i></button>
                </div>
            </x-slot:actions>

            <x-slot:filters>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Status') }}</label>
                        <select class="form-select form-select-sm">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option>Pending</option>
                            <option>In Progress</option>
                            <option>Waiting Parts</option>
                            <option>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Technician') }}</label>
                        <select class="form-select form-select-sm">
                            <option value="">{{ __('All Technicians') }}</option>
                            <option>Mike Torres</option>
                            <option>Lisa Chen</option>
                            <option>Alex Rivera</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Priority') }}</label>
                        <select class="form-select form-select-sm">
                            <option value="">{{ __('All Priorities') }}</option>
                            <option>High</option>
                            <option>Medium</option>
                            <option>Low</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Date Range') }}</label>
                        <input type="date" class="form-control form-control-sm">
                    </div>
                </div>
            </x-slot:filters>

            <x-slot:bulkActions>
                <button class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem;"><i class="bi bi-printer me-1"></i>{{ __('Print') }}</button>
                <button class="btn btn-sm btn-outline-success" style="font-size: 0.75rem;"><i class="bi bi-check2-all me-1"></i>{{ __('Mark Complete') }}</button>
                <button class="btn btn-sm btn-outline-danger" style="font-size: 0.75rem;"><i class="bi bi-trash me-1"></i>{{ __('Delete') }}</button>
            </x-slot:bulkActions>
        </x-ui.datatable>
    </div>


    {{-- ════════════════════════════════════════════════════════════════
         DESIGN 2: Invoices Table — Stats Hero + Status Tabs
         ════════════════════════════════════════════════════════════════ --}}
    <div class="mb-4">

        {{-- Stats Row --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1">{{ __('TOTAL INVOICED') }}</div>
                                <h4 class="mb-0">$12,480</h4>
                            </div>
                            <div class="stats-icon"><i class="bi bi-receipt"></i></div>
                        </div>
                        <div style="font-size: 0.72rem; opacity: 0.85;" class="mt-1">
                            <i class="bi bi-arrow-up-short"></i> 12% {{ __('from last month') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1">{{ __('PAID') }}</div>
                                <h4 class="mb-0">$9,340</h4>
                            </div>
                            <div class="stats-icon"><i class="bi bi-check-circle"></i></div>
                        </div>
                        <div style="font-size: 0.72rem; opacity: 0.85;" class="mt-1">
                            42 {{ __('invoices') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stats-card bg-warning text-dark">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="color: rgba(0,0,0,0.7);">{{ __('OUTSTANDING') }}</div>
                                <h4 class="mb-0" style="color: #111;">$2,640</h4>
                            </div>
                            <div class="stats-icon" style="color: rgba(0,0,0,0.5);"><i class="bi bi-clock-history"></i></div>
                        </div>
                        <div style="font-size: 0.72rem; color: rgba(0,0,0,0.55);" class="mt-1">
                            8 {{ __('invoices') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stats-card bg-danger text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1">{{ __('OVERDUE') }}</div>
                                <h4 class="mb-0">$500</h4>
                            </div>
                            <div class="stats-icon"><i class="bi bi-exclamation-triangle"></i></div>
                        </div>
                        <div style="font-size: 0.72rem; opacity: 0.85;" class="mt-1">
                            2 {{ __('invoices') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-ui.datatable
            tableId="invoicesTableMockup"
            :title="__('Invoices')"
            :columns="$invoiceColumns"
            :rows="$invoiceRows"
            :searchable="true"
            :paginate="true"
            :perPage="5"
            :perPageOptions="[5, 10, 25, 50]"
            :exportable="true"
            :filterable="false"
            createRoute="#"
            :createLabel="__('New Invoice')"
        >
            <x-slot:actions>
                {{-- Status tabs as pill buttons --}}
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary active">{{ __('All') }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1">52</span></button>
                    <button type="button" class="btn btn-outline-secondary">{{ __('Paid') }} <span class="badge bg-success bg-opacity-25 text-success ms-1">42</span></button>
                    <button type="button" class="btn btn-outline-secondary">{{ __('Unpaid') }} <span class="badge bg-warning bg-opacity-25 text-warning ms-1">8</span></button>
                    <button type="button" class="btn btn-outline-secondary">{{ __('Overdue') }} <span class="badge bg-danger bg-opacity-25 text-danger ms-1">2</span></button>
                </div>
            </x-slot:actions>
        </x-ui.datatable>
    </div>


    {{-- ════════════════════════════════════════════════════════════════
         DESIGN 3: Inventory / Parts Table — Dense, Column Filters
         ════════════════════════════════════════════════════════════════ --}}
    <div class="mb-4">
        <x-ui.datatable
            tableId="inventoryTableMockup"
            :title="__('Parts Inventory')"
            :columns="$inventoryColumns"
            :rows="$inventoryRows"
            :searchable="true"
            :paginate="true"
            :perPage="10"
            :perPageOptions="[10, 25, 50, 100]"
            :exportable="true"
            :filterable="true"
            createRoute="#"
            :createLabel="__('Add Part')"
        >
            <x-slot:actions>
                {{-- Quick stock summary --}}
                <div class="d-flex align-items-center gap-3" style="font-size: 0.78rem;">
                    <span class="text-muted">
                        <i class="bi bi-box-seam me-1"></i>{{ __('Total SKUs:') }} <strong>15</strong>
                    </span>
                    <span class="text-warning">
                        <i class="bi bi-exclamation-triangle me-1"></i>{{ __('Low:') }} <strong>3</strong>
                    </span>
                    <span class="text-danger">
                        <i class="bi bi-x-circle me-1"></i>{{ __('Out:') }} <strong>2</strong>
                    </span>
                </div>
            </x-slot:actions>

            <x-slot:filters>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Category') }}</label>
                        <select class="form-select form-select-sm">
                            <option value="">{{ __('All Categories') }}</option>
                            <option>Screens</option>
                            <option>Batteries</option>
                            <option>Keyboards</option>
                            <option>Connectors</option>
                            <option>Logic Boards</option>
                            <option>Cameras</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Brand') }}</label>
                        <select class="form-select form-select-sm">
                            <option value="">{{ __('All Brands') }}</option>
                            <option>Apple</option>
                            <option>Samsung</option>
                            <option>Google</option>
                            <option>Dell</option>
                            <option>Lenovo</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Stock Status') }}</label>
                        <select class="form-select form-select-sm">
                            <option value="">{{ __('All') }}</option>
                            <option>In Stock</option>
                            <option>Low Stock</option>
                            <option>Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Price Range') }}</label>
                        <div class="d-flex gap-2">
                            <input type="number" class="form-control form-control-sm" placeholder="{{ __('Min') }}" style="font-size: 0.78rem;">
                            <input type="number" class="form-control form-control-sm" placeholder="{{ __('Max') }}" style="font-size: 0.78rem;">
                        </div>
                    </div>
                </div>
            </x-slot:filters>

            <x-slot:bulkActions>
                <button class="btn btn-sm btn-outline-warning" style="font-size: 0.75rem;"><i class="bi bi-arrow-repeat me-1"></i>{{ __('Reorder') }}</button>
                <button class="btn btn-sm btn-outline-secondary" style="font-size: 0.75rem;"><i class="bi bi-tag me-1"></i>{{ __('Update Prices') }}</button>
                <button class="btn btn-sm btn-outline-danger" style="font-size: 0.75rem;"><i class="bi bi-trash me-1"></i>{{ __('Delete') }}</button>
            </x-slot:bulkActions>
        </x-ui.datatable>
    </div>

</div>
@endsection

@push('page-styles')
<style>
    /* ── Extra badge variants for the mockups ── */

    /* Status pills */
    .wcrb-pill--progress {
        color: #1d4ed8;
        background: rgba(59, 130, 246, 0.10);
        border-color: rgba(59, 130, 246, 0.25);
    }
    .wcrb-pill--pending {
        color: #92400e;
        background: rgba(245, 158, 11, 0.10);
        border-color: rgba(245, 158, 11, 0.25);
    }
    .wcrb-pill--warning {
        color: #92400e;
        background: rgba(245, 158, 11, 0.10);
        border-color: rgba(245, 158, 11, 0.25);
    }
    .wcrb-pill--danger {
        color: #991b1b;
        background: rgba(239, 68, 68, 0.10);
        border-color: rgba(239, 68, 68, 0.25);
    }

    /* Priority pills */
    .wcrb-pill--high {
        color: #991b1b;
        background: rgba(239, 68, 68, 0.10);
        border-color: rgba(239, 68, 68, 0.25);
    }
    .wcrb-pill--medium {
        color: #92400e;
        background: rgba(245, 158, 11, 0.10);
        border-color: rgba(245, 158, 11, 0.25);
    }
    .wcrb-pill--low {
        color: #065f46;
        background: rgba(16, 185, 129, 0.10);
        border-color: rgba(16, 185, 129, 0.25);
    }

    /* Category pills */
    .wcrb-pill--cat-screen     { color: #7c3aed; background: rgba(124, 58, 237, 0.08); border-color: rgba(124, 58, 237, 0.20); }
    .wcrb-pill--cat-battery    { color: #059669; background: rgba(5, 150, 105, 0.08);  border-color: rgba(5, 150, 105, 0.20);  }
    .wcrb-pill--cat-keyboard   { color: #0369a1; background: rgba(3, 105, 161, 0.08);  border-color: rgba(3, 105, 161, 0.20);  }
    .wcrb-pill--cat-connector  { color: #ea580c; background: rgba(234, 88, 12, 0.08);  border-color: rgba(234, 88, 12, 0.20);  }
    .wcrb-pill--cat-board      { color: #b91c1c; background: rgba(185, 28, 28, 0.08);  border-color: rgba(185, 28, 28, 0.20);  }
    .wcrb-pill--cat-camera     { color: #4338ca; background: rgba(67, 56, 202, 0.08);  border-color: rgba(67, 56, 202, 0.20);  }
    .wcrb-pill--cat-cooling    { color: #0284c7; background: rgba(2, 132, 199, 0.08);  border-color: rgba(2, 132, 199, 0.20);  }
    .wcrb-pill--cat-housing    { color: #854d0e; background: rgba(133, 77, 14, 0.08);  border-color: rgba(133, 77, 14, 0.20);  }
    .wcrb-pill--cat-storage    { color: #6b21a8; background: rgba(107, 33, 168, 0.08); border-color: rgba(107, 33, 168, 0.20); }
    .wcrb-pill--cat-power      { color: #dc2626; background: rgba(220, 38, 38, 0.08);  border-color: rgba(220, 38, 38, 0.20);  }

    /* Column filter row  */
    .rb-col-filters th {
        background: #f1f5f9 !important;
        padding: 0.35rem 0.5rem !important;
    }

    /* Sortable header hover */
    .rb-sortable:hover {
        background: #eef2f7 !important;
        transition: background 0.15s ease;
    }

    /* Smooth transitions for Alpine x-show */
    [x-cloak] { display: none !important; }

    .rb-datatable-wrapper .table-primary td {
        background-color: rgba(59, 130, 246, 0.06) !important;
    }

    /* Dark mode badge overrides */
    [data-bs-theme="dark"] .wcrb-pill--progress  { background: rgba(59, 130, 246, 0.20); }
    [data-bs-theme="dark"] .wcrb-pill--pending   { background: rgba(245, 158, 11, 0.20); }
    [data-bs-theme="dark"] .wcrb-pill--warning   { background: rgba(245, 158, 11, 0.20); }
    [data-bs-theme="dark"] .wcrb-pill--danger    { background: rgba(239, 68, 68, 0.20); }
    [data-bs-theme="dark"] .wcrb-pill--high      { background: rgba(239, 68, 68, 0.20); }
    [data-bs-theme="dark"] .wcrb-pill--medium    { background: rgba(245, 158, 11, 0.20); }
    [data-bs-theme="dark"] .wcrb-pill--low       { background: rgba(16, 185, 129, 0.20); }
</style>
@endpush
