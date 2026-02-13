<div class="tabs-panel team-wrap{{ $class_status }}" id="panel3" role="tabpanel" aria-hidden="false" aria-labelledby="panel3-label">
	
	<p class="help-text">
		<a class="button button-primary button-small" data-bs-toggle="modal" data-bs-target="#statusFormModal">
			{{ __( 'Add New Status' ) }}
		</a>
	</p>

	<div class="modal fade" id="statusFormModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">{{ __( 'Add new Status' ) }}</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="form-message"></div>
					<form class="needs-validation" name="status_form_sync" novalidate method="post" action="{{ route('tenant.settings.job_status.store', ['business' => $tenant->slug]) }}">
						@csrf
						<div class="row g-3">
							<div class="col-md-6">
								<x-settings.field for="status_name" :label="__( 'Status Name' )" class="wcrb-settings-field">
									<x-settings.input name="status_name" id="status_name" :required="true" :value="old('status_name', '')" />
								</x-settings.field>
							</div>
							<div class="col-md-6">
								<x-settings.field for="status_description" :label="__( 'Description' )" class="wcrb-settings-field">
									<x-settings.input name="status_description" id="status_description" :value="old('status_description', '')" />
								</x-settings.field>
							</div>
							<div class="col-md-6">
								<x-settings.field for="invoice_label" :label="__( 'Invoice Label' )" class="wcrb-settings-field">
									<x-settings.input name="invoice_label" id="invoice_label" :value="old('invoice_label', 'Invoice')" />
								</x-settings.field>
							</div>
							<div class="col-md-6">
								<x-settings.field for="status_status" :label="__( 'Status' )" class="wcrb-settings-field">
									<x-settings.select
										name="status_status"
										id="status_status"
										:options="['active' => __( 'Active' ), 'inactive' => __( 'Inactive' )]"
										:value="(string) old('status_status', 'active')"
									/>
								</x-settings.field>
							</div>
							<div class="col-12">
								<x-settings.field
									for="statusEmailMessage"
									:label="__( 'Status Email Message' )"
									:help="__( 'Can be used in other mediums of notifications like SMS if used. Available keywords brackets required @{{KEYWORDHERE}} @{{device_name}} @{{customer_name}} @{{order_total}} @{{order_balance}}' )"
									class="wcrb-settings-field"
								>
									<x-settings.textarea
										name="statusEmailMessage"
										id="statusEmailMessage"
										rows="5"
										:placeholder="__( 'This message would be sent when a job status is changed to this.' )"
										:value="old('statusEmailMessage', '')"
									/>
								</x-settings.field>
							</div>
						</div>
						<div class="mt-3 d-flex justify-content-end gap-2">
							<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __( 'Cancel' ) }}</button>
							<button type="submit" class="btn btn-primary">{{ __( 'Add new' ) }}</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<div id="job_status_wrapper">
		<table id="status_poststuff" class="wp-list-table widefat fixed posts">
			<thead>
				<tr>
					<th  class="column-id">{{ __( 'ID' ) }}</th>
					<th>{{ __( 'Name' ) }}</th>
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
				@if (isset($jobStatuses) && $jobStatuses instanceof \Illuminate\Support\Collection)
					@forelse ($jobStatuses as $s)
						<tr>
							<td class="column-id">{{ $s->id }}</td>
							<td>{{ $s->label }}</td>
							<td>—</td>
							<td>{{ $s->invoice_label ?: '—' }}</td>

							@if ($wc_inventory_management_status)
							<td>—</td>
							@endif
							<td class="column-id">{{ $s->is_active ? __( 'Active' ) : __( 'Inactive' ) }}</td>
							<td class="column-id">
								<a class="button button-small" data-bs-toggle="modal" data-bs-target="#editStatusModal-{{ $s->id }}">{{ __( 'Edit' ) }}</a>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="{{ $wc_inventory_management_status ? 8 : 7 }}" class="text-center text-muted">{{ __( 'No statuses found.' ) }}</td>
						</tr>
					@endforelse
				@else
					{!! $job_status_rows_html !!}
				@endif
			</tbody>
		</table>

		@if (isset($jobStatuses) && $jobStatuses instanceof \Illuminate\Support\Collection)
			@foreach ($jobStatuses as $s)
				<div class="modal fade" id="editStatusModal-{{ $s->id }}" tabindex="-1" aria-hidden="true">
					<div class="modal-dialog modal-lg">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title">{{ __( 'Edit Status' ) }}</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<div class="form-message"></div>
								<form class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.job_status.statuses.update', ['business' => $tenant->slug, 'status' => $s->id]) }}">
									@csrf
									<div class="row g-3">
										<div class="col-md-6">
											<x-settings.field :for="'status_name_'.$s->id" :label="__( 'Status Name' )" class="wcrb-settings-field">
												<x-settings.input name="status_name" :id="'status_name_'.$s->id" :required="true" :value="old('status_name', $s->label)" />
											</x-settings.field>
										</div>
										<div class="col-md-6">
											<x-settings.field :for="'invoice_label_'.$s->id" :label="__( 'Invoice Label' )" class="wcrb-settings-field">
												<x-settings.input name="invoice_label" :id="'invoice_label_'.$s->id" :value="old('invoice_label', $s->invoice_label)" />
											</x-settings.field>
										</div>
										<div class="col-md-6">
											<x-settings.field :for="'status_status_'.$s->id" :label="__( 'Status' )" class="wcrb-settings-field">
												<x-settings.select
													name="status_status"
													:id="'status_status_'.$s->id"
													:options="['active' => __( 'Active' ), 'inactive' => __( 'Inactive' )]"
													:value="(string) old('status_status', $s->is_active ? 'active' : 'inactive')"
												/>
											</x-settings.field>
										</div>
										<div class="col-12">
											<x-settings.field
												:for="'statusEmailMessage_'.$s->id"
												:label="__( 'Status Email Message' )"
												:help="__( 'Can be used in other mediums of notifications like SMS if used. Available keywords brackets required @{{KEYWORDHERE}} @{{device_name}} @{{customer_name}} @{{order_total}} @{{order_balance}}' )"
												class="wcrb-settings-field"
											>
												<x-settings.textarea
													name="statusEmailMessage"
													:id="'statusEmailMessage_'.$s->id"
													rows="5"
													:placeholder="__( 'This message would be sent when a job status is changed to this.' )"
													:value="old('statusEmailMessage', $s->email_template)"
												/>
											</x-settings.field>
										</div>
									</div>
									<div class="mt-3 d-flex justify-content-end gap-2">
										<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __( 'Cancel' ) }}</button>
										<button type="submit" class="btn btn-primary">{{ __( 'Save changes' ) }}</button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			@endforeach
		@endif
		<!-- Let's produce the form for status to consider completed and cancelled /-->
	</div><!-- Post Stuff/-->

	<div class="wc-rb-grey-bg-box">
		<h2>{{ __( 'Status settings' ) }}</h2>
		<div class="job_status_settings_msg"></div>

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.job_status.update', ['business' => $tenant->slug]) }}" data-success-class=".job_status_settings_msg">
			@csrf
			<div class="wcrb-settings-form">
			<div class="grid-x grid-margin-x">
				<div class="cell medium-6 small-12">
					<x-settings.field for="wcrb_job_status_delivered" :label="__( 'Job status to consider job completed' )" class="wcrb-settings-field">
						<x-settings.select
							name="wcrb_job_status_delivered"
							id="wcrb_job_status_delivered"
							:options="($jobStatusOptions ?? [])"
							:value="(string) old('wcrb_job_status_delivered', $job_status_delivered)"
						/>
					</x-settings.field>
				</div>
				<div class="cell medium-6 small-12">
					<x-settings.field for="wcrb_job_status_cancelled" :label="__( 'Job status to consider job cancelled' )" class="wcrb-settings-field">
						<x-settings.select
							name="wcrb_job_status_cancelled"
							id="wcrb_job_status_cancelled"
							:options="($jobStatusOptions ?? [])"
							:value="(string) old('wcrb_job_status_cancelled', $job_status_cancelled)"
						/>
					</x-settings.field>
				</div>
			</div>
			</div>
			
			<input type="hidden" name="form_action" value="wcrb_update_job_status_consideration" />
			<input type="hidden" name="form_type" value="wcrb_update_job_status_consideration" />

			<button type="submit" class="button button-primary" data-type="rbssubmitdevices">Update</button>
		</form>
	</div><!-- wc rb Devices /-->

</div><!-- tab 3 Ends -->
