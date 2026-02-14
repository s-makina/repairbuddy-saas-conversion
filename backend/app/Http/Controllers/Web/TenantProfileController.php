<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyJob;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class TenantProfileController extends Controller
{
    public function edit(Request $request)
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();
        $user = $request->user();

        if (! $tenant instanceof Tenant || ! $tenantId) {
            abort(400, 'Tenant context is missing.');
        }

        if (! $user) {
            return redirect()->route('web.login');
        }

        if ((int) $user->tenant_id !== (int) $tenantId) {
            abort(403);
        }

        $countries = $this->countryOptions();
        $jobsCount = RepairBuddyJob::query()
            ->where('customer_id', $user->id)
            ->count();
        $estimatesCount = RepairBuddyEstimate::query()
            ->where('customer_id', $user->id)
            ->count();

        $memberSince = $user->created_at ? $user->created_at->format('M j, Y') : '';
        $userRole = is_string($user->role) && $user->role !== '' ? ucfirst($user->role) : 'Customer';

        return view('tenant.profile', [
            'tenant' => $tenant,
            'user' => $user,
            'activeNav' => 'profile',
            'pageTitle' => 'Profile',
            'countries' => $countries,
            '_jobs_count' => $jobsCount,
            '_estimates_count' => $estimatesCount,
            'lifetime_value_formatted' => '$0.00',
            'dateTime' => $memberSince,
            'userRole' => $userRole,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenantId = TenantContext::tenantId();
        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $tenant instanceof Tenant || ! $tenantId) {
            abort(400, 'Tenant context is missing.');
        }

        if (! $user) {
            return redirect()->route('web.login');
        }

        if ((int) $user->tenant_id !== (int) $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'company' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'max:255'],
            'address_postal_code' => ['nullable', 'string', 'max:64'],
            'address_country' => ['nullable', 'string', 'size:2'],
        ]);

        if ($validated['email'] !== $user->email) {
            $emailExists = \App\Models\User::query()
                ->where('email', $validated['email'])
                ->whereKeyNot($user->id)
                ->exists();

            if ($emailExists) {
                return back()
                    ->withErrors(['email' => 'This email is already in use.'])
                    ->withInput();
            }
        }

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'address_line1' => $validated['address_line1'] ?? null,
            'address_line2' => $validated['address_line2'] ?? null,
            'address_city' => $validated['address_city'] ?? null,
            'address_state' => $validated['address_state'] ?? null,
            'address_postal_code' => $validated['address_postal_code'] ?? null,
            'address_country' => $validated['address_country'] ?? null,
        ])->save();

        return redirect()
            ->route('tenant.profile.edit', ['business' => $tenant->slug])
            ->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $tenantId = TenantContext::tenantId();
        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $tenant instanceof Tenant || ! $tenantId) {
            abort(400, 'Tenant context is missing.');
        }

        if (! $user) {
            return redirect()->route('web.login');
        }

        if ((int) $user->tenant_id !== (int) $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($validated['current_password'], (string) $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect.'])
                ->withInput();
        }

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
        ])->save();

        return redirect()
            ->route('tenant.profile.edit', ['business' => $tenant->slug])
            ->with('status', 'Password updated.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $tenantId = TenantContext::tenantId();
        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $tenant instanceof Tenant || ! $tenantId) {
            abort(400, 'Tenant context is missing.');
        }

        if (! $user) {
            return redirect()->route('web.login');
        }

        if ((int) $user->tenant_id !== (int) $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'profile_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
        ]);

        $file = $validated['profile_photo'];

        $path = $file->storePublicly('avatars', ['disk' => 'public']);

        if (is_string($user->avatar_path) && $user->avatar_path !== '' && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill([
            'avatar_path' => $path,
        ])->save();

        return redirect()
            ->route('tenant.profile.edit', ['business' => $tenant->slug])
            ->with('status', 'Profile photo updated.');
    }

    private function countryOptions(): array
    {
        return [
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BR' => 'Brazil',
            'BN' => 'Brunei Darussalam',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CD' => 'Congo, the Democratic Republic of the',
            'CR' => 'Costa Rica',
            'CI' => "Cote D'Ivoire",
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GF' => 'French Guiana',
            'PF' => 'French Polynesia',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GN' => 'Guinea',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran, Islamic Republic of',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KR' => 'Korea, Republic of',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => "Lao People's Democratic Republic",
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MK' => 'Macedonia, the Former Yugoslav Republic of',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'MX' => 'Mexico',
            'MD' => 'Moldova, Republic of',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RO' => 'Romania',
            'RU' => 'Russian Federation',
            'SA' => 'Saudi Arabia',
            'RS' => 'Serbia',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'ZA' => 'South Africa',
            'ES' => 'Spain',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'TW' => 'Taiwan, Province of China',
            'TH' => 'Thailand',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VE' => 'Venezuela',
            'VN' => 'Viet Nam',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        ];
    }
}
