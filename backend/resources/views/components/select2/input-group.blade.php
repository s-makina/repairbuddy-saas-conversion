@props([
    'name',
    'id' => null,
    'label' => null,
    'multiple' => false,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'buttonUrl' => null,
    'buttonTitle' => null,
    'buttonAriaLabel' => null,
    'buttonIconClass' => 'bi bi-plus-circle',
    'errorKey' => null,
    'select2' => true,
    'select2AllowClear' => false,
])

@php
    $selectId = $id;
    if (! is_string($selectId) || $selectId === '') {
        $fromName = is_string($name) ? $name : '';
        $fromName = str_replace(['][', '[', ']', ' '], ['_', '_', '', '_'], $fromName);
        $fromName = preg_replace('/[^A-Za-z0-9\-_:]/', '_', $fromName) ?? '';
        $fromName = trim($fromName, '_');
        $selectId = $fromName !== '' ? $fromName : null;
    }
    $values = $multiple ? array_map('strval', (array) ($value ?? [])) : [(string) ($value ?? '')];
    $select2Enabled = (bool) $select2;
    $select2AllowClear = (bool) $select2AllowClear;
    $select2Placeholder = is_string($placeholder) && $placeholder !== '' ? $placeholder : __('Select...');
@endphp

@if ($label !== null)
    <label class="form-label" @if($selectId) for="{{ $selectId }}" @endif>{{ $label }}</label>
@endif

<div class="input-group">
    <select
        name="{{ $name }}"
        @if($selectId) id="{{ $selectId }}" @endif
        @if($multiple) multiple @endif
        data-select2="{{ $select2Enabled ? '1' : '0' }}"
        data-select2-placeholder="{{ $select2Placeholder }}"
        data-select2-allow-clear="{{ $select2AllowClear ? '1' : '0' }}"
        {{ $attributes->class(['form-select']) }}
    >
        @if (! $multiple)
            <option value="">{{ $select2Placeholder }}</option>
        @endif
        @foreach ($options as $optValue => $optLabel)
            @php
                $selected = $multiple
                    ? in_array((string) $optValue, $values, true)
                    : ((string) ($values[0] ?? '') === (string) $optValue);
            @endphp
            <option value="{{ $optValue }}" {{ $selected ? 'selected' : '' }}>{{ $optLabel }}</option>
        @endforeach
    </select>

    @if (is_string($buttonUrl) && $buttonUrl !== '')
        <a
            class="btn btn-primary"
            href="{{ $buttonUrl }}"
            target="_blank"
            rel="noopener"
            @if($buttonTitle) title="{{ $buttonTitle }}" @endif
            aria-label="{{ $buttonAriaLabel ?? $buttonTitle ?? __('Add') }}"
        >
            <i class="{{ $buttonIconClass }}"></i>
        </a>
    @endif
</div>

@if ($errorKey)
    @error($errorKey)
        <div class="text-danger small">{{ $message }}</div>
    @enderror
@endif
