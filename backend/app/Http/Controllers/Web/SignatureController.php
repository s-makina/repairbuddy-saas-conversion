<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddySignatureRequest;
use App\Models\Status;
use App\Models\Tenant;
use App\Services\SignatureWorkflowService;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SignatureController extends Controller
{
    public function __construct(
        private SignatureWorkflowService $signatureService,
    ) {}

    /* ────────────────────────────────────────────────────────────────
     *  AUTHENTICATED — Signature Requests List for a Job
     * ──────────────────────────────────────────────────────────────── */
    public function index(Request $request, string $business, $jobId)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();
        if (! $user) {
            return redirect()->route('web.login');
        }

        $job = RepairBuddyJob::query()
            ->with(['customer', 'signatureRequests' => fn ($q) => $q->orderBy('created_at', 'desc')])
            ->whereKey((int) $jobId)
            ->firstOrFail();

        $store = new TenantSettingsStore($tenant);
        $signatureSettings = $store->get('signature', []);

        return view('tenant.signatures.index', [
            'tenant'            => $tenant,
            'user'              => $user,
            'activeNav'         => 'jobs',
            'pageTitle'         => __('Signature Requests') . ' — ' . $job->case_number,
            'job'               => $job,
            'signatureRequests' => $job->signatureRequests,
            'signatureSettings' => $signatureSettings,
        ]);
    }

    /* ────────────────────────────────────────────────────────────────
     *  AUTHENTICATED — Generate a new signature request
     * ──────────────────────────────────────────────────────────────── */
    public function create(Request $request, string $business, $jobId)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();
        if (! $user) {
            return redirect()->route('web.login');
        }

        $job = RepairBuddyJob::query()
            ->with(['customer', 'jobDevices.customerDevice.device'])
            ->whereKey((int) $jobId)
            ->firstOrFail();

        $store = new TenantSettingsStore($tenant);
        $signatureSettings = $store->get('signature', []);

        $jobStatuses = Status::query()
            ->where('tenant_id', $tenant->id)
            ->where('entity_type', 'job')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('tenant.signatures.create', [
            'tenant'            => $tenant,
            'user'              => $user,
            'activeNav'         => 'jobs',
            'pageTitle'         => __('Generate Signature Request'),
            'job'               => $job,
            'signatureSettings' => $signatureSettings,
            'jobStatuses'       => $jobStatuses,
        ]);
    }

    /* ────────────────────────────────────────────────────────────────
     *  AUTHENTICATED — Store (generate) a signature request
     * ──────────────────────────────────────────────────────────────── */
    public function store(Request $request, string $business, $jobId)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();
        if (! $user) {
            return redirect()->route('web.login');
        }

        $validated = $request->validate([
            'signature_type'  => 'required|string|in:pickup,delivery,custom',
            'signature_label' => 'required|string|max:255',
            'send_email'      => 'nullable|boolean',
        ]);

        $job = RepairBuddyJob::query()
            ->with(['customer'])
            ->whereKey((int) $jobId)
            ->firstOrFail();

        $signatureRequest = $this->signatureService->generateRequest(
            tenant: $tenant,
            job: $job,
            signatureType: $validated['signature_type'],
            signatureLabel: $validated['signature_label'],
            generatedBy: $user,
        );

        // Optionally send email notification
        if (! empty($validated['send_email']) && $job->customer && $job->customer->email) {
            $this->signatureService->sendSignatureNotification($tenant, $job, $signatureRequest, $user);
        }

        $tenantSlug = is_string($tenant->slug) ? (string) $tenant->slug : '';

        return redirect()
            ->route('tenant.signatures.generator', [
                'business'    => $tenantSlug,
                'jobId'       => $job->id,
                'signatureId' => $signatureRequest->id,
            ])
            ->with('success', __('Signature request generated successfully.'));
    }

    /* ────────────────────────────────────────────────────────────────
     *  AUTHENTICATED — Generator page (shows URL to copy/share)
     * ──────────────────────────────────────────────────────────────── */
    public function generator(Request $request, string $business, $jobId, $signatureId)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();
        if (! $user) {
            return redirect()->route('web.login');
        }

        $job = RepairBuddyJob::query()
            ->with(['customer', 'jobDevices.customerDevice.device'])
            ->whereKey((int) $jobId)
            ->firstOrFail();

        $signatureRequest = RepairBuddySignatureRequest::query()
            ->where('job_id', $job->id)
            ->whereKey((int) $signatureId)
            ->firstOrFail();

        $signatureUrl = $signatureRequest->getSignatureUrl($tenant->slug);

        return view('tenant.signatures.generator', [
            'tenant'           => $tenant,
            'user'             => $user,
            'activeNav'        => 'jobs',
            'pageTitle'        => __('Signature Request Generated'),
            'job'              => $job,
            'signatureRequest' => $signatureRequest,
            'signatureUrl'     => $signatureUrl,
        ]);
    }

    /* ────────────────────────────────────────────────────────────────
     *  AUTHENTICATED — Send signature request email
     * ──────────────────────────────────────────────────────────────── */
    public function sendEmail(Request $request, string $business, $jobId, $signatureId)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();
        if (! $user) {
            return redirect()->route('web.login');
        }

        $job = RepairBuddyJob::query()
            ->with(['customer'])
            ->whereKey((int) $jobId)
            ->firstOrFail();

        $signatureRequest = RepairBuddySignatureRequest::query()
            ->where('job_id', $job->id)
            ->whereKey((int) $signatureId)
            ->firstOrFail();

        if (! $job->customer || ! $job->customer->email) {
            return redirect()->back()->with('error', __('Customer does not have an email address.'));
        }

        $this->signatureService->sendSignatureNotification($tenant, $job, $signatureRequest, $user);

        return redirect()->back()->with('success', __('Signature request email sent successfully.'));
    }

    /* ────────────────────────────────────────────────────────────────
     *  PUBLIC — Customer-facing signature request page
     * ──────────────────────────────────────────────────────────────── */
    public function signatureRequest(Request $request, string $business, string $verification)
    {
        $signatureRequest = RepairBuddySignatureRequest::query()
            ->with(['job.customer', 'job.jobDevices.customerDevice.device'])
            ->where('verification_code', $verification)
            ->firstOrFail();

        $tenant = Tenant::find($signatureRequest->tenant_id);
        if (! $tenant) {
            abort(404);
        }

        $job = $signatureRequest->job;
        $customer = $job->customer;

        // Check if already completed
        if ($signatureRequest->isCompleted()) {
            return view('tenant.signatures.already-signed', [
                'tenant'           => $tenant,
                'job'              => $job,
                'signatureRequest' => $signatureRequest,
            ]);
        }

        // Check if expired
        if ($signatureRequest->isExpired()) {
            return view('tenant.signatures.expired', [
                'tenant'           => $tenant,
                'job'              => $job,
                'signatureRequest' => $signatureRequest,
            ]);
        }

        // Check if job status still matches (for pickup/delivery types)
        $canSign = true;
        $statusMessage = '';
        $store = new TenantSettingsStore($tenant);
        $settings = $store->get('signature', []);

        if ($signatureRequest->signature_type === 'pickup') {
            $triggerStatus = $settings['pickup_trigger_status'] ?? '';
            if (! empty($triggerStatus) && $job->status_slug !== $triggerStatus) {
                $canSign = false;
                $statusMessage = __('Job status is different than allowed for this signature.');
            }
        }

        if ($signatureRequest->signature_type === 'delivery') {
            $triggerStatus = $settings['delivery_trigger_status'] ?? '';
            if (! empty($triggerStatus) && $job->status_slug !== $triggerStatus) {
                $canSign = false;
                $statusMessage = __('Job status is different than allowed for this signature.');
            }
        }

        return view('tenant.signatures.sign', [
            'tenant'           => $tenant,
            'job'              => $job,
            'customer'         => $customer,
            'signatureRequest' => $signatureRequest,
            'canSign'          => $canSign,
            'statusMessage'    => $statusMessage,
        ]);
    }

    /* ────────────────────────────────────────────────────────────────
     *  PUBLIC — Submit signature (AJAX)
     * ──────────────────────────────────────────────────────────────── */
    public function submitSignature(Request $request, string $business, string $verification)
    {
        $signatureRequest = RepairBuddySignatureRequest::query()
            ->with(['job.customer'])
            ->where('verification_code', $verification)
            ->firstOrFail();

        $tenant = Tenant::find($signatureRequest->tenant_id);
        if (! $tenant) {
            return response()->json(['success' => false, 'error' => 'Tenant not found.'], 404);
        }

        // Validate state
        if ($signatureRequest->isCompleted()) {
            return response()->json([
                'success' => false,
                'error'   => __('This signature has already been submitted.'),
            ]);
        }

        if ($signatureRequest->isExpired()) {
            return response()->json([
                'success' => false,
                'error'   => __('This signature link has expired.'),
            ]);
        }

        // Validate file upload
        $request->validate([
            'signature_file' => 'required|file|mimes:png,jpg,jpeg|max:2048',
        ]);

        // Store the signature file
        $file = $request->file('signature_file');
        $filename = 'signature_' . $signatureRequest->job_id . '_' . $signatureRequest->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs(
            "tenants/{$tenant->id}/signatures",
            $filename,
            'public'
        );

        $fileUrl = Storage::disk('public')->url($path);

        // Complete the signature
        $this->signatureService->completeSignature(
            signatureRequest: $signatureRequest,
            filePath: $fileUrl,
            ip: $request->ip() ?? '0.0.0.0',
            userAgent: $request->userAgent() ?? '',
        );

        // Notify admin
        $this->notifyAdminOfSignature($tenant, $signatureRequest);

        return response()->json([
            'success'  => true,
            'message'  => __('Signature submitted successfully!'),
            'data'     => [
                'redirect' => route('tenant.signature.success', [
                    'business'     => $tenant->slug,
                    'verification' => $verification,
                ]),
            ],
        ]);
    }

    /* ────────────────────────────────────────────────────────────────
     *  PUBLIC — Success page after signing
     * ──────────────────────────────────────────────────────────────── */
    public function success(Request $request, string $business, string $verification)
    {
        $signatureRequest = RepairBuddySignatureRequest::query()
            ->with(['job'])
            ->where('verification_code', $verification)
            ->firstOrFail();

        $tenant = Tenant::find($signatureRequest->tenant_id);

        return view('tenant.signatures.success', [
            'tenant'           => $tenant,
            'job'              => $signatureRequest->job,
            'signatureRequest' => $signatureRequest,
        ]);
    }

    /* ────────────────────────────────────────────────────────────────
     *  Helpers
     * ──────────────────────────────────────────────────────────────── */
    private function notifyAdminOfSignature(Tenant $tenant, RepairBuddySignatureRequest $signatureRequest): void
    {
        // Find tenant admin users and create event
        RepairBuddyEvent::create([
            'tenant_id'    => $tenant->id,
            'entity_type'  => 'job',
            'entity_id'    => $signatureRequest->job_id,
            'event_type'   => 'signature_admin_notified',
            'actor_id'     => null,
            'payload_json' => [
                'title'   => 'Signature submission notification',
                'message' => "Verified {$signatureRequest->signature_type} signature received for job #{$signatureRequest->job->case_number}.",
            ],
        ]);
    }
}
