<div class="tabs-panel team-wrap" id="documentation" role="tabpanel" aria-hidden="false" 
aria-labelledby="documentation-label">
	<h1>Shortcodes</h1>
	<p>RepairBuddy WordPress Plugin provides various shortcodes to use in different pages. Just copy a shortcode you need and paste in a page to use it. Please check Page Setup for some default created pages.</p>
	
	<div class="documentation-section">
		<h2>Check Repair Status</h2>
	<p>To add check case status form create a page and insert shortcode</p>
	<pre>[wc_order_status_form]</pre></div>
		
	<div class="documentation-section">
		<h2>Book Device / Book Service</h2>
		<p>Book the service with brand, device, and service selection.</p>
		<p>Doesn't include device type or grouped services.</p>
		<pre>[wc_book_my_service]</pre>

		<p>Grouped services with device type, brands, devices.</p>
		<pre>[wc_book_type_grouped_service]</pre>

		<p>To add start new job by device on front end for loged in users only</p> 
		<pre>[wc_start_job_with_device]</pre></div>

	<div class="documentation-section">
		<h2>Get feedback on job page</h2>
		<p>Using this shortcode you can get the feedback from customers on jobs you performed for them. For auto feedback request check reviews settings. </p>
		<pre>[wc_get_order_feedback]</pre>
		<h2>Display Reviews on Page</h2>
		<p>Using the following shortcode you can display reviews in a page, widget or post. Columns 1, 2, 3 </p>
		<pre>[wcrb_display_reviews columns="2" hide_below_rating="3"]</pre>
	</div>

	<div class="documentation-section">
		<h2>My Account Page</h2>
		<p>Note: If you are using WooCommerce then WooCommerce My Account page can list Repair Orders and Request quote section, You do not need to add separate account page in that case.</p>
		<p>To add user account page into front end create a page and use</p>
		<pre>[wc_cr_my_account]</pre>
	</div>	

	<div class="documentation-section">
		<h2>For Warranty Claim</h2>
		<p>Warranty claim can be done for WooCommerce products or Devices.</p>
		<p>Following Shortcode let customers book their device for warranty claim. Doesn't require services to be included.</p>
		<pre>[wc_book_my_warranty]</pre>
	</div>	
	
	<div class="documentation-section">
		<h2>Simple Quote Form</h2>
	<p>To add simple request quote form into front end use</p> 
	<pre>[wc_request_quote_form]</pre></div>

	<div class="documentation-section">
		<h2>Services Page</h2>
	<p>To populate services create a page and insert shortcode</p>
	<pre>[wc_list_services]</pre></div>

	<div class="documentation-section"><h2>Parts Page</h2>
	<p>To populate parts/products create a page and insert shortcode</p> 
	<pre>[wc_list_products]</pre></div>
</div><!-- tab Documentation Ends -->
