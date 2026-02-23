<?php

namespace App\Livewire\Tenant\Settings;

use Livewire\Component;

class SectionPlaceholder extends Component
{
    public $tenant;
    public string $sectionKey = '';
    public string $sectionLabel = '';

    public function mount($tenant, string $sectionKey = '', string $sectionLabel = ''): void
    {
        $this->tenant = $tenant;
        $this->sectionKey = $sectionKey;
        $this->sectionLabel = $sectionLabel;
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.placeholder');
    }
}
