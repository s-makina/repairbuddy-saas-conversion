<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ $title ?? 'RepairBuddy' }}</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <style>
      :root {
        --rb-blue: #063e70;
        --rb-orange: #fd6742;
        --rb-bg-soft: #fcfdfe;
        --rb-border: #f1f5f9;
        --rb-text-main: #0f172a;
        --rb-text-muted: #64748b;
      }

      body {
        font-family: 'Inter', sans-serif;
        background-color: var(--rb-bg-soft);
        background-image:
          radial-gradient(at 0% 0%, rgba(253, 103, 66, 0.03) 0px, transparent 50%),
          radial-gradient(at 100% 100%, rgba(6, 62, 112, 0.03) 0px, transparent 50%);
        color: var(--rb-text-main);
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        margin: 0;
        padding: 20px;
      }

      .auth-card {
        background: #ffffff;
        width: 100%;
        max-width: 440px;
        padding: 40px 48px;
        border-radius: 32px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(226, 232, 240, 0.8);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        animation: reveal 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
      }

      .auth-card.wider {
        max-width: 480px;
      }

      .auth-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--rb-blue), var(--rb-orange));
        opacity: 0.8;
      }

      .brand-logo {
        font-weight: 800;
        font-size: 2rem;
        color: var(--rb-blue);
        text-decoration: none;
        display: inline-block;
        margin-bottom: 24px;
        letter-spacing: -1px;
        transition: opacity 0.3s;
      }

      .brand-logo:hover {
        opacity: 0.8;
      }

      .brand-logo span {
        color: var(--rb-orange);
      }

      .auth-header h1 {
        font-size: 1.85rem;
        font-weight: 800;
        margin-bottom: 12px;
        letter-spacing: -0.5px;
      }

      .auth-header p {
        color: var(--rb-text-muted);
        font-size: 1rem;
        margin-bottom: 30px;
        line-height: 1.5;
      }

      .form-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--rb-text-main);
        margin-bottom: 10px;
      }

      .input-group-modern {
        position: relative;
        margin-bottom: 20px;
      }

      .input-wrapper {
        position: relative;
      }

      .input-group-modern i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--rb-text-muted);
        font-size: 1.1rem;
        transition: all 0.3s;
        pointer-events: none;
      }

      .form-control {
        border-radius: 16px;
        padding: 14px 16px 14px 48px;
        border: 1.5px solid #eef2f6;
        font-size: 0.95rem;
        background-color: #fcfdfe;
        font-weight: 500;
        transition: all 0.3s ease;
      }

      .form-control:focus {
        border-color: var(--rb-blue);
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(6, 62, 112, 0.08);
        outline: none;
      }

      .form-control:focus + i {
        color: var(--rb-blue);
      }

      .form-control.is-invalid {
        border-color: #dc3545;
        background-color: #fff;
      }

      .form-control.is-invalid:focus {
        box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.15);
      }

      .password-toggle {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--rb-text-muted);
        cursor: pointer;
        padding: 5px;
        transition: color 0.3s;
        background: none;
        border: none;
      }

      .password-toggle:hover {
        color: var(--rb-blue);
      }

      .btn-modern {
        background-color: var(--rb-blue);
        color: #fff;
        border: none;
        border-radius: 16px;
        padding: 16px;
        font-weight: 700;
        font-size: 1.05rem;
        width: 100%;
        margin-top: 12px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 20px rgba(6, 62, 112, 0.1);
      }

      .btn-modern:hover {
        background-color: #05335d;
        transform: translateY(-2px);
        box-shadow: 0 15px 30px rgba(6, 62, 112, 0.2);
        color: #fff;
      }

      .btn-modern:active {
        transform: translateY(0);
      }

      .forgot-password {
        font-size: 0.875rem;
        color: var(--rb-blue);
        text-decoration: none;
        font-weight: 600;
      }

      .forgot-password:hover {
        text-decoration: underline;
      }

      .footer-link {
        text-align: center;
        margin-top: 32px;
        font-size: 0.95rem;
        color: var(--rb-text-muted);
      }

      .footer-link a {
        color: var(--rb-blue);
        text-decoration: none;
        font-weight: 700;
      }

      .footer-link a:hover {
        text-decoration: underline;
      }

      /* OTP Input Styles */
      .otp-container {
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 30px;
      }

      .otp-input {
        width: 50px;
        height: 60px;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 700;
        border-radius: 12px;
        border: 1.5px solid #eef2f6;
        background-color: #fcfdfe;
        transition: all 0.3s;
      }

      .otp-input:focus {
        border-color: var(--rb-blue);
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(6, 62, 112, 0.08);
        outline: none;
      }

      /* Animations */
      @keyframes reveal {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      /* Alert styles */
      .alert {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      }
    </style>
  </head>
  <body>
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
  </body>
</html>
