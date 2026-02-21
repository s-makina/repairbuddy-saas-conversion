<div>
    <div x-data="{ show: @entangle('showModal').live }"
         x-show="show"
         x-on:keydown.escape.window="show = false"
         @close-part-modal.window="show = false"
         class="rb-modal-backdrop"
         style="display: none;">
        
        <div class="rb-modal-container" 
             @click.away="show = false"
             style="max-width: 680px;">
            
            <div class="rb-modal-header">
                <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2"></i>{{ __('Quick Add Part') }}</h5>
                <button type="button" class="btn-close" @click="show = false"></button>
            </div>

            <form wire:submit.prevent="save">
                <div class="rb-modal-body">
                    <div class="row g-3">

                        {{-- Name * --}}
                        <div class="col-12">
                            <label class="form-label" for="qp_name">{{ __('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" id="qp_name" class="form-control @error('name') is-invalid @enderror" wire:model.defer="name" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Brand --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qp_brand">{{ __('Brand') }}</label>
                            <select id="qp_brand" class="form-select @error('part_brand_id') is-invalid @enderror" wire:model.defer="part_brand_id">
                                <option value="">{{ __('None') }}</option>
                                @foreach($brands as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('part_brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Type --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qp_type">{{ __('Type') }}</label>
                            <select id="qp_type" class="form-select @error('part_type_id') is-invalid @enderror" wire:model.defer="part_type_id">
                                <option value="">{{ __('None') }}</option>
                                @foreach($types as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            @error('part_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Manufacturing Code * --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qp_mfg">{{ __('Manufacturing Code') }} <span class="text-danger">*</span></label>
                            <input type="text" id="qp_mfg" class="form-control @error('manufacturing_code') is-invalid @enderror" wire:model.defer="manufacturing_code" required>
                            @error('manufacturing_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Stock Code --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qp_stock_code">{{ __('Stock Code') }}</label>
                            <input type="text" id="qp_stock_code" class="form-control @error('stock_code') is-invalid @enderror" wire:model.defer="stock_code">
                            @error('stock_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- SKU --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qp_sku">{{ __('SKU') }}</label>
                            <input type="text" id="qp_sku" class="form-control @error('sku') is-invalid @enderror" wire:model.defer="sku">
                            @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Price * --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qp_price">{{ __('Price') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="any" id="qp_price" class="form-control @error('price_amount') is-invalid @enderror" wire:model.defer="price_amount" required>
                                <span class="input-group-text">{{ $tenantCurrency ?? '' }}</span>
                            </div>
                            @error('price_amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- Warranty --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qp_warranty">{{ __('Warranty') }}</label>
                            <input type="text" id="qp_warranty" class="form-control @error('warranty') is-invalid @enderror" wire:model.defer="warranty">
                            @error('warranty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Capacity --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qp_capacity">{{ __('Capacity') }}</label>
                            <input type="text" id="qp_capacity" class="form-control @error('capacity') is-invalid @enderror" wire:model.defer="capacity">
                            @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Core Features --}}
                        <div class="col-12">
                            <label class="form-label" for="qp_features">{{ __('Core Features') }}</label>
                            <textarea id="qp_features" rows="3" class="form-control @error('core_features') is-invalid @enderror" wire:model.defer="core_features"></textarea>
                            @error('core_features') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Installation Charges --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qp_install_charges">{{ __('Installation Charges') }}</label>
                            <div class="input-group">
                                <input type="number" step="any" id="qp_install_charges" class="form-control @error('installation_charges') is-invalid @enderror" wire:model.defer="installation_charges">
                                <span class="input-group-text">{{ $tenantCurrency ?? '' }}</span>
                            </div>
                            @error('installation_charges') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- Installation Message --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qp_install_msg">{{ __('Installation Message') }}</label>
                            <input type="text" id="qp_install_msg" class="form-control @error('installation_message') is-invalid @enderror" wire:model.defer="installation_message">
                            @error('installation_message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Stock --}}
                        <div class="col-md-6">
                            <label class="form-label" for="qp_stock">{{ __('Stock') }}</label>
                            <input type="number" step="1" min="0" id="qp_stock" class="form-control @error('stock') is-invalid @enderror" wire:model.defer="stock">
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
</div>
