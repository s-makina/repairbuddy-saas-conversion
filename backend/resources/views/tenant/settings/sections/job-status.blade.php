<div class="tabs-panel team-wrap{{ $class_status }}" id="panel3" role="tabpanel" aria-hidden="false" aria-labelledby="panel3-label">
	
	<p class="help-text">
		<a class="button button-primary button-small" data-open="statusFormReveal">
			{{ __( 'Add New Status' ) }}
		</a>
	</p>
	{!! $add_status_form_footer_html ?? '' !!}

	<div id="job_status_wrapper">
		<table id="status_poststuff" class="wp-list-table widefat fixed striped posts">
			<thead>
				<tr>
					<th  class="column-id">{{ __( 'ID' ) }}</th>
					<th>{{ __( 'Name' ) }}</th>
					<th>{{ __( 'Slug' ) }}</th>
					<th>{{ __( 'Description' ) }}</th>
					<th>{{ __( 'Invoice Label' ) }}</th>

					@if ($wc_inventory_management_status)
					<th>{{ __( 'Manage Woo Stock' ) }}</th>
					@endif
					<th class="column-id">{{ __( 'Status' ) }}</th>
					<th class="column-id">{{ __( 'Actions' ) }}</th>
				</tr>
			</thead>

			<tbody>
				{!! $job_status_rows_html !!}
			</tbody>
		</table>
		<!-- Let's produce the form for status to consider completed and cancelled /-->
	</div><!-- Post Stuff/-->

	<div class="wc-rb-grey-bg-box">
		<h2>{{ __( 'Status settings' ) }}</h2>
		<div class="job_status_settings_msg"></div>

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.job_status.update', ['business' => $tenant->slug]) }}" data-success-class=".job_status_settings_msg">
			@csrf
			<table class="form-table border">
				<tbody>
					<tr>
						<th scope="row">
							<label for="wcrb_job_status_delivered">{{ __( 'Job status to consider job completed' ) }}</label>
						</th>
						<td>
							<x-settings.select
								name="wcrb_job_status_delivered"
								id="wcrb_job_status_delivered"
								class="form-select"
								:options="($jobStatusOptions ?? [])"
								:value="(string) old('wcrb_job_status_delivered', $job_status_delivered)"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wcrb_job_status_cancelled">{{ __( 'Job status to consider job cancelled' ) }}</label>
						</th>
						<td>
							<x-settings.select
								name="wcrb_job_status_cancelled"
								id="wcrb_job_status_cancelled"
								class="form-select"
								:options="($jobStatusOptions ?? [])"
								:value="(string) old('wcrb_job_status_cancelled', $job_status_cancelled)"
							/>
						</td>
					</tr>
				</tbody>
			</table>
			
			<input type="hidden" name="form_action" value="wcrb_update_job_status_consideration" />
			<input type="hidden" name="form_type" value="wcrb_update_job_status_consideration" />

			<button type="submit" class="button button-primary" data-type="rbssubmitdevices">Update</button>
		</form>
	</div><!-- wc rb Devices /-->

</div><!-- tab 3 Ends -->
