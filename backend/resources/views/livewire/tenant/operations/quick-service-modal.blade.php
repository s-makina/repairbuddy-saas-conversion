<div>
    <div x-data="{ show: @entangle('showModal').live }"
         x-show="show"
         x-bind:style="show ? 'display: flex !important' : ''"
         x-cloak
         x-on:keydown.escape.window="show = false"
         @close-service-modal.window="show = false"
         class="rb-modal-backdrop">
        
        <div class="rb-modal-container" 
             @click.away="show = false"
             style="max-width: 680px;">
            
            <div class="rb-modal-header">
                <h5 class="mb-0 fw-bold"><i class="bi bi-wrench-adjustable me-2"></i>{{ __('Quick Add Service') }}</h5>
                <button type="button" class="btn-close" @click="show = false"></button>
            </div>

            <form wire:submit.prevent="save">
                <div class="rb-modal-body">
                    <div class="row g-3">

                        {{-- Name * --}}
                        <div class="col-12">
                            <label class="form-label" for="qs_name">{{ __('Service Name') }} <span class="text-danger">*</span></label>
                            <input type="text" id="qs_name" class="form-control @error('name') is-invalid @enderror" wire:model.defer="name" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Service Type (Searchable) --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qs_type">{{ __('Service Type') }}</label>
                            <div class="search-select-container" 
                                 data-options='@json($types->map(fn($t) => ["id" => $t->id, "name" => $t->name]))'
                                 x-data="{ 
                                    open: false, 
                                    search: '', 
                                    selectedId: @entangle('service_type_id'),
                                    options: [],
                                    init() {
                                        this.options = JSON.parse($el.dataset.options || '[]');
                                    },
                                    get selectedName() {
                                        let item = this.options.find(o => o.id == this.selectedId);
                                        return item ? item.name : '{{ __('None') }}';
                                    },
                                    get filteredOptions() {
                                        if (!this.search) return this.options;
                                        return this.options.filter(o => o.name.toLowerCase().includes(this.search.toLowerCase()));
                                    },
                                    select(id) {
                                        this.selectedId = id;
                                        this.open = false;
                                        this.search = '';
                                    }
                                 }" @click.away="open = false; search = ''">
                                
                                <div class="input-group">
                                    <input type="text" class="form-control" 
                                           :placeholder="selectedName"
                                           x-model="search"
                                           @focus="open = true"
                                           @keydown.escape="open = false; search = ''"
                                           style="background-image: none !important;">
                                    <button type="button" class="btn btn-outline-secondary" @click="open = !open">
                                        <i class="bi" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                    </button>
                                </div>

                                <div class="search-dropdown" x-show="open" x-cloak style="width: 100%; border-color: var(--rb-primary);">
                                    <div class="search-item" @click="select(null)">
                                        <span class="item-title text-muted fst-italic">{{ __('None') }}</span>
                                    </div>
                                    <template x-for="opt in filteredOptions" :key="opt.id">
                                        <div class="search-item" @click="select(opt.id)" :class="{'bg-light': selectedId == opt.id}">
                                            <span class="item-title" x-text="opt.name"></span>
                                            <i class="bi bi-check text-primary" x-show="selectedId == opt.id"></i>
                                        </div>
                                    </template>
                                    <div class="p-3 text-center text-muted small" x-show="filteredOptions.length === 0">
                                        {{ __('No results found') }}
                                    </div>
                                </div>
                            </div>
                            @error('service_type_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- Service Code --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qs_code">{{ __('Service Code') }}</label>
                            <input type="text" id="qs_code" class="form-control @error('service_code') is-invalid @enderror" wire:model.defer="service_code">
                            @error('service_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Base Price * --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qs_price">{{ __('Base Price') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="any" id="qs_price" class="form-control @error('base_price') is-invalid @enderror" wire:model.defer="base_price" required>
                                <span class="input-group-text">{{ $tenantCurrency ?? '' }}</span>
                            </div>
                            @error('base_price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- Tax --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qs_tax">{{ __('Tax Rate') }}</label>
                            <select id="qs_tax" class="form-select @error('tax_id') is-invalid @enderror" wire:model.defer="tax_id">
                                <option value="">{{ __('Select Tax...') }}</option>
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax->id }}">{{ $tax->name }} ({{ rtrim(rtrim($tax->rate, '0'), '.') }}%)</option>
                                @endforeach
                            </select>
                            @error('tax_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Warranty --}}
                        <div class="col-md-12">
                            <label class="form-label" for="qs_warranty">{{ __('Warranty') }}</label>
                            <input type="text" id="qs_warranty" class="form-control @error('warranty') is-invalid @enderror" wire:model.defer="warranty">
                            @error('warranty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Description --}}
                        <div class="col-12">
                            <label class="form-label" for="qs_description">{{ __('Description') }}</label>
                            <textarea id="qs_description" rows="3" class="form-control @error('description') is-invalid @enderror" wire:model.defer="description"></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                </div>

                <div class="rb-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" @click="show = false">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <span wire:loading.remove wire:target="save">{{ __('Save Service') }}</span>
                        <span wire:loading wire:target="save">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            {{ __('Saving...') }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
