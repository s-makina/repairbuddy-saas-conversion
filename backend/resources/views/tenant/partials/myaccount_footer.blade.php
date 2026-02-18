</div><!-- main content /-->
</div><!-- dashboard-wrapper /-->

<script
  src="{{ asset('repairbuddy/my_account/js/jquery.min.js') }}"
  onerror="(function(){var s=document.createElement('script');s.src='https://code.jquery.com/jquery-3.7.1.min.js';document.head.appendChild(s);})()"
></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  window.wcrb_ajax = window.wcrb_ajax || {
    ajax_url: "{{ isset($tenant?->slug) ? route('tenant.legacy-ajax', ['business' => $tenant->slug]) : '' }}",
    nonce: "{{ csrf_token() }}"
  };
  window.wcrbAjax = window.wcrbAjax || window.wcrb_ajax;

  if (window.jQuery) {
    window.jQuery.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
      }
    });
  }
</script>
<script defer src="{{ asset('repairbuddy/my_account/js/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('repairbuddy/my_account/js/chart.js') }}"></script>
<script defer src="{{ asset('repairbuddy/my_account/js/wcrb_ajax.js') }}"></script>
<script defer src="{{ asset('repairbuddy/my_account/js/wcrbscript.js') }}"></script>

@stack('page-scripts')

</body>
</html>
