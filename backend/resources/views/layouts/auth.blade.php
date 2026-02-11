<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ $title ?? 'RepairBuddy' }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <style>
      :root {
        --rb-blue: #063e70;
        --rb-orange: #fd6742;
      }

      body {
        background: #f8f9fa;
      }

      .rb-auth-card {
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 10px 30px rgba(0,0,0,0.06);
      }

      .rb-brand {
        color: var(--rb-blue);
      }

      .btn-rb {
        background: var(--rb-blue);
        border-color: var(--rb-blue);
        color: #fff;
      }

      .btn-rb:hover {
        opacity: 0.92;
        color: #fff;
      }
    </style>
  </head>
  <body>
    <div class="container py-5">
      @yield('content')
    </div>
  </body>
</html>
