@php
  /** @var \App\Models\Tenant|null $tenant */
  $pageTitle = is_string($pageTitle ?? null) ? $pageTitle : 'Dashboard';
  $siteName = config('app.name', 'RepairBuddy');
  $thePageTitle = $pageTitle . ' - ' . $siteName;
@endphp
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $thePageTitle }}</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap-icons.min.css') }}">
  <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/dark-mode.css') }}">
  <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/style.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

  <style>
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered {
      display: flex;
      flex-wrap: wrap;
      gap: .25rem;
      padding: .25rem .5rem;
    }
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
      margin: 0;
      max-width: 100%;
    }
  </style>

  @livewireStyles

  @stack('page-styles')
</head>
<body>
  <div class="dashboard-wrapper">
