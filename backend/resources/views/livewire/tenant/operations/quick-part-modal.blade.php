<div>
    <div x-data="{ show: @entangle('showModal').live }"
         x-show="show"
         x-on:keydown.escape.window="show = false"
         @close-Part-modal.window="show = false"
         class="rb-modal-backdrop"
         style="display: none;">
        
        <div class="rb-modal-container" 
             @click.away="show = false">
            
            <div class="rb-modal-header">
                <h5 class="mb-0 fw-bold"><i class="bi bi-Box-plus me-2"></i>{{ __('Quick Add Part') }}</h5>
                <button type="button" class="btn-close" @click="show = false"></button>
            </div>

            <form wire:submit.prevent="save">
                <div class="rb-modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">{{ __('Part Name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model.defer="name" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('SKU / Serial No.') }}</label>
                            <input type="text" class="form-control @error('sku') is-invalid @enderror" wire:model.defer="sku">
                            @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('Price Amount') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ \App\Support\TenantContext::tenant()?->currency ?? 'USD' }}</span>
                                <input type="number" step="0.01" class="form-control @error('price_amount') is-invalid @enderror" wire:model.defer="price_amount" required>
                            </div>
                            @error('price_amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('Part Type') }}</label>
                            <select class="form-select @error('part_type_id') is-invalid @enderror" wire:model.defer="part_type_id">
                                <option value="">{{ __('-- Select Type --') }}</option>
                                @foreach($types as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            @error('part_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('Part Brand') }}</label>
                            <select class="form-select @error('part_brand_id') is-invalid @enderror" wire:model.defer="part_brand_id">
                                <option value="">{{ __('-- Select Brand --') }}</option>
                                @foreach($brands as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('part_brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('Initial Stock') }}</label>
                            <input type="number" step="1" min="0" class="form-control @error('stock') is-invalid @enderror" wire:model.defer="stock">
                            @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="rb-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" @click="show = false">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <span wire:loading.remove wire:target="save">{{ __('Save Part') }}</span>
                        <span wire:loading wire:target="save">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            {{ __('Saving...') }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .fade-in { opacity: 1; transition: opacity 0.2s ease-out; }
        .fade-out { opacity: 0; transition: opacity 0.2s ease-in; }
        .slide-up { transform: translateY(0); opacity: 1; transition: all 0.3s ease-out; }
        .slide-down { transform: translateY(20px); opacity: 0; transition: all 0.3s ease-in; }
    </style>
</div>
