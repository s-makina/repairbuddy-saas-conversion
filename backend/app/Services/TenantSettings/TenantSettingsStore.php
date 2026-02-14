<?php

namespace App\Services\TenantSettings;

use App\Models\Tenant;
use Illuminate\Support\Arr;

class TenantSettingsStore
{
    public function __construct(
        private Tenant $tenant,
        private string $rootKey = 'repairbuddy_settings',
    ) {
    }

    public function get(string $path, mixed $default = null): mixed
    {
        $state = $this->stateArray();

        return data_get($state, $this->normalizePath($path), $default);
    }

    public function set(string $path, mixed $value): void
    {
        $state = $this->stateArray();
        data_set($state, $this->normalizePath($path), $value);
        $this->writeState($state);
    }

    public function merge(string $path, array $value): void
    {
        $existing = $this->get($path, []);
        if (! is_array($existing)) {
            $existing = [];
        }

        $this->set($path, array_replace_recursive($existing, $value));
    }

    public function save(): void
    {
        $this->tenant->save();
    }

    public function tenant(): Tenant
    {
        return $this->tenant;
    }

    private function stateArray(): array
    {
        $setupState = $this->tenant->setup_state;
        if (! is_array($setupState)) {
            $setupState = [];
        }

        $settings = $setupState[$this->rootKey] ?? [];
        if (! is_array($settings)) {
            $settings = [];
        }

        $setupState[$this->rootKey] = $settings;

        return $setupState;
    }

    private function writeState(array $setupState): void
    {
        $this->tenant->forceFill([
            'setup_state' => $setupState,
        ]);
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return $this->rootKey;
        }

        if (str_starts_with($path, $this->rootKey.'.')) {
            return $path;
        }

        return $this->rootKey.'.'.$path;
    }
}
