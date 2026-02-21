<div>
    <div x-data="{ show: @entangle('showModal') }"
         x-show="show"
         x-on:keydown.escape.window="show = false"
         class="rb-modal-backdrop"
         style="display: none;"
         x-transition:enter="fade-in"
         x-transition:leave="fade-out">
        
        <div class="rb-modal-container" 
             @click.away="show = false"
             x-transition:enter="slide-up"
             x-transition:leave="slide-down">
            
            <div class="rb-modal-header">
                <h5 class="mb-0 fw-bold"><i class="bi bi-person-plus me-2"></i>{{ __('Quick Add Customer') }}</h5>
                <button type="button" class="btn-close" @click="show = false"></button>
            </div>

            <form wire:submit.prevent="save">
                <div class="rb-modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('First Name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" wire:model.defer="first_name" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Last Name') }}</label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" wire:model.defer="last_name">
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Email Address') }} <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" wire:model.defer="email" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Phone Number') }}</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" wire:model.defer="phone">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Company') }}</label>
                            <input type="text" class="form-control @error('company') is-invalid @enderror" wire:model.defer="company">
                            @error('company') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="rb-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" @click="show = false">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <span wire:loading.remove wire:target="save">{{ __('Save Customer') }}</span>
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
