<div class="tabs-panel team-wrap" id="wcrb_signature_workflow" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_signature_workflow-label">
	<div class="wrap">
		<h2>{{ __('Digital Signature Workflow') }}</h2>
		<p>{{ __('Configure digital signature workflow for repair orders and customer approvals.') }}</p>

		<div class="wc-rb-grey-bg-box">
			<h3>{{ __('Signature Settings') }}</h3>
			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.signature.update', ['business' => $tenant->slug]) }}">
				@csrf
				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row"><label for="signature_required">{{ __('Require Signature') }}</label></th>
							<td>
								<input type="checkbox" name="signature_required" id="signature_required" {{ ($signatureRequiredUi ?? false) ? 'checked' : '' }} />
								{{ __('Require customer signature on repair orders') }}
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="signature_type">{{ __('Signature Type') }}</label></th>
							<td>
								@php
									$type = (string) old('signature_type', (string) ($signatureTypeUi ?? 'draw'));
								@endphp
								<select name="signature_type" id="signature_type" class="form-control">
									<option value="draw" {{ $type === 'draw' ? 'selected' : '' }}>{{ __('Draw Signature') }}</option>
									<option value="type" {{ $type === 'type' ? 'selected' : '' }}>{{ __('Type Signature') }}</option>
									<option value="upload" {{ $type === 'upload' ? 'selected' : '' }}>{{ __('Upload Signature') }}</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="signature_terms">{{ __('Signature Terms') }}</label></th>
							<td>
								<textarea name="signature_terms" id="signature_terms" rows="4" class="large-text" placeholder="{{ __('I agree to the terms and conditions of the repair service.') }}">{{ old('signature_terms', (string) ($signatureTermsUi ?? '')) }}</textarea>
							</td>
						</tr>
					</tbody>
				</table>
				<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
			</form>
		</div>
	</div>
</div>
