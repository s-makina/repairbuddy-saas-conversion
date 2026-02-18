@props([
    'backHref' => null,
    'iconClass' => 'bi bi-briefcase-fill',
    'title' => '',
    'subtitle' => '',
])

<x-ui.page-hero :back-href="$backHref" :icon-class="$iconClass" :title="$title" :subtitle="$subtitle">
    <x-slot:actions>
        {{ $actions ?? '' }}
    </x-slot:actions>
</x-ui.page-hero>
