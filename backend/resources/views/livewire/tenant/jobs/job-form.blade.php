<div>
    <form wire:submit.prevent="save" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4" id="section-job-details">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Job Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Case Number') }}</label>
                                <input type="text" class="form-control" wire:model.defer="case_number" />
                                @error('case_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Title') }}</label>
                                <input type="text" class="form-control" wire:model.defer="title" />
                                @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
                                <select id="customer_id" class="form-select" wire:model.defer="customer_id">
                                    <option value="">{{ __('Select...') }}</option>
                                    @foreach ($customers ?? [] as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="technician_ids">{{ __('Technicians') }}</label>
                                <select id="technician_ids" class="form-select" multiple wire:model.defer="technician_ids">
                                    @foreach ($technicians ?? [] as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                @error('technician_ids')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Pickup Date') }}</label>
                                <input type="date" class="form-control" wire:model.defer="pickup_date" />
                                @error('pickup_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Delivery Date') }}</label>
                                <input type="date" class="form-control" wire:model.defer="delivery_date" />
                                @error('delivery_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Next Service Date') }}</label>
                                <input type="date" class="form-control" wire:model.defer="next_service_date" />
                                @error('next_service_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('Job Details') }}</label>
                                <textarea class="form-control" rows="4" wire:model.defer="case_detail"></textarea>
                                @error('case_detail')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4" id="section-devices">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Devices') }}</h5>
                        <button type="button" class="btn btn-success btn-sm" wire:click="addDevice">{{ __('Add Device') }}</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Device') }}</th>
                                        <th style="width:180px">{{ __('Device ID/IMEI') }}</th>
                                        @if ($enablePinCodeField)
                                            <th style="width:180px">{{ __('Pin Code/Password') }}</th>
                                        @endif
                                        <th>{{ __('Device Note') }}</th>
                                        <th style="width:120px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($deviceRows) === 0)
                                        <tr>
                                            <td colspan="{{ $enablePinCodeField ? 5 : 4 }}" class="text-center text-muted py-4">{{ __('No devices added yet.') }}</td>
                                        </tr>
                                    @endif

                                    @foreach ($deviceRows as $i => $row)
                                        <tr>
                                            <td>
                                                <select class="form-select form-select-sm" wire:model.defer="deviceRows.{{ $i }}.customer_device_id">
                                                    <option value="">{{ __('Select...') }}</option>
                                                    @foreach ($customerDevices ?? [] as $cd)
                                                        <option value="{{ $cd->id }}">{{ $cd->label ?? ('#'.$cd->id) }}</option>
                                                    @endforeach
                                                </select>
                                                @error('deviceRows.' . $i . '.customer_device_id')<div class="text-danger small">{{ $message }}</div>@enderror
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" wire:model.defer="deviceRows.{{ $i }}.serial" />
                                                @error('deviceRows.' . $i . '.serial')<div class="text-danger small">{{ $message }}</div>@enderror
                                            </td>
                                            @if ($enablePinCodeField)
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" wire:model.defer="deviceRows.{{ $i }}.pin" />
                                                    @error('deviceRows.' . $i . '.pin')<div class="text-danger small">{{ $message }}</div>@enderror
                                                </td>
                                            @endif
                                            <td>
                                                <input type="text" class="form-control form-control-sm" wire:model.defer="deviceRows.{{ $i }}.notes" />
                                                @error('deviceRows.' . $i . '.notes')<div class="text-danger small">{{ $message }}</div>@enderror
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeDevice({{ $i }})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4" id="section-extras">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Extra Fields & Files') }}</h5>
                        <button type="button" class="btn btn-success btn-sm" wire:click="addExtra">{{ __('Add Field') }}</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:160px">{{ __('Date') }}</th>
                                        <th style="width:220px">{{ __('Label') }}</th>
                                        <th>{{ __('Data') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th style="width:140px">{{ __('Visibility') }}</th>
                                        <th style="width:220px">{{ __('File') }}</th>
                                        <th style="width:120px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($extras) === 0)
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">{{ __('No extra fields added yet.') }}</td>
                                        </tr>
                                    @endif

                                    @foreach ($extras as $i => $row)
                                        <tr>
                                            <td>
                                                <input type="date" class="form-control form-control-sm" wire:model.defer="extras.{{ $i }}.occurred_at" />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" wire:model.defer="extras.{{ $i }}.label" />
                                                @error('extras.' . $i . '.label')<div class="text-danger small">{{ $message }}</div>@enderror
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" wire:model.defer="extras.{{ $i }}.data_text" />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" wire:model.defer="extras.{{ $i }}.description" />
                                                @error('extras.' . $i . '.description')<div class="text-danger small">{{ $message }}</div>@enderror
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm" wire:model.defer="extras.{{ $i }}.visibility">
                                                    <option value="public">{{ __('Customer & Staff') }}</option>
                                                    <option value="private">{{ __('Staff') }}</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="file" class="form-control form-control-sm" wire:model="extra_item_files.{{ $i }}" />
                                                @error('extra_item_files.' . $i)<div class="text-danger small">{{ $message }}</div>@enderror
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeExtra({{ $i }})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Parts Section --}}
                <div class="card mb-4" id="section-parts">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Select Parts') }}</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addPart">
                            <i class="bi bi-plus-circle me-1"></i>{{ __('Add Part') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th style="width:110px">{{ __('Code') }}</th>
                                        <th style="width:90px" class="text-end">{{ __('Qty') }}</th>
                                        <th style="width:130px" class="text-end">{{ __('Price (cents)') }}</th>
                                        <th style="width:90px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $partsItems = array_filter($items, fn($r) => ($r['type'] ?? '') === 'part'); @endphp
                                    @if (count($partsItems) === 0)
                                        <tr><td colspan="5" class="text-center text-muted py-3">{{ __('No parts selected yet.') }}</td></tr>
                                    @endif
                                    @foreach ($items as $i => $row)
                                        @if (($row['type'] ?? '') === 'part')
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm" wire:model.defer="items.{{ $i }}.name" /></td>
                                            <td><input type="text" class="form-control form-control-sm" wire:model.defer="items.{{ $i }}.code" /></td>
                                            <td><input type="number" min="1" class="form-control form-control-sm text-end" wire:model.defer="items.{{ $i }}.qty" /></td>
                                            <td><input type="number" class="form-control form-control-sm text-end" wire:model.defer="items.{{ $i }}.unit_price_cents" /></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeItem({{ $i }})"><i class="bi bi-trash"></i></button>
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Services Section --}}
                <div class="card mb-4" id="section-services">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Select Services') }}</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addService">
                            <i class="bi bi-wrench-adjustable-circle me-1"></i>{{ __('Add Service') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th style="width:140px">{{ __('Service Code') }}</th>
                                        <th style="width:90px" class="text-end">{{ __('Qty') }}</th>
                                        <th style="width:130px" class="text-end">{{ __('Price (cents)') }}</th>
                                        <th style="width:90px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $servicesItems = array_filter($items, fn($r) => ($r['type'] ?? '') === 'service'); @endphp
                                    @if (count($servicesItems) === 0)
                                        <tr><td colspan="5" class="text-center text-muted py-3">{{ __('No services selected yet.') }}</td></tr>
                                    @endif
                                    @foreach ($items as $i => $row)
                                        @if (($row['type'] ?? '') === 'service')
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm" wire:model.defer="items.{{ $i }}.name" /></td>
                                            <td><input type="text" class="form-control form-control-sm" wire:model.defer="items.{{ $i }}.code" /></td>
                                            <td><input type="number" min="1" class="form-control form-control-sm text-end" wire:model.defer="items.{{ $i }}.qty" /></td>
                                            <td><input type="number" class="form-control form-control-sm text-end" wire:model.defer="items.{{ $i }}.unit_price_cents" /></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeItem({{ $i }})"><i class="bi bi-trash"></i></button>
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Other Items Section --}}
                <div class="card mb-4" id="section-other-items">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Other Items') }}</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addOtherItem">
                            <i class="bi bi-plus-circle me-1"></i>{{ __('Add Other Item') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th style="width:140px">{{ __('Code') }}</th>
                                        <th style="width:120px">{{ __('Type') }}</th>
                                        <th style="width:90px" class="text-end">{{ __('Qty') }}</th>
                                        <th style="width:130px" class="text-end">{{ __('Price (cents)') }}</th>
                                        <th style="width:90px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $otherItems = array_filter($items, fn($r) => !in_array($r['type'] ?? '', ['part', 'service'])); @endphp
                                    @if (count($otherItems) === 0)
                                        <tr><td colspan="6" class="text-center text-muted py-3">{{ __('No other items added yet.') }}</td></tr>
                                    @endif
                                    @foreach ($items as $i => $row)
                                        @if (!in_array($row['type'] ?? '', ['part', 'service']))
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm" wire:model.defer="items.{{ $i }}.name" /></td>
                                            <td><input type="text" class="form-control form-control-sm" wire:model.defer="items.{{ $i }}.code" /></td>
                                            <td>
                                                <select class="form-select form-select-sm" wire:model.defer="items.{{ $i }}.type">
                                                    <option value="fee">{{ __('Fee') }}</option>
                                                    <option value="discount">{{ __('Discount') }}</option>
                                                </select>
                                            </td>
                                            <td><input type="number" min="1" class="form-control form-control-sm text-end" wire:model.defer="items.{{ $i }}.qty" /></td>
                                            <td><input type="number" class="form-control form-control-sm text-end" wire:model.defer="items.{{ $i }}.unit_price_cents" /></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeItem({{ $i }})"><i class="bi bi-trash"></i></button>
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="position-sticky" style="top: 1rem;">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Order Information') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="can_review_it" wire:model.defer="can_review_it">
                                <label class="form-check-label" for="can_review_it">{{ __('Customer can review') }}</label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Status') }}</label>
                                <select class="form-select" wire:model.defer="status_slug">
                                    <option value="">{{ __('Select...') }}</option>
                                    @foreach ($jobStatuses ?? [] as $st)
                                        <option value="{{ $st->code }}">{{ $st->label ?? $st->code }}</option>
                                    @endforeach
                                </select>
                                @error('status_slug')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Payment Status') }}</label>
                                <select class="form-select" wire:model.defer="payment_status_slug">
                                    <option value="">{{ __('Select...') }}</option>
                                    @foreach ($paymentStatuses ?? [] as $st)
                                        <option value="{{ $st->code }}">{{ $st->label ?? $st->code }}</option>
                                    @endforeach
                                </select>
                                @error('payment_status_slug')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Priority') }}</label>
                                <input type="text" class="form-control" wire:model.defer="priority" />
                                @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Tax Mode') }}</label>
                                <select class="form-select" wire:model.defer="prices_inclu_exclu">
                                    <option value="">{{ __('Select...') }}</option>
                                    <option value="inclusive">{{ __('Inclusive') }}</option>
                                    <option value="exclusive">{{ __('Exclusive') }}</option>
                                </select>
                                @error('prices_inclu_exclu')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Order Note (Customer)') }}</label>
                                <textarea class="form-control" rows="3" wire:model.defer="wc_order_note"></textarea>
                                @error('wc_order_note')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Attachment') }}</label>
                                <input type="file" class="form-control" wire:model="job_file" />
                                @error('job_file')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100">{{ __('Save') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
