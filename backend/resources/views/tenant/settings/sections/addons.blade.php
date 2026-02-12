<div class="tabs-panel team-wrap" id="addons" role="tabpanel" aria-hidden="false" 
aria-labelledby="addons-label">
	<h1>Addons</h1>
	<p>We have some addons which you can use to extend the features of your RepairBuddy WordPress Plugin.</p>
	
	<div class="theaddons-container grid-x grid-margin-x grid-container fluid">
		@if (! $rb_ms_version_defined)
		<div class="large-4 medium-4 medium-6 cell">
			<div class="documentation-section theaddon">
					<h2>MultiStore - RepairBuddy</h2>
				<p>Multistore RepairBuddy addon extends your CRM with features to have more than one stores, filter jobs based on stores. Technicians can access jobs only they have access to, Managers can access only store they have access to. Invoices can also have address of selected store on that job and much more ...</p>
				<a href="https://www.webfulcreations.com/products/multi-store-addon-repairbuddy/" class="button button-primary" target="_blank">Learn More</a>
			</div>
		</div> <!-- Column Ends /-->
		@endif

		@if (! $rb_qb_version_defined)
		<div class="large-4 medium-4 medium-6 cell">
			<div class="documentation-section theaddon">
					<h2>QuickBooks Addon – RepairBuddy</h2>
				<p>QuickBooks Addon – RepairBuddy is another great addon to expand features of your RepairBuddy supported website. Using QuickBooks addon you can easily fetch your customers from QuickBooks and also send invoices to QuickBooks from RepairBuddy. While you can manually send invoices to QuickBooks clicking button but also on status change automatically job can be sent to QuickBooks as invoice. </p>
				<a href="https://www.webfulcreations.com/products/quickbooks-addon-repairbuddy/" class="button button-primary" target="_blank">Learn More</a>
			</div>
		</div>
		@endif
	</div>
</div>
