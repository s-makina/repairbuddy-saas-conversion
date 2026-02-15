@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Edit User')])

@section('content')
	<div class="container-fluid p-3">
		@if ($errors->any())
			<div class="alert alert-danger">
				<div class="fw-semibold mb-1">{{ __( 'Please fix the errors below.' ) }}</div>
				<ul class="mb-0">
					@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		@endif

		<div class="row justify-content-center">
			<div class="col-12 col-lg-8 col-xl-7">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">{{ __('Edit User') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.settings.users.update', ['business' => $tenant->slug, 'user' => $editUser->id]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="name" class="col-sm-3 col-form-label">{{ __('Name') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', (string) ($editUser->name ?? '')) }}" required>
											@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="email" class="col-sm-3 col-form-label">{{ __('Email') }} *</label>
										<div class="col-sm-9">
											<input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', (string) ($editUser->email ?? '')) }}" required>
											@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="status" class="col-sm-3 col-form-label">{{ __('Status') }}</label>
										<div class="col-sm-9">
											<select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
												@foreach (($statusOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('status', (string) ($editUser->status ?? 'active')) === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="role_id" class="col-sm-3 col-form-label">{{ __('Role') }} *</label>
										<div class="col-sm-9">
											<select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
												@foreach (($roleOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('role_id', (string) ($currentRoleId ?? '')) === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="password" class="col-sm-3 col-form-label">{{ __('New password') }}</label>
										<div class="col-sm-9">
											<input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('Leave blank to keep unchanged') }}">
											@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
											<div class="form-text">{{ __('Leave empty if you do not want to change the password.') }}</div>
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="password_confirmation" class="col-sm-3 col-form-label">{{ __('Confirm') }}</label>
										<div class="col-sm-9">
											<input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="{{ __('Confirm new password') }}">
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="d-flex justify-content-end gap-2">
										<a class="btn btn-outline-secondary" href="{{ route('tenant.settings.users.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
										<button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
