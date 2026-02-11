</div><!-- main content /-->
</div><!-- dashboard-wrapper /-->

<script src="{{ asset('repairbuddy/my_account/js/jquery.min.js') }}"></script>
<script>
  window.wcrb_ajax = window.wcrb_ajax || {
    ajax_url: '',
    nonce: ''
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
<script src="{{ asset('repairbuddy/my_account/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('repairbuddy/my_account/js/chart.js') }}"></script>
<script src="{{ asset('repairbuddy/my_account/js/wcrb_ajax.js') }}"></script>
<script src="{{ asset('repairbuddy/my_account/js/wcrbscript.js') }}"></script>

@stack('page-scripts')

</body>
</html>
