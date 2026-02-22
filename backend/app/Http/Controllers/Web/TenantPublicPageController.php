<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class TenantPublicPageController extends Controller
{
    public function myaccount(Request $request, string $business)
    {
        return $this->placeholder('tenant.placeholders.myaccount', $business);
    }

    public function services(Request $request, string $business)
    {
        return $this->placeholder('tenant.placeholders.services', $business);
    }

    public function parts(Request $request, string $business)
    {
        return $this->placeholder('tenant.placeholders.parts', $business);
    }

    public function review(Request $request, string $business)
    {
        return $this->placeholder('tenant.placeholders.review', $business);
    }

    private function placeholder(string $view, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant) {
            abort(404);
        }

        return view($view, [
            'tenant'   => $tenant,
            'business' => $business,
        ]);
    }
}
