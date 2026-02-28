{{--
  ┌──────────────────────────────────────────────────────────────────┐
  │  <x-ui.datatable>  — Reusable DataTable component               │
  │                                                                  │
  │  Props:                                                          │
  │   • tableId       (string)  — DOM id for the <table>            │
  │   • title         (string)  — Card heading                      │
  │   • :columns      (array)   — [['label'=>'Name','key'=>'name']] │
  │   • :rows         (array)   — Array of assoc-arrays for data    │
  │   • :searchable   (bool)    — Show search box (default true)    │
  │   • :paginate     (bool)    — Show pagination (default true)    │
  │   • :perPage      (int)     — Rows per page (default 10)       │
  │   • :perPageOptions (array) — e.g. [10,25,50,100]              │
  │   • :exportable   (bool)    — Show export button (default false)│
  │   • :filterable   (bool)    — Show column filter row (false)    │
  │   • createRoute   (string)  — URL for "Add" button (optional)  │
  │   • createLabel   (string)  — Label for "Add" button            │
  │   • emptyMessage  (string)  — Shown when 0 rows                │
  │                                                                  │
  │  Slots:                                                          │
  │   • actions       — Extra toolbar buttons (left side)           │
  │   • filters       — Advanced filter bar (between toolbar & tbl) │
  │   • bulk-actions  — Dropdown shown when rows are selected       │
  │                                                                  │
  │  Columns support:                                                │
  │   'align'  => 'text-end' | 'text-center'                       │
  │   'width'  => '120px'                                            │
  │   'nowrap' => true                                               │
  │   'badge'  => true  (renders value as .wcrb-pill)               │
  │   'html'   => true  (render raw HTML from row value)            │
  └──────────────────────────────────────────────────────────────────┘
--}}
@props([
    'tableId'        => 'dataTable_' . uniqid(),
    'title'          => null,
    'columns'        => [],
    'rows'           => [],
    'searchable'     => true,
    'paginate'       => true,
    'perPage'        => 10,
    'perPageOptions' => [10, 25, 50, 100],
    'exportable'     => false,
    'filterable'     => false,
    'createRoute'    => null,
    'createLabel'    => __('Add New'),
    'emptyMessage'   => __('No records found.'),
])

<div
    x-data="rbDataTable({
        rows: {{ json_encode($rows) }},
        perPage: {{ $perPage }},
        columns: {{ json_encode(collect($columns)->map(fn ($c) => ['key' => $c['key'] ?? '', 'searchable' => $c['searchable'] ?? true])->values()) }},
    })"
    x-init="$watch('rows', (newRows) => { allRows = newRows.map((r, i) => ({ _id: i, ...r })); currentPage = 1; })"
    x-cloak
    {{ $attributes->class(['rb-datatable-wrapper']) }}
>
    <x-settings.card :title="$title">

        {{-- ═══════ Toolbar ═══════ --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">

            {{-- Left side: slot actions + optional filters toggle --}}
            <div class="d-flex align-items-center gap-2 flex-wrap">
                {{ $actions ?? '' }}

                @if($filterable)
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="showFilters = !showFilters">
                    <i class="bi bi-funnel"></i> {{ __('Filters') }}
                    <template x-if="activeFilterCount > 0">
                        <span class="badge bg-primary ms-1 rounded-pill" x-text="activeFilterCount"></span>
                    </template>
                </button>
                @endif

                @if($exportable)
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download"></i> {{ __('Export') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" @click.prevent="exportCSV()"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
                        <li><a class="dropdown-item" href="#" @click.prevent="exportPrint()"><i class="bi bi-printer me-2"></i>{{ __('Print') }}</a></li>
                    </ul>
                </div>
                @endif
            </div>

            {{-- Right side: search + create --}}
            <div class="d-flex align-items-center gap-2">
                @if($searchable)
                <div class="position-relative">
                    <i class="bi bi-search position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem;"></i>
                    <input
                        type="text"
                        x-model.debounce.250ms="search"
                        placeholder="{{ __('Search…') }}"
                        class="form-control form-control-sm"
                        style="padding-left: 2.2rem; min-width: 220px; border-radius: 10px; font-size: 0.8rem;"
                    >
                    <button
                        x-show="search.length > 0"
                        @click="search = ''"
                        type="button"
                        class="btn-close position-absolute"
                        style="right: 10px; top: 50%; transform: translateY(-50%); font-size: 0.55rem; opacity: 0.5;"
                    ></button>
                </div>
                @endif

                @if($createRoute)
                <a class="btn btn-primary btn-sm" href="{{ $createRoute }}">
                    <i class="bi bi-plus-lg me-1"></i>{{ $createLabel }}
                </a>
                @endif
            </div>
        </div>

        {{-- ═══════ Advanced Filters Slot ═══════ --}}
        @if($filterable && isset($filters))
        <div x-show="showFilters" x-transition.duration.200ms class="mb-3 p-3 rounded-3" style="background: #f8fafc; border: 1px solid #eef2f7;">
            {{ $filters }}
        </div>
        @endif

        {{-- ═══════ Bulk Actions ═══════ --}}
        <div x-show="selectedRows.length > 0" x-transition.duration.150ms class="d-flex align-items-center gap-2 mb-2 p-2 rounded-2" style="background: #eff6ff; border: 1px solid #bfdbfe;">
            <span class="text-primary fw-medium" style="font-size: 0.8rem;">
                <i class="bi bi-check2-square me-1"></i>
                <span x-text="selectedRows.length"></span> {{ __('selected') }}
            </span>
            {{ $bulkActions ?? '' }}
            <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" @click="selectedRows = []" style="font-size: 0.75rem;">
                {{ __('Clear') }}
            </button>
        </div>

        {{-- ═══════ Table ═══════ --}}
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="{{ $tableId }}">
                <thead class="bg-light">
                    <tr>
                        {{-- Checkbox column --}}
                        @if(isset($bulkActions))
                        <th style="width: 40px;">
                            <input type="checkbox" class="form-check-input" @click="toggleSelectAll($event)" :checked="isAllSelected" :indeterminate.prop="isIndeterminate">
                        </th>
                        @endif

                        @foreach($columns as $col)
                        <th
                            class="{{ $col['align'] ?? '' }} {{ ($col['sortable'] ?? true) ? 'rb-sortable' : '' }}"
                            style="{{ isset($col['width']) ? 'width: ' . $col['width'] . ';' : '' }} cursor: {{ ($col['sortable'] ?? true) ? 'pointer' : 'default' }};"
                            {!! ($col['sortable'] ?? true) ? '@click=sortBy(\'' . e($col['key']) . '\')' : '' !!}
                        >
                            <span class="d-inline-flex align-items-center gap-1">
                                {{ $col['label'] }}
                                <template x-if="sortCol === '{{ $col['key'] }}'">
                                    <i class="bi" :class="sortDir === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill'" style="font-size: 0.6rem; opacity: 0.7;"></i>
                                </template>
                            </span>
                        </th>
                        @endforeach
                    </tr>

                    {{-- Column filter row --}}
                    @if($filterable)
                    <tr x-show="showFilters" class="rb-col-filters">
                        @if(isset($bulkActions))<th></th>@endif
                        @foreach($columns as $col)
                        <th style="padding: 0.35rem 0.5rem;">
                            @if($col['filter'] ?? false)
                            <input
                                type="text"
                                class="form-control form-control-sm"
                                placeholder="{{ $col['label'] }}…"
                                x-model.debounce.250ms="colFilters['{{ $col['key'] }}']"
                                style="font-size: 0.72rem; padding: 0.3rem 0.5rem; border-radius: 6px;"
                            >
                            @endif
                        </th>
                        @endforeach
                    </tr>
                    @endif

                </thead>
                <tbody>
                    <template x-for="(row, idx) in paginatedRows" :key="row._id ?? idx">
                        <tr :class="{ 'table-primary': selectedRows.includes(row._id ?? idx) }">
                            @if(isset($bulkActions))
                            <td>
                                <input type="checkbox" class="form-check-input" :value="row._id ?? idx" x-model="selectedRows">
                            </td>
                            @endif

                            @foreach($columns as $col)
                            <td class="{{ $col['align'] ?? '' }} {{ ($col['nowrap'] ?? false) ? 'text-nowrap' : '' }}">
                                @if($col['badge'] ?? false)
                                    <span class="wcrb-pill" :class="row['_badgeClass_{{ $col['key'] }}'] ?? 'wcrb-pill--active'" x-text="row['{{ $col['key'] }}']"></span>
                                @elseif($col['html'] ?? false)
                                    <span x-html="row['{{ $col['key'] }}']" x-init="$nextTick(() => { const dropdowns = $el.querySelectorAll('[data-bs-toggle=dropdown]'); dropdowns.forEach(el => new bootstrap.Dropdown(el)); })"></span>
                                @else
                                    <span x-text="row['{{ $col['key'] }}']"></span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                    </template>

                    {{-- Empty state --}}
                    <template x-if="filteredRows.length === 0">
                        <tr>
                            <td colspan="{{ count($columns) + (isset($bulkActions) ? 1 : 0) }}" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <i class="bi bi-inbox" style="font-size: 2rem; color: #cbd5e1;"></i>
                                    <span class="text-muted" style="font-size: 0.85rem;">{{ $emptyMessage }}</span>
                                    <template x-if="search.length > 0">
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" @click="search = ''" style="font-size: 0.78rem;">
                                            <i class="bi bi-x-circle me-1"></i>{{ __('Clear search') }}
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- ═══════ Footer: Info + Pagination ═══════ --}}
        @if($paginate)
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 px-1">

            {{-- Info --}}
            <div class="d-flex align-items-center gap-2" style="font-size: 0.8rem; color: #64748b;">
                <span>{{ __('Show') }}</span>
                <select x-model.number="perPage" class="form-select form-select-sm" style="width: auto; min-width: 70px; border-radius: 8px; font-size: 0.8rem;">
                    @foreach($perPageOptions as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
                <span>
                    {{ __('of') }} <strong x-text="filteredRows.length"></strong> {{ __('entries') }}
                </span>
            </div>

            {{-- Pagination --}}
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    {{-- Previous --}}
                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                        <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">
                            <i class="bi bi-chevron-left" style="font-size: 0.65rem;"></i>
                        </a>
                    </li>

                    {{-- Page numbers --}}
                    <template x-for="p in visiblePages" :key="p">
                        <li class="page-item" :class="{ active: p === currentPage, disabled: p === '...' }">
                            <a class="page-link" href="#" @click.prevent="p !== '...' && goToPage(p)" x-text="p"></a>
                        </li>
                    </template>

                    {{-- Next --}}
                    <li class="page-item" :class="{ disabled: currentPage === totalPages || totalPages === 0 }">
                        <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">
                            <i class="bi bi-chevron-right" style="font-size: 0.65rem;"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif
    </x-settings.card>
</div>

@once
@push('page-scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rbDataTable', (config) => ({
        allRows: config.rows.map((r, i) => ({ _id: i, ...r })),
        search: '',
        perPage: config.perPage || 10,
        currentPage: 1,
        sortCol: '',
        sortDir: 'asc',
        selectedRows: [],
        showFilters: false,
        colFilters: {},
        columns: config.columns || [],

        /* ── Computed ── */
        get filteredRows() {
            let result = [...this.allRows];
            const q = this.search.toLowerCase().trim();

            // Global search
            if (q) {
                const searchableCols = this.columns.filter(c => c.searchable !== false).map(c => c.key);
                result = result.filter(row =>
                    searchableCols.some(key => {
                        const val = row[key];
                        return val !== null && val !== undefined && String(val).toLowerCase().includes(q);
                    })
                );
            }

            // Column filters
            for (const [key, filterVal] of Object.entries(this.colFilters)) {
                const fv = (filterVal || '').toLowerCase().trim();
                if (fv) {
                    result = result.filter(row => {
                        const val = row[key];
                        return val !== null && val !== undefined && String(val).toLowerCase().includes(fv);
                    });
                }
            }

            // Sort
            if (this.sortCol) {
                const col = this.sortCol;
                const dir = this.sortDir === 'asc' ? 1 : -1;
                result.sort((a, b) => {
                    let va = a[col] ?? '';
                    let vb = b[col] ?? '';
                    // Try numeric comparison
                    const na = parseFloat(String(va).replace(/[^0-9.-]/g, ''));
                    const nb = parseFloat(String(vb).replace(/[^0-9.-]/g, ''));
                    if (!isNaN(na) && !isNaN(nb)) return (na - nb) * dir;
                    return String(va).localeCompare(String(vb)) * dir;
                });
            }

            return result;
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage));
        },

        get paginatedRows() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredRows.slice(start, start + this.perPage);
        },

        get visiblePages() {
            const total = this.totalPages;
            const cur = this.currentPage;
            if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

            const pages = [];
            pages.push(1);
            if (cur > 3) pages.push('...');
            for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) {
                pages.push(i);
            }
            if (cur < total - 2) pages.push('...');
            pages.push(total);
            return pages;
        },

        get isAllSelected() {
            return this.paginatedRows.length > 0 && this.paginatedRows.every(r => this.selectedRows.includes(r._id));
        },

        get isIndeterminate() {
            return this.selectedRows.length > 0 && !this.isAllSelected;
        },

        get activeFilterCount() {
            return Object.values(this.colFilters).filter(v => (v || '').trim()).length;
        },

        /* ── Methods ── */
        sortBy(col) {
            if (this.sortCol === col) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortCol = col;
                this.sortDir = 'asc';
            }
        },

        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
        },

        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedRows = [...new Set([...this.selectedRows, ...this.paginatedRows.map(r => r._id)])];
            } else {
                const pageIds = this.paginatedRows.map(r => r._id);
                this.selectedRows = this.selectedRows.filter(id => !pageIds.includes(id));
            }
        },

        exportCSV() {
            const cols = this.columns.map(c => c.key);
            const header = this.columns.map(c => c.key).join(',');
            const rows = this.filteredRows.map(row => cols.map(k => '"' + String(row[k] ?? '').replace(/"/g, '""') + '"').join(','));
            const csv = [header, ...rows].join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'export.csv'; a.click();
            URL.revokeObjectURL(url);
        },

        exportPrint() {
            window.print();
        },

        /* ── Watchers ── */
        init() {
            this.$watch('search', () => this.currentPage = 1);
            this.$watch('perPage', () => this.currentPage = 1);
            this.$watch('colFilters', () => this.currentPage = 1, { deep: true });
        },
    }));
});
</script>
@endpush
@endonce
