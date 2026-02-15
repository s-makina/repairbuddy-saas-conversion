@push('page-styles')
    <style>
        select[data-select2="1"] + .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius);
            background-color: var(--bs-body-bg);
        }

        select[data-select2="1"] + .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-left: 0;
            padding-right: 1.5rem;
            line-height: 1.5;
        }

        select[data-select2="1"] + .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            top: 50%;
            transform: translateY(-50%);
            right: .75rem;
        }

        select[data-select2="1"] + .select2-container--bootstrap-5 .select2-selection--multiple {
            min-height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
        }

        select[data-select2="1"] + .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
            padding: 0;
        }

        select[data-select2="1"] + .select2-container--bootstrap-5 .select2-selection--multiple .select2-search__field {
            margin-top: 0;
        }

        select[data-select2="1"] + .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #86b7fe;
            box-shadow: 0 0 0 .25rem rgba(13,110,253,.25);
        }

        .input-group > .select2-container {
            width: auto !important;
            flex: 1 1 auto;
        }

        .input-group > .select2-container--bootstrap-5 .select2-selection,
        .input-group > .select2-container--bootstrap-5 .select2-selection--multiple {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
    </style>
@endpush
