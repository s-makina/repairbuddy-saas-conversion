<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateDevice;
use App\Models\RepairBuddyEstimateItem;
use App\Models\RepairBuddyEstimateToken;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyService;
use App\Models\Role;
use App\Models\User;
use App\Notifications\EstimateToCustomerNotification;
use App\Support\RepairBuddyCaseNumberService;
use App\Support\BranchContext;
use App\Support\RepairBuddyEstimateConversionService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class TenantEstimateController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  LIST (GET /t/{business}/estimates)                                */
    /* ------------------------------------------------------------------ */
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();

        if (! $tenant || ! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        /* ---------- build query ---------- */
        $query = RepairBuddyEstimate::query()
            ->with(['customer', 'assignedTechnician', 'devices', 'items.tax'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->orderBy('id', 'desc');

        /* search */
        $search = is_string($request->query('searchinput'))
            ? trim((string) $request->query('searchinput')) : '';

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('case_detail', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        /* status filter */
        $statusFilter = is_string($request->query('estimate_status'))
            ? trim((string) $request->query('estimate_status')) : '';

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            if (in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
                $query->where('status', $statusFilter);
            }
        }

        /* device filter */
        $deviceFilter = $request->query('device_post_id');
        if (is_numeric($deviceFilter) && (int) $deviceFilter > 0) {
            $query->whereHas('devices', function ($dq) use ($deviceFilter) {
                $dq->where('customer_device_id', (int) $deviceFilter);
            });
        }

        /* customer filter */
        $customerFilter = $request->query('customer_id');
        if (is_numeric($customerFilter) && (int) $customerFilter > 0) {
            $query->where('customer_id', (int) $customerFilter);
        }

        /* technician filter */
        $technicianFilter = $request->query('technician_id');
        if (is_numeric($technicianFilter) && (int) $technicianFilter > 0) {
            $query->where('assigned_technician_id', (int) $technicianFilter);
        }

        /* ---------- paginate ---------- */
        $perPage   = 20;
        $estimates = $query->paginate($perPage)->appends($request->query());

        /* ---------- counts for stats ---------- */
        $baseCountQuery = RepairBuddyEstimate::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id);

        $countPending  = (clone $baseCountQuery)->where('status', 'pending')->count();
        $countApproved = (clone $baseCountQuery)->where('status', 'approved')->count();
        $countRejected = (clone $baseCountQuery)->where('status', 'rejected')->count();

        /* ---------- look-ups for filter dropdowns ---------- */
        $customers = User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('role', 'customer')
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name']);

        $technicians = $this->loadTechnicians($tenant);

        $customerDevices = RepairBuddyCustomerDevice::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->orderBy('id', 'desc')
            ->limit(500)
            ->get(['id', 'label', 'serial']);

        $currency = is_string($tenant->currency) && $tenant->currency !== ''
            ? strtoupper((string) $tenant->currency) : 'USD';

        /* use table view vs card view */
        $viewMode = is_string($request->query('view'))
            ? trim((string) $request->query('view')) : 'table';
        if (! in_array($viewMode, ['table', 'card'], true)) {
            $viewMode = 'table';
        }

        return view('tenant.estimates.index', [
            'tenant'          => $tenant,
            'user'            => $user,
            'activeNav'       => 'estimates',
            'pageTitle'       => 'Estimates',
            'estimates'       => $estimates,
            'countPending'    => $countPending,
            'countApproved'   => $countApproved,
            'countRejected'   => $countRejected,
            'customers'       => $customers,
            'technicians'     => $technicians,
            'customerDevices' => $customerDevices,
            'currency'        => $currency,
            'viewMode'        => $viewMode,
            'searchInput'     => $search,
            'statusFilter'    => $statusFilter,
            'deviceFilter'    => $deviceFilter,
            'customerFilterId'   => $customerFilter,
            'technicianFilterId' => $technicianFilter,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  DATATABLE (GET /t/{business}/estimates/datatable)                 */
    /* ------------------------------------------------------------------ */
    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $branch = BranchContext::branch();
        if (! $tenant || ! $branch instanceof Branch) {
            return response()->json(['message' => 'Tenant or branch context is missing.'], 400);
        }

        $query = RepairBuddyEstimate::query()
            ->with(['customer', 'assignedTechnician', 'devices', 'items.tax'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id);

        return DataTables::eloquent($query)
            ->addColumn('id_display', fn (RepairBuddyEstimate $e) => '# ' . $e->id)
            ->addColumn('case_number_display', function (RepairBuddyEstimate $e) {
                return is_string($e->case_number) ? (string) $e->case_number : '';
            })
            ->addColumn('tech_display', function (RepairBuddyEstimate $e) {
                return $e->assignedTechnician?->name ?? '';
            })
            ->addColumn('customer_display', function (RepairBuddyEstimate $e) {
                $c = $e->customer;
                if (! $c) return '';
                $parts = [];
                if (is_string($c->name) && $c->name !== '') $parts[] = e($c->name);
                if (is_string($c->phone) && trim($c->phone) !== '') $parts[] = '<strong>P</strong>: ' . e($c->phone);
                if (is_string($c->email) && trim($c->email) !== '') $parts[] = '<strong>E</strong>: ' . e($c->email);
                return implode('<br>', $parts);
            })
            ->addColumn('devices_display', function (RepairBuddyEstimate $e) {
                $labels = [];
                foreach ($e->devices as $d) {
                    $label = is_string($d->label_snapshot ?? null) ? trim((string) $d->label_snapshot) : '';
                    $serial = is_string($d->serial_snapshot ?? null) ? trim((string) $d->serial_snapshot) : '';
                    if ($label !== '') {
                        $labels[] = $label . ($serial !== '' ? ' (' . $serial . ')' : '');
                    }
                }
                return e(implode(', ', array_slice($labels, 0, 3)));
            })
            ->addColumn('total_display', function (RepairBuddyEstimate $e) {
                $total = 0;
                foreach ($e->items as $item) {
                    $qty  = is_numeric($item->qty) ? (int) $item->qty : 1;
                    $unit = is_numeric($item->unit_price_amount_cents) ? (int) $item->unit_price_amount_cents : 0;
                    $total += ($qty * $unit);
                }
                return '$' . number_format($total / 100, 2);
            })
            ->addColumn('status_display', function (RepairBuddyEstimate $e) {
                $status = is_string($e->status) ? $e->status : 'pending';
                $colors = [
                    'pending'  => 'bg-info',
                    'approved' => 'bg-success',
                    'rejected' => 'bg-danger',
                ];
                $cls = $colors[$status] ?? 'bg-secondary';
                $label = ucfirst($status);
                return '<span class="badge ' . $cls . '">' . e($label) . '</span>';
            })
            ->addColumn('actions_display', function (RepairBuddyEstimate $e) use ($tenant) {
                if (! $tenant?->slug) return '';
                $showUrl = route('tenant.estimates.show', ['business' => $tenant->slug, 'estimateId' => $e->id]);
                return '<a class="btn btn-outline-primary btn-sm" href="' . e($showUrl) . '" title="View"><i class="bi bi-eye"></i></a>';
            })
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                $search = is_string($search) ? trim($search) : '';
                if ($search === '') return;
                $query->where(function ($q) use ($search) {
                    $q->where('case_number', 'like', '%' . $search . '%')
                      ->orWhere('title', 'like', '%' . $search . '%')
                      ->orWhere('case_detail', 'like', '%' . $search . '%');
                });
            })
            ->rawColumns(['customer_display', 'status_display', 'actions_display'])
            ->toJson();
    }

    /* ------------------------------------------------------------------ */
    /*  SHOW (GET /t/{business}/estimates/{estimateId})                   */
    /* ------------------------------------------------------------------ */
    public function show(Request $request, string $business, $estimateId)
    {
        if (! is_numeric($estimateId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();
        if (! $tenant || ! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $estimate = RepairBuddyEstimate::query()
            ->with(['customer', 'assignedTechnician', 'devices', 'items.tax', 'attachments'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->whereKey((int) $estimateId)
            ->first();

        if (! $estimate) {
            abort(404);
        }

        $items = $estimate->items;
        $devices = $estimate->devices;
        $attachments = $estimate->attachments;

        $events = RepairBuddyEvent::query()
            ->with(['actor'])
            ->where('entity_type', 'estimate')
            ->where('entity_id', $estimate->id)
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        $currency = is_string($tenant->currency) && $tenant->currency !== ''
            ? strtoupper((string) $tenant->currency) : 'USD';

        /* categorize items same as plugin */
        $productItems = [];
        $partItems    = [];
        $serviceItems = [];
        $extraItems   = [];

        foreach ($items as $item) {
            $type = is_string($item->item_type) ? strtolower((string) $item->item_type) : 'other';
            switch ($type) {
                case 'product':
                case 'products':
                    $productItems[] = $item;
                    break;
                case 'part':
                case 'parts':
                    $partItems[] = $item;
                    break;
                case 'service':
                case 'services':
                    $serviceItems[] = $item;
                    break;
                default:
                    $extraItems[] = $item;
                    break;
            }
        }

        /* compute per-category and grand totals */
        $computeCategoryTotals = function (array $categoryItems) {
            $sub = 0;
            $tax = 0;
            foreach ($categoryItems as $item) {
                $qty  = is_numeric($item->qty) ? (int) $item->qty : 1;
                $unit = is_numeric($item->unit_price_amount_cents) ? (int) $item->unit_price_amount_cents : 0;
                $line = $qty * $unit;
                $sub += $line;
                if ($item->relationLoaded('tax') && $item->tax) {
                    $rate = (float) ($item->tax->rate ?? 0);
                    $tax += (int) round($line * ($rate / 100.0));
                }
            }
            return ['subtotal' => $sub, 'tax' => $tax, 'total' => $sub + $tax];
        };

        $productTotals = $computeCategoryTotals($productItems);
        $partTotals    = $computeCategoryTotals($partItems);
        $serviceTotals = $computeCategoryTotals($serviceItems);
        $extraTotals   = $computeCategoryTotals($extraItems);

        $subtotalCents = $productTotals['subtotal'] + $partTotals['subtotal'] + $serviceTotals['subtotal'] + $extraTotals['subtotal'];
        $taxCents = $productTotals['tax'] + $partTotals['tax'] + $serviceTotals['tax'] + $extraTotals['tax'];

        $totals = [
            'subtotal_cents' => $subtotalCents,
            'tax_cents'      => $taxCents,
            'total_cents'    => $subtotalCents + $taxCents,
            'currency'       => $currency,
            'products' => $productTotals,
            'parts'    => $partTotals,
            'services' => $serviceTotals,
            'extras'   => $extraTotals,
        ];

        return view('tenant.job_show', [
            'tenant'       => $tenant,
            'user'         => $user,
            'activeNav'    => 'estimates',
            'pageTitle'    => 'Estimate ' . ($estimate->case_number ?? '#' . $estimate->id),
            // Pass as $estimate so the view enters estimate mode
            'estimate'     => $estimate,
            // Map estimate items/devices/events to the shared variable names
            'jobItems'     => $items,
            'jobDevices'   => $devices,
            'jobAttachments' => $attachments,
            'jobEvents'    => $events,
            // Estimate-only: also pass categorised arrays for financial summary
            'productItems' => $productItems,
            'partItems'    => $partItems,
            'serviceItems' => $serviceItems,
            'extraItems'   => $extraItems,
            // Not applicable for estimates
            'jobTimelogs'  => collect(),
            'jobPayments'  => collect(),
            'jobExpenses'  => collect(),
            'jobFeedback'  => collect(),
            'totals'       => $totals,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  CREATE FORM (GET /t/{business}/estimates/new)                     */
    /* ------------------------------------------------------------------ */
    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();
        if (! $tenant || ! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $customers = User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('role', 'customer')
            ->orderBy('name')
            ->limit(500)
            ->get();

        $technicians = $this->loadTechnicians($tenant);

        $customerDevices = RepairBuddyCustomerDevice::query()
            ->with(['customer'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->orderBy('id', 'desc')
            ->limit(500)
            ->get();

        $devices = RepairBuddyDevice::query()
            ->with(['type', 'brand', 'parent'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('model')
            ->limit(2000)
            ->get();

        $parts = RepairBuddyPart::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'sku', 'price_amount_cents', 'price_currency']);

        $services = RepairBuddyService::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'service_code', 'base_price_amount_cents', 'base_price_currency']);

        return view('tenant.estimates.create', [
            'tenant'          => $tenant,
            'user'            => $user,
            'activeNav'       => 'estimates',
            'pageTitle'       => 'New Estimate',
            'estimate'        => null,
            'customers'       => $customers,
            'technicians'     => $technicians,
            'customerDevices' => $customerDevices,
            'devices'         => $devices,
            'parts'           => $parts,
            'services'        => $services,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT FORM (GET /t/{business}/estimates/{estimateId}/edit)         */
    /* ------------------------------------------------------------------ */
    public function edit(Request $request, string $business, $estimateId)
    {
        if (! is_numeric($estimateId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();
        if (! $tenant || ! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $estimate = RepairBuddyEstimate::query()
            ->with(['customer', 'assignedTechnician', 'devices', 'items.tax'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->whereKey((int) $estimateId)
            ->first();

        if (! $estimate) {
            abort(404);
        }

        $customers = User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('role', 'customer')
            ->orderBy('name')
            ->limit(500)
            ->get();

        $technicians = $this->loadTechnicians($tenant);

        $customerDevices = RepairBuddyCustomerDevice::query()
            ->with(['customer'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->orderBy('id', 'desc')
            ->limit(500)
            ->get();

        $devices = RepairBuddyDevice::query()
            ->with(['type', 'brand', 'parent'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('model')
            ->limit(2000)
            ->get();

        $parts = RepairBuddyPart::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'sku', 'price_amount_cents', 'price_currency']);

        $services = RepairBuddyService::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'service_code', 'base_price_amount_cents', 'base_price_currency']);

        return view('tenant.estimates.create', [
            'tenant'          => $tenant,
            'user'            => $user,
            'activeNav'       => 'estimates',
            'pageTitle'       => 'Edit Estimate ' . ($estimate->case_number ?? '#' . $estimate->id),
            'estimate'        => $estimate,
            'customers'       => $customers,
            'technicians'     => $technicians,
            'customerDevices' => $customerDevices,
            'devices'         => $devices,
            'parts'           => $parts,
            'services'        => $services,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE (POST /t/{business}/estimates)                              */
    /* ------------------------------------------------------------------ */
    public function store(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();
        if (! $tenant || ! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $validated = $request->validate([
            'case_number'           => ['sometimes', 'nullable', 'string', 'max:64'],
            'title'                 => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_id'           => ['sometimes', 'nullable', 'integer'],
            'assigned_technician_id'=> ['sometimes', 'nullable', 'integer'],
            'pickup_date'           => ['sometimes', 'nullable', 'date'],
            'delivery_date'         => ['sometimes', 'nullable', 'date'],
            'case_detail'           => ['sometimes', 'nullable', 'string', 'max:5000'],
            'order_notes'           => ['sometimes', 'nullable', 'string', 'max:5000'],

            /* devices – nested array: devices[0][customer_device_id], devices[0][serial], etc. */
            'devices'                        => ['sometimes', 'array'],
            'devices.*.customer_device_id'   => ['sometimes', 'nullable', 'integer'],
            'devices.*.serial'               => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.pin'                  => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.notes'                => ['sometimes', 'nullable', 'string', 'max:5000'],

            /* line items – nested array: items[0][type], items[0][name], items[0][unit_price_dollars], etc. */
            'items'                       => ['sometimes', 'array'],
            'items.*.type'                => ['sometimes', 'nullable', 'string', 'max:32'],
            'items.*.name'                => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.qty'                 => ['sometimes', 'nullable', 'integer', 'min:1', 'max:9999'],
            'items.*.unit_price_dollars'  => ['sometimes', 'nullable', 'numeric', 'min:-10000000', 'max:10000000'],
        ]);

        $caseNumber = is_string($validated['case_number'] ?? null) ? trim((string) $validated['case_number']) : '';
        if ($caseNumber === '') {
            $caseNumber = $this->generateCaseNumber($tenant, $branch);
        }

        $title = is_string($validated['title'] ?? null) ? trim((string) $validated['title']) : '';
        if ($title === '') {
            $title = $caseNumber;
        }

        $estimate = DB::transaction(function () use ($validated, $caseNumber, $title, $tenant, $branch, $user) {
            $estimate = RepairBuddyEstimate::query()->create([
                'tenant_id'              => (int) $tenant->id,
                'branch_id'              => (int) $branch->id,
                'case_number'            => $caseNumber,
                'title'                  => $title,
                'status'                 => 'pending',
                'customer_id'            => is_numeric($validated['customer_id'] ?? null) ? (int) $validated['customer_id'] : null,
                'created_by'             => $user->id,
                'assigned_technician_id' => is_numeric($validated['assigned_technician_id'] ?? null) ? (int) $validated['assigned_technician_id'] : null,
                'pickup_date'            => $validated['pickup_date'] ?? null,
                'delivery_date'          => $validated['delivery_date'] ?? null,
                'case_detail'            => $validated['case_detail'] ?? null,
            ]);

            /* devices */
            $devices = (array) ($validated['devices'] ?? []);
            foreach ($devices as $dev) {
                $cdId = $dev['customer_device_id'] ?? null;
                if (! is_numeric($cdId) || (int) $cdId <= 0) continue;

                $cd = RepairBuddyCustomerDevice::query()->whereKey((int) $cdId)->first();

                RepairBuddyEstimateDevice::query()->create([
                    'estimate_id'        => $estimate->id,
                    'customer_device_id' => (int) $cdId,
                    'label_snapshot'     => $cd?->label ?? '',
                    'serial_snapshot'    => ($dev['serial'] ?? null) ?: ($cd?->serial),
                    'pin_snapshot'       => $dev['pin'] ?? null,
                    'notes_snapshot'     => $dev['notes'] ?? null,
                    'extra_fields_snapshot_json' => [],
                ]);
            }

            /* items – convert dollars to cents */
            $items = (array) ($validated['items'] ?? []);

            $currency = is_string($tenant->currency) && $tenant->currency !== ''
                ? strtoupper((string) $tenant->currency) : 'USD';

            foreach ($items as $item) {
                $name = $item['name'] ?? '';
                if (! is_string($name) || trim($name) === '') continue;

                $priceDollars = $item['unit_price_dollars'] ?? 0;
                $priceCents   = (int) round(floatval($priceDollars) * 100);

                RepairBuddyEstimateItem::query()->create([
                    'estimate_id'             => $estimate->id,
                    'item_type'               => is_string($item['type'] ?? null) ? (string) $item['type'] : 'other',
                    'ref_id'                  => null,
                    'name_snapshot'           => trim($name),
                    'qty'                     => is_numeric($item['qty'] ?? null) ? (int) $item['qty'] : 1,
                    'unit_price_amount_cents' => $priceCents,
                    'unit_price_currency'     => $currency,
                    'tax_id'                  => null,
                    'meta_json'               => null,
                ]);
            }

            /* audit event */
            RepairBuddyEvent::query()->create([
                'actor_user_id' => $user->id,
                'entity_type'   => 'estimate',
                'entity_id'     => $estimate->id,
                'visibility'    => 'private',
                'event_type'    => 'estimate.created',
                'payload_json'  => [
                    'title'       => 'Estimate created',
                    'case_number' => $caseNumber,
                ],
            ]);

            return $estimate;
        });

        return redirect()->route('tenant.estimates.show', [
            'business'   => $tenant->slug,
            'estimateId' => $estimate->id,
        ])->with('success', 'Estimate created successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE (PUT /t/{business}/estimates/{estimateId})                 */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, string $business, $estimateId)
    {
        if (! is_numeric($estimateId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();
        if (! $tenant || ! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $estimate = RepairBuddyEstimate::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->whereKey((int) $estimateId)
            ->first();

        if (! $estimate) {
            abort(404);
        }

        $validated = $request->validate([
            'case_number'           => ['sometimes', 'nullable', 'string', 'max:64'],
            'title'                 => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_id'           => ['sometimes', 'nullable', 'integer'],
            'assigned_technician_id'=> ['sometimes', 'nullable', 'integer'],
            'pickup_date'           => ['sometimes', 'nullable', 'date'],
            'delivery_date'         => ['sometimes', 'nullable', 'date'],
            'case_detail'           => ['sometimes', 'nullable', 'string', 'max:5000'],
            'order_notes'           => ['sometimes', 'nullable', 'string', 'max:5000'],

            /* devices – nested array: devices[0][customer_device_id], etc. */
            'devices'                        => ['sometimes', 'array'],
            'devices.*.customer_device_id'   => ['sometimes', 'nullable', 'integer'],
            'devices.*.serial'               => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.pin'                  => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.notes'                => ['sometimes', 'nullable', 'string', 'max:5000'],

            /* line items – nested array: items[0][type], items[0][unit_price_dollars], etc. */
            'items'                       => ['sometimes', 'array'],
            'items.*.type'                => ['sometimes', 'nullable', 'string', 'max:32'],
            'items.*.name'                => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.qty'                 => ['sometimes', 'nullable', 'integer', 'min:1', 'max:9999'],
            'items.*.unit_price_dollars'  => ['sometimes', 'nullable', 'numeric', 'min:-10000000', 'max:10000000'],
        ]);

        DB::transaction(function () use ($estimate, $validated, $tenant, $user) {
            $fills = [];
            if (array_key_exists('case_number', $validated) && is_string($validated['case_number'])) {
                $fills['case_number'] = trim((string) $validated['case_number']);
            }
            if (array_key_exists('title', $validated) && is_string($validated['title'])) {
                $fills['title'] = trim((string) $validated['title']);
            }
            if (array_key_exists('customer_id', $validated)) {
                $fills['customer_id'] = is_numeric($validated['customer_id']) ? (int) $validated['customer_id'] : null;
            }
            if (array_key_exists('assigned_technician_id', $validated)) {
                $fills['assigned_technician_id'] = is_numeric($validated['assigned_technician_id']) ? (int) $validated['assigned_technician_id'] : null;
            }
            if (array_key_exists('pickup_date', $validated)) {
                $fills['pickup_date'] = $validated['pickup_date'];
            }
            if (array_key_exists('delivery_date', $validated)) {
                $fills['delivery_date'] = $validated['delivery_date'];
            }
            if (array_key_exists('case_detail', $validated)) {
                $fills['case_detail'] = $validated['case_detail'];
            }

            if (count($fills) > 0) {
                $estimate->forceFill($fills)->save();
            }

            /* rebuild devices */
            $devices = (array) ($validated['devices'] ?? []);
            if (count($devices) > 0) {
                RepairBuddyEstimateDevice::query()->where('estimate_id', $estimate->id)->delete();

                foreach ($devices as $dev) {
                    $cdId = $dev['customer_device_id'] ?? null;
                    if (! is_numeric($cdId) || (int) $cdId <= 0) continue;
                    $cd = RepairBuddyCustomerDevice::query()->whereKey((int) $cdId)->first();

                    RepairBuddyEstimateDevice::query()->create([
                        'estimate_id'        => $estimate->id,
                        'customer_device_id' => (int) $cdId,
                        'label_snapshot'     => $cd?->label ?? '',
                        'serial_snapshot'    => ($dev['serial'] ?? null) ?: ($cd?->serial),
                        'pin_snapshot'       => $dev['pin'] ?? null,
                        'notes_snapshot'     => $dev['notes'] ?? null,
                        'extra_fields_snapshot_json' => [],
                    ]);
                }
            }

            /* rebuild items – convert dollars to cents */
            $items = (array) ($validated['items'] ?? []);
            if (count($items) > 0) {
                RepairBuddyEstimateItem::query()->where('estimate_id', $estimate->id)->delete();

                $currency = is_string($tenant->currency) && $tenant->currency !== ''
                    ? strtoupper((string) $tenant->currency) : 'USD';

                foreach ($items as $item) {
                    $name = $item['name'] ?? '';
                    if (! is_string($name) || trim($name) === '') continue;

                    $priceDollars = $item['unit_price_dollars'] ?? 0;
                    $priceCents   = (int) round(floatval($priceDollars) * 100);

                    RepairBuddyEstimateItem::query()->create([
                        'estimate_id'             => $estimate->id,
                        'item_type'               => is_string($item['type'] ?? null) ? (string) $item['type'] : 'other',
                        'ref_id'                  => null,
                        'name_snapshot'           => trim($name),
                        'qty'                     => is_numeric($item['qty'] ?? null) ? (int) $item['qty'] : 1,
                        'unit_price_amount_cents' => $priceCents,
                        'unit_price_currency'     => $currency,
                        'tax_id'                  => null,
                        'meta_json'               => null,
                    ]);
                }
            }

            /* audit event */
            RepairBuddyEvent::query()->create([
                'actor_user_id' => $user->id,
                'entity_type'   => 'estimate',
                'entity_id'     => $estimate->id,
                'visibility'    => 'private',
                'event_type'    => 'estimate.updated',
                'payload_json'  => [
                    'title'       => 'Estimate updated',
                    'case_number' => (string) $estimate->case_number,
                ],
            ]);
        });

        return redirect()->route('tenant.estimates.show', [
            'business'   => $tenant->slug,
            'estimateId' => $estimate->id,
        ])->with('success', 'Estimate updated successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  ACTIONS                                                           */
    /* ------------------------------------------------------------------ */

    /** POST /t/{business}/estimates/{estimateId}/approve */
    public function approve(Request $request, string $business, $estimateId)
    {
        $estimate = $this->findOwnEstimate($estimateId);

        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if ($estimate->status === 'approved' && $estimate->converted_job_id) {
            return redirect()->route('tenant.estimates.show', [
                'business' => $tenant->slug, 'estimateId' => $estimate->id,
            ])->with('info', 'Estimate was already approved.');
        }

        $svc = app(RepairBuddyEstimateConversionService::class);
        $job = $svc->convertToJob($estimate, $user?->id);

        return redirect()->route('tenant.estimates.show', [
            'business' => $tenant->slug, 'estimateId' => $estimate->id,
        ])->with('success', 'Estimate approved and converted to Job #' . $job->case_number . '.');
    }

    /** POST /t/{business}/estimates/{estimateId}/reject */
    public function reject(Request $request, string $business, $estimateId)
    {
        $estimate = $this->findOwnEstimate($estimateId);
        $tenant = TenantContext::tenant();
        $user   = $request->user();

        $estimate->forceFill([
            'status'      => 'rejected',
            'rejected_at' => now(),
        ])->save();

        RepairBuddyEvent::query()->create([
            'actor_user_id' => $user?->id,
            'entity_type'   => 'estimate',
            'entity_id'     => $estimate->id,
            'visibility'    => 'private',
            'event_type'    => 'estimate.rejected',
            'payload_json'  => [
                'title'       => 'Estimate rejected by admin',
                'case_number' => (string) $estimate->case_number,
            ],
        ]);

        return redirect()->route('tenant.estimates.show', [
            'business' => $tenant->slug, 'estimateId' => $estimate->id,
        ])->with('success', 'Estimate rejected.');
    }

    /** POST /t/{business}/estimates/{estimateId}/convert */
    public function convert(Request $request, string $business, $estimateId)
    {
        $estimate = $this->findOwnEstimate($estimateId);
        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if ($estimate->converted_job_id) {
            return redirect()->route('tenant.estimates.show', [
                'business' => $tenant->slug, 'estimateId' => $estimate->id,
            ])->with('info', 'Estimate has already been converted to a job.');
        }

        $svc = app(RepairBuddyEstimateConversionService::class);
        $job = $svc->convertToJob($estimate, $user?->id);

        return redirect()->route('tenant.estimates.show', [
            'business' => $tenant->slug, 'estimateId' => $estimate->id,
        ])->with('success', 'Estimate converted to Job #' . $job->case_number . '.');
    }

    /** POST /t/{business}/estimates/{estimateId}/send */
    public function send(Request $request, string $business, $estimateId)
    {
        $estimate = $this->findOwnEstimate($estimateId);
        $tenant = TenantContext::tenant();
        $branch = BranchContext::branch();
        $user   = $request->user();

        $estimate->load('customer');

        if (! $estimate->customer || ! $estimate->customer->email) {
            return redirect()->route('tenant.estimates.show', [
                'business' => $tenant->slug, 'estimateId' => $estimate->id,
            ])->with('error', 'Customer email address is missing.');
        }

        /* Build subject & body */
        $subject = $request->input('email_subject',
            'Estimate ' . ($estimate->case_number ?? '#' . $estimate->id) . ' from ' . ($tenant->name ?? 'RepairBuddy'));
        $body = $request->input('email_body',
            'Hello ' . ($estimate->customer->name ?? '') . ",\n\n"
            . 'Please find your estimate ' . ($estimate->case_number ?? '') . " below.\n"
            . "You can approve or reject this estimate using the buttons below.\n\n"
            . 'Thank you.');

        /* Create approve/reject tokens */
        $approveToken = Str::random(64);
        $rejectToken  = Str::random(64);

        RepairBuddyEstimateToken::query()->create([
            'tenant_id'  => (int) $tenant->id,
            'branch_id'  => (int) ($branch->id ?? 0),
            'estimate_id' => $estimate->id,
            'purpose'    => 'approve',
            'token_hash' => hash('sha256', $approveToken),
            'expires_at' => now()->addDays(30),
        ]);

        RepairBuddyEstimateToken::query()->create([
            'tenant_id'  => (int) $tenant->id,
            'branch_id'  => (int) ($branch->id ?? 0),
            'estimate_id' => $estimate->id,
            'purpose'    => 'reject',
            'token_hash' => hash('sha256', $rejectToken),
            'expires_at' => now()->addDays(30),
        ]);

        $apiBase = rtrim((string) env('APP_URL', ''), '/');
        $approveUrl = $apiBase . '/api/t/' . $tenant->slug . '/estimates/' . urlencode((string) $estimate->case_number) . '/approve?token=' . urlencode($approveToken);
        $rejectUrl  = $apiBase . '/api/t/' . $tenant->slug . '/estimates/' . urlencode((string) $estimate->case_number) . '/reject?token=' . urlencode($rejectToken);

        try {
            $estimate->customer->notify(new EstimateToCustomerNotification(
                estimate: $estimate,
                subject: $subject,
                body: $body,
                approveUrl: $approveUrl,
                rejectUrl: $rejectUrl,
                attachPdf: false,
                pdfPath: null,
            ));
        } catch (\Throwable $e) {
            return redirect()->route('tenant.estimates.show', [
                'business' => $tenant->slug, 'estimateId' => $estimate->id,
            ])->with('error', 'Failed to send email: ' . $e->getMessage());
        }

        $estimate->forceFill(['sent_at' => now()])->save();

        RepairBuddyEvent::query()->create([
            'actor_user_id' => $user?->id,
            'entity_type'   => 'estimate',
            'entity_id'     => $estimate->id,
            'visibility'    => 'private',
            'event_type'    => 'estimate.sent',
            'payload_json'  => [
                'title'       => 'Estimate sent to customer',
                'case_number' => (string) $estimate->case_number,
                'sent_to'     => $estimate->customer->email,
            ],
        ]);

        return redirect()->route('tenant.estimates.show', [
            'business' => $tenant->slug, 'estimateId' => $estimate->id,
        ])->with('success', 'Estimate sent to ' . $estimate->customer->email . '.');
    }

    /** POST /t/{business}/estimates/{estimateId}/delete */
    public function destroy(Request $request, string $business, $estimateId)
    {
        $estimate = $this->findOwnEstimate($estimateId);
        $tenant = TenantContext::tenant();
        $user   = $request->user();

        DB::transaction(function () use ($estimate, $user) {
            RepairBuddyEstimateDevice::query()->where('estimate_id', $estimate->id)->delete();
            RepairBuddyEstimateItem::query()->where('estimate_id', $estimate->id)->delete();

            RepairBuddyEvent::query()->create([
                'actor_user_id' => $user?->id,
                'entity_type'   => 'estimate',
                'entity_id'     => $estimate->id,
                'visibility'    => 'private',
                'event_type'    => 'estimate.deleted',
                'payload_json'  => [
                    'title'       => 'Estimate deleted',
                    'case_number' => (string) $estimate->case_number,
                ],
            ]);

            $estimate->delete();
        });

        return redirect()->route('tenant.estimates.index', [
            'business' => $tenant->slug,
        ])->with('success', 'Estimate deleted.');
    }

    /* ================================================================== */
    /*  HELPERS                                                           */
    /* ================================================================== */

    private function findOwnEstimate($estimateId): RepairBuddyEstimate
    {
        if (! is_numeric($estimateId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $branch = BranchContext::branch();

        if (! $tenant || ! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $estimate = RepairBuddyEstimate::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->whereKey((int) $estimateId)
            ->first();

        if (! $estimate) {
            abort(404);
        }

        return $estimate;
    }

    private function loadTechnicians($tenant)
    {
        $technicianRoleId = Role::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('name', 'Technician')
            ->value('id');
        $technicianRoleId = is_numeric($technicianRoleId) ? (int) $technicianRoleId : null;

        return User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('is_admin', false)
            ->where('status', 'active')
            ->where(function ($q) use ($technicianRoleId) {
                if ($technicianRoleId) {
                    $q->where('role_id', $technicianRoleId);
                }
                $q->orWhereHas('roles', fn ($rq) => $rq->where('name', 'Technician'))
                  ->orWhere('role', 'technician');
            })
            ->orderBy('name')
            ->limit(500)
            ->get();
    }

    private function generateCaseNumber($tenant, $branch): string
    {
        $settings = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        if (! is_array($settings)) {
            $settings = [];
        }

        $general = [];
        if (array_key_exists('general', $settings) && is_array($settings['general'])) {
            $general = $settings['general'];
        }

        $svc = app(RepairBuddyCaseNumberService::class);

        return $svc->nextCaseNumber($tenant, $branch, $general);
    }
}
