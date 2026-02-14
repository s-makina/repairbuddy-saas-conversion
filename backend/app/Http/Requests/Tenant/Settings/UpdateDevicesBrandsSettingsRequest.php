<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDevicesBrandsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enablePinCodeField' => ['nullable', 'in:0,1'],
            'showPinCodeInDocuments' => ['nullable', 'in:0,1'],
            'useWooProductsAsDevices' => ['nullable', 'in:0,1'],

            'labels' => ['sometimes', 'array'],
            'labels.note' => ['nullable', 'string', 'max:255'],
            'labels.pin' => ['nullable', 'string', 'max:255'],
            'labels.device' => ['nullable', 'string', 'max:255'],
            'labels.deviceBrand' => ['nullable', 'string', 'max:255'],
            'labels.deviceType' => ['nullable', 'string', 'max:255'],
            'labels.imei' => ['nullable', 'string', 'max:255'],

            'additionalDeviceFields' => ['sometimes', 'array'],
            'additionalDeviceFields.*.id' => ['nullable', 'string', 'max:255'],
            'additionalDeviceFields.*.label' => ['nullable', 'string', 'max:255'],
            'additionalDeviceFields.*.type' => ['nullable', 'in:text'],
            'additionalDeviceFields.*.displayInBookingForm' => ['nullable', 'in:0,1'],
            'additionalDeviceFields.*.displayInInvoice' => ['nullable', 'in:0,1'],
            'additionalDeviceFields.*.displayForCustomer' => ['nullable', 'in:0,1'],

            'pickupDeliveryEnabled' => ['nullable', 'in:0,1'],
            'pickupCharge' => ['nullable', 'string', 'max:64'],
            'deliveryCharge' => ['nullable', 'string', 'max:64'],
            'rentalEnabled' => ['nullable', 'in:0,1'],
            'rentalPerDay' => ['nullable', 'string', 'max:64'],
            'rentalPerWeek' => ['nullable', 'string', 'max:64'],
        ];
    }
}
