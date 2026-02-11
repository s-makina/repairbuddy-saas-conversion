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

  @stack('page-styles')
</head>
<body>
  <div class="dashboard-wrapper">
