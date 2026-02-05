<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyPartBrand;
use App\Models\RepairBuddyPartType;
use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RepairBuddyCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasTable('branches')) {
            return;
        }

        if (! Schema::hasTable('rb_device_types')
            || ! Schema::hasTable('rb_device_brands')
            || ! Schema::hasTable('rb_devices')
            || ! Schema::hasTable('rb_part_types')
            || ! Schema::hasTable('rb_part_brands')
            || ! Schema::hasTable('rb_parts')) {
            return;
        }

        $tenant = Tenant::query()->where('slug', 'demo')->first();
        if (! $tenant) {
            return;
        }

        $downloadImages = filter_var(env('SEED_REPAIRBUDDY_IMAGES', false), FILTER_VALIDATE_BOOL);

        TenantContext::set($tenant);

        $branch = $tenant->defaultBranch;
        if (! $branch) {
            $branch = Branch::query()->firstOrCreate([
                'name' => 'Main Branch',
            ], [
                'code' => 'MAIN',
                'phone' => null,
                'email' => null,
                'address_line1' => 'Demo Address',
                'address_line2' => null,
                'address_city' => 'Cairo',
                'address_state' => null,
                'address_postal_code' => null,
                'address_country' => 'EG',
                'is_active' => true,
            ]);

            $tenant->forceFill([
                'default_branch_id' => $branch->id,
            ])->save();
        }

        BranchContext::set($branch);

        try {
            $deviceTypes = [
                [
                    'name' => 'Phone',
                    'description' => 'Smartphones and feature phones.',
                    'image_key' => 'smartphone',
                ],
                [
                    'name' => 'Tablet',
                    'description' => 'Tablets and iPads.',
                    'image_key' => 'tablet',
                ],
                [
                    'name' => 'Laptop',
                    'description' => 'Laptops and notebooks.',
                    'image_key' => 'laptop',
                ],
                [
                    'name' => 'Desktop',
                    'description' => 'Desktop PCs and workstations.',
                    'image_key' => 'monitor',
                ],
                [
                    'name' => 'Game Console',
                    'description' => 'Consoles (PlayStation, Xbox, Nintendo).',
                    'image_key' => 'gamepad-2',
                ],
            ];

            $deviceTypeModels = [];
            foreach ($deviceTypes as $t) {
                $imagePath = null;
                if ($downloadImages) {
                    $imagePath = $this->downloadImageIfMissing(
                        url: $this->lucideIconUrl((string) $t['image_key']),
                        diskPath: 'seed/device-types/'.Str::slug((string) $t['image_key']).'.svg'
                    );

                    if (! $imagePath) {
                        $imagePath = $this->downloadImageIfMissing(
                            url: $this->placeholderImageUrl($t['name']),
                            diskPath: 'seed/device-types/'.Str::slug((string) $t['image_key']).'.png'
                        );
                    }
                }

                $deviceTypeModels[$t['name']] = RepairBuddyDeviceType::query()->updateOrCreate([
                    'name' => $t['name'],
                ], [
                    'description' => $t['description'],
                    'image_path' => $imagePath,
                    'is_active' => true,
                ]);
            }

            $deviceBrands = [
                ['name' => 'Apple', 'icon' => 'apple'],
                ['name' => 'Samsung', 'icon' => 'samsung'],
                ['name' => 'Google', 'icon' => 'google'],
                ['name' => 'Xiaomi', 'icon' => 'xiaomi'],
                ['name' => 'Huawei', 'icon' => 'huawei'],
                ['name' => 'Dell', 'icon' => 'dell'],
                ['name' => 'HP', 'icon' => 'hp'],
                ['name' => 'Lenovo', 'icon' => 'lenovo'],
                ['name' => 'Asus', 'icon' => 'asus'],
                ['name' => 'Acer', 'icon' => 'acer'],
                ['name' => 'Sony', 'icon' => 'sony'],
                ['name' => 'Microsoft', 'icon' => 'microsoft'],
                ['name' => 'Nintendo', 'icon' => 'nintendo'],
            ];

            $deviceBrandModels = [];
            foreach ($deviceBrands as $b) {
                $brandName = (string) ($b['name'] ?? '');
                if ($brandName === '') {
                    continue;
                }

                $imagePath = null;
                if ($downloadImages) {
                    $imagePath = $this->downloadImageIfMissing(
                        url: $this->simpleIconUrl((string) ($b['icon'] ?? '')),
                        diskPath: 'seed/device-brands/'.Str::slug($brandName).'.svg'
                    );

                    if (! $imagePath) {
                        $imagePath = $this->downloadImageIfMissing(
                            url: $this->placeholderImageUrl($brandName),
                            diskPath: 'seed/device-brands/'.Str::slug($brandName).'.png'
                        );
                    }
                }

                $deviceBrandModels[$brandName] = RepairBuddyDeviceBrand::query()->updateOrCreate([
                    'name' => $brandName,
                ], [
                    'image_path' => $imagePath,
                    'is_active' => true,
                ]);
            }

            $devices = [
                ['type' => 'Phone', 'brand' => 'Apple', 'model' => 'iPhone 13'],
                ['type' => 'Phone', 'brand' => 'Apple', 'model' => 'iPhone 14 Pro'],
                ['type' => 'Phone', 'brand' => 'Samsung', 'model' => 'Galaxy S23'],
                ['type' => 'Phone', 'brand' => 'Google', 'model' => 'Pixel 8'],
                ['type' => 'Tablet', 'brand' => 'Apple', 'model' => 'iPad Air'],
                ['type' => 'Tablet', 'brand' => 'Samsung', 'model' => 'Galaxy Tab S9'],
                ['type' => 'Laptop', 'brand' => 'Dell', 'model' => 'XPS 13'],
                ['type' => 'Laptop', 'brand' => 'Lenovo', 'model' => 'ThinkPad X1 Carbon'],
                ['type' => 'Laptop', 'brand' => 'HP', 'model' => 'Spectre x360'],
                ['type' => 'Desktop', 'brand' => 'Asus', 'model' => 'ROG Desktop'],
                ['type' => 'Game Console', 'brand' => 'Sony', 'model' => 'PlayStation 5'],
                ['type' => 'Game Console', 'brand' => 'Microsoft', 'model' => 'Xbox Series X'],
                ['type' => 'Game Console', 'brand' => 'Nintendo', 'model' => 'Switch'],
            ];

            foreach ($devices as $d) {
                $type = $deviceTypeModels[$d['type']] ?? null;
                $brand = $deviceBrandModels[$d['brand']] ?? null;
                if (! $type || ! $brand) {
                    continue;
                }

                RepairBuddyDevice::query()->updateOrCreate([
                    'device_type_id' => $type->id,
                    'device_brand_id' => $brand->id,
                    'model' => $d['model'],
                ], [
                    'is_active' => true,
                    'disable_in_booking_form' => false,
                    'is_other' => false,
                    'parent_device_id' => null,
                ]);
            }

            $partTypes = [
                [
                    'name' => 'Screen',
                    'description' => 'Displays and touch panels.',
                ],
                [
                    'name' => 'Battery',
                    'description' => 'Replacement batteries.',
                ],
                [
                    'name' => 'Charging Port',
                    'description' => 'USB/Lightning ports and flex cables.',
                ],
                [
                    'name' => 'Keyboard',
                    'description' => 'Laptop keyboards and top cases.',
                ],
                [
                    'name' => 'SSD',
                    'description' => 'Solid state drives.',
                ],
            ];

            $partTypeModels = [];
            foreach ($partTypes as $t) {
                $imagePath = null;
                if ($downloadImages) {
                    $imagePath = $this->downloadImageIfMissing(
                        url: $this->lucideIconUrl(match ((string) ($t['name'] ?? '')) {
                            'Battery' => 'battery',
                            'Charging Port' => 'plug',
                            'Keyboard' => 'keyboard',
                            'SSD' => 'hard-drive',
                            default => 'square',
                        }),
                        diskPath: 'seed/part-types/'.Str::slug((string) $t['name']).'.svg'
                    );

                    if (! $imagePath) {
                        $imagePath = $this->downloadImageIfMissing(
                            url: $this->placeholderImageUrl($t['name']),
                            diskPath: 'seed/part-types/'.Str::slug($t['name']).'.png'
                        );
                    }
                }

                $partTypeModels[$t['name']] = RepairBuddyPartType::query()->updateOrCreate([
                    'name' => $t['name'],
                ], [
                    'description' => $t['description'],
                    'image_path' => $imagePath,
                    'is_active' => true,
                ]);
            }

            $partBrands = [
                [
                    'name' => 'OEM',
                    'description' => 'Original-equivalent parts.',
                    'icon_source' => 'lucide',
                    'icon' => 'badge-check',
                ],
                [
                    'name' => 'Aftermarket',
                    'description' => 'Third-party compatible parts.',
                    'icon_source' => 'lucide',
                    'icon' => 'package',
                ],
                [
                    'name' => 'iFixit',
                    'description' => 'Repair-friendly parts and tools.',
                    'icon_source' => 'simpleicons',
                    'icon' => 'ifixit',
                ],
            ];

            $partBrandModels = [];
            foreach ($partBrands as $b) {
                $imagePath = null;
                if ($downloadImages) {
                    $url = null;
                    if (($b['icon_source'] ?? null) === 'simpleicons') {
                        $url = $this->simpleIconUrl((string) ($b['icon'] ?? ''));
                    }
                    if (($b['icon_source'] ?? null) === 'lucide') {
                        $url = $this->lucideIconUrl((string) ($b['icon'] ?? ''));
                    }

                    $imagePath = $this->downloadImageIfMissing(
                        url: $url ?: $this->placeholderImageUrl($b['name']),
                        diskPath: 'seed/part-brands/'.Str::slug($b['name']).($url ? '.svg' : '.png')
                    );

                    if (! $imagePath) {
                        $imagePath = $this->downloadImageIfMissing(
                            url: $this->placeholderImageUrl($b['name']),
                            diskPath: 'seed/part-brands/'.Str::slug($b['name']).'.png'
                        );
                    }
                }

                $partBrandModels[$b['name']] = RepairBuddyPartBrand::query()->updateOrCreate([
                    'name' => $b['name'],
                ], [
                    'description' => $b['description'],
                    'image_path' => $imagePath,
                    'is_active' => true,
                ]);
            }

            $parts = [
                [
                    'name' => 'iPhone 13 Screen (OEM)',
                    'type' => 'Screen',
                    'brand' => 'OEM',
                    'sku' => 'IPH13-SCR-OEM',
                    'price_amount_cents' => 14900,
                    'price_currency' => 'USD',
                    'stock' => 5,
                ],
                [
                    'name' => 'iPhone 13 Battery (OEM)',
                    'type' => 'Battery',
                    'brand' => 'OEM',
                    'sku' => 'IPH13-BAT-OEM',
                    'price_amount_cents' => 4900,
                    'price_currency' => 'USD',
                    'stock' => 10,
                ],
                [
                    'name' => 'USB-C Charging Port (Aftermarket)',
                    'type' => 'Charging Port',
                    'brand' => 'Aftermarket',
                    'sku' => 'USBC-PORT-AM',
                    'price_amount_cents' => 1900,
                    'price_currency' => 'USD',
                    'stock' => 25,
                ],
                [
                    'name' => 'Laptop Keyboard (Aftermarket)',
                    'type' => 'Keyboard',
                    'brand' => 'Aftermarket',
                    'sku' => 'LAP-KB-AM',
                    'price_amount_cents' => 3900,
                    'price_currency' => 'USD',
                    'stock' => 8,
                ],
                [
                    'name' => '1TB NVMe SSD',
                    'type' => 'SSD',
                    'brand' => 'iFixit',
                    'sku' => 'NVME-1TB-IFX',
                    'price_amount_cents' => 7900,
                    'price_currency' => 'USD',
                    'stock' => 12,
                ],
            ];

            foreach ($parts as $p) {
                $type = $partTypeModels[$p['type']] ?? null;
                $brand = $partBrandModels[$p['brand']] ?? null;

                RepairBuddyPart::query()->updateOrCreate([
                    'sku' => $p['sku'],
                ], [
                    'part_type_id' => $type?->id,
                    'part_brand_id' => $brand?->id,
                    'name' => $p['name'],
                    'manufacturing_code' => null,
                    'stock_code' => null,
                    'price_amount_cents' => $p['price_amount_cents'],
                    'price_currency' => $p['price_currency'],
                    'tax_id' => null,
                    'warranty' => '30 days',
                    'core_features' => null,
                    'capacity' => null,
                    'installation_charges_amount_cents' => null,
                    'installation_charges_currency' => null,
                    'installation_message' => null,
                    'stock' => $p['stock'],
                    'is_active' => true,
                ]);
            }
        } finally {
            BranchContext::set(null);
            TenantContext::set(null);
        }
    }

    private function placeholderImageUrl(string $label): string
    {
        $safe = trim($label) === '' ? 'Image' : $label;
        $encoded = rawurlencode($safe);

        return "https://placehold.co/256x256/png?text={$encoded}";
    }

    private function simpleIconUrl(string $slug): string
    {
        $s = Str::of($slug)->trim()->lower()->toString();
        if ($s === '') {
            return '';
        }

        return "https://cdn.simpleicons.org/{$s}";
    }

    private function lucideIconUrl(string $name): string
    {
        $icon = Str::of($name)->trim()->lower()->toString();
        if ($icon === '') {
            return '';
        }

        // lucide-static is a simple CDN source for SVG assets.
        return "https://unpkg.com/lucide-static@0.563.0/icons/{$icon}.svg";
    }

    private function downloadImageIfMissing(string $url, string $diskPath): ?string
    {
        if ($url === '') {
            return null;
        }

        if ($diskPath === '') {
            return null;
        }

        if (Storage::disk('public')->exists($diskPath)) {
            return $diskPath;
        }

        try {
            $res = Http::timeout(15)->retry(2, 250)->get($url);
            if (! $res->successful()) {
                return null;
            }

            Storage::disk('public')->put($diskPath, $res->body());

            return $diskPath;
        } catch (\Throwable) {
            return null;
        }
    }
}
