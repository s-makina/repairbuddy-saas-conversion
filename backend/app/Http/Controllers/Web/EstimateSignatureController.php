<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddySignatureRequest;
use App\Models\Tenant;
use App\Services\SignatureWorkflowService;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EstimateSignatureController extends Controller
{
    public function __construct(
        protected SignatureWorkflowService $signatureService,
    ) {}

    /* ------------------------------------------------------------------ */
    /*  AUTHENTICATED — Signature Requests List for an Estimate           */
    /* ------------------------------------------------------------------ */
    public function index(string $business, int $estimateId)
    {
        $tenant = TenantContext::getTenantOrFail($business);
        $estimate = RepairBuddyEstimate::where('tenant_id', $tenant->id)
            ->where('id', $estimateId)
            ->with(['customer', 'devices.customerDevice.device'])
            ->firstOrFail();

        $signatureRequests = RepairBuddySignatureRequest::query()
            ->where('estimate_id', $estimate->id)
            ->orderByDesc('created_at')
            ->get();

        // Count by status
        $countPending = $signatureRequests->where('status', 'pending')
            ->filter(fn ($s) => ! $s->isExpired())->count();
        $countCompleted = $signatureRequests->where('status', 'completed')->count();
        $countExpired = $signatureRequests->filter(fn ($s) => $s->isExpired())->count();
        $countTotal = $signatureRequests->count();

        // Get estimate signature settings
        $store = new TenantSettingsStore($tenant);
        $signatureSettings = $store->get('estimate_signature', []);

        return view('tenant.estimate_signatures.index', [
            'tenant'            => $tenant,
            'estimate'          => $estimate,
            'signatureRequests' => $signatureRequests,
            'countPending'      => $countPending,
            'countCompleted'    => $countCompleted,
            'countExpired'      => $countExpired,
            'countTotal'        => $countTotal,
            'signatureSettings' => $signatureSettings,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  AUTHENTICATED — Create form for a new signature request           */
    /* ------------------------------------------------------------------ */
    public function create(string $business, int $estimateId)
    {
        $tenant = TenantContext::getTenantOrFail($business);
        $estimate = RepairBuddyEstimate::where('tenant_id', $tenant->id)
            ->where('id', $estimateId)
            ->with(['customer', 'devices.customerDevice.device'])
            ->firstOrFail();

        return view('tenant.estimate_signatures.create', [
            'tenant'   => $tenant,
            'estimate' => $estimate,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  AUTHENTICATED — Store a new signature request                     */
    /* ------------------------------------------------------------------ */
    public function store(Request $request, string $business, int $estimateId)
    {
        $tenant = TenantContext::getTenantOrFail($business);
        $estimate = RepairBuddyEstimate::where('tenant_id', $tenant->id)
            ->where('id', $estimateId)
            ->firstOrFail();

        $validated = $request->validate([
            'signature_type'  => 'required|in:approval,pickup,delivery,custom',
            'signature_label' => 'required|string|max:255',
            'send_email'      => 'nullable|boolean',
        ]);

        $user = Auth::user();

        // Generate the signature request
        $signatureRequest = $this->signatureService->generateRequestForEstimate(
            tenant: $tenant,
            estimate: $estimate,
            signatureType: $validated['signature_type'],
            signatureLabel: $validated['signature_label'],
            generatedBy: $user,
        );

        // Send email notification if requested
        $sendEmail = ! empty($validated['send_email']);
        if ($sendEmail && $estimate->customer && $estimate->customer->email) {
            $this->signatureService->sendSignatureNotificationForEstimate(
                tenant: $tenant,
                estimate: $estimate,
                signatureRequest: $signatureRequest,
                triggeredBy: $user,
            );
        }

        return redirect()->route('tenant.estimates.signatures.generator', [
            'business'    => $tenant->slug,
            'estimateId'  => $estimate->id,
            'signatureId' => $signatureRequest->id,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  AUTHENTICATED — Generator page (shows URL to copy/share)         */
    /* ------------------------------------------------------------------ */
    public function generator(string $business, int $estimateId, int $signatureId)
    {
        $tenant = TenantContext::getTenantOrFail($business);
        $estimate = RepairBuddyEstimate::where('tenant_id', $tenant->id)
            ->where('id', $estimateId)
            ->with(['customer'])
            ->firstOrFail();

        $signatureRequest = RepairBuddySignatureRequest::where('estimate_id', $estimate->id)
            ->where('id', $signatureId)
            ->firstOrFail();

        $signatureUrl = $signatureRequest->getSignatureUrl($tenant->slug);

        return view('tenant.estimate_signatures.generator', [
            'tenant'           => $tenant,
            'estimate'         => $estimate,
            'signatureRequest' => $signatureRequest,
            'signatureUrl'     => $signatureUrl,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  AUTHENTICATED — Show signature request details                    */
    /* ------------------------------------------------------------------ */
    public function show(string $business, int $estimateId, int $signatureId)
    {
        $tenant = TenantContext::getTenantOrFail($business);
        $estimate = RepairBuddyEstimate::where('tenant_id', $tenant->id)
            ->where('id', $estimateId)
            ->with(['customer'])
            ->firstOrFail();

        $signatureRequest = RepairBuddySignatureRequest::where('estimate_id', $estimate->id)
            ->where('id', $signatureId)
            ->with(['generator'])
            ->firstOrFail();

        $signatureUrl = $signatureRequest->getSignatureUrl($tenant->slug);

        return view('tenant.estimate_signatures.show', [
            'tenant'           => $tenant,
            'estimate'         => $estimate,
            'signatureRequest' => $signatureRequest,
            'signatureUrl'     => $signatureUrl,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  AUTHENTICATED — Send (or resend) signature request email         */
    /* ------------------------------------------------------------------ */
    public function sendEmail(Request $request, string $business, int $estimateId, int $signatureId)
    {
        $tenant = TenantContext::getTenantOrFail($business);
        $estimate = RepairBuddyEstimate::where('tenant_id', $tenant->id)
            ->where('id', $estimateId)
            ->firstOrFail();

        $signatureRequest = RepairBuddySignatureRequest::where('estimate_id', $estimate->id)
            ->where('id', $signatureId)
            ->firstOrFail();

        if ($signatureRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'Cannot send email for a non-pending signature request.']);
        }

        $user = Auth::user();

        $this->signatureService->sendSignatureNotificationForEstimate(
            tenant: $tenant,
            estimate: $estimate,
            signatureRequest: $signatureRequest,
            triggeredBy: $user,
        );

        return back()->with('status', 'Signature request email sent successfully.');
    }
}
