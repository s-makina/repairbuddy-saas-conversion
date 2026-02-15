@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Roles')])

@push('page-styles')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" />
<style>
  #permissionsPanelBody {
    max-height: calc(100vh - 260px);
    overflow: auto;
  }
  #permissionsPanelHeader {
    position: sticky;
    top: 0;
    z-index: 5;
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color);
    padding-bottom: .75rem;
    margin-bottom: .75rem;
  }
</style>
@endpush

@push('page-scripts')
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script>
  (function () {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
      return;
    }

    var $table = window.jQuery('#rolesTable');
    if ($table.length === 0) {
      return;
    }

    if (window.jQuery.fn.DataTable.isDataTable($table)) {
      return;
    }

    var allPermissions = [];
    var selectedRoleId = null;
    var selectedPermissionIds = new Set();
    var isSyncing = false;
    var pendingSync = false;

    function escapeHtml(str) {
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function setLoading(isLoading) {
      var el = document.getElementById('permissionsLoading');
      if (!el) return;
      el.classList.toggle('d-none', !isLoading);
    }

    function setSaveStatus(state, message) {
      var el = document.getElementById('permissionsSaveStatus');
      if (!el) return;

      el.classList.remove('text-muted', 'text-danger', 'text-success');

      if (state === 'saving') {
        el.classList.add('text-muted');
        el.textContent = "{{ __('Saving...') }}";
        return;
      }

      if (state === 'saved') {
        el.classList.add('text-success');
        el.textContent = "{{ __('Saved') }}";
        return;
      }

      if (state === 'error') {
        el.classList.add('text-danger');
        el.textContent = message || "{{ __('Failed to save') }}";
        return;
      }

      el.classList.add('text-muted');
      el.textContent = message || '';
    }

    function setEmptyState(message) {
      var el = document.getElementById('permissionsEmpty');
      if (!el) return;
      el.textContent = message || '';
      el.classList.toggle('d-none', !message);
    }

    function updateHeader() {
      var el = document.getElementById('selectedRoleName');
      if (!el) return;
      if (!selectedRoleId) {
        el.textContent = "{{ __('Select a role') }}";
        document.getElementById('selectedRoleMeta')?.classList.add('d-none');
        setSaveStatus('idle', '');
        document.getElementById('permissionsSelectAll')?.setAttribute('disabled', 'disabled');
        document.getElementById('permissionsClearAll')?.setAttribute('disabled', 'disabled');
        return;
      }

      var name = document.getElementById('selectedRoleName').getAttribute('data-role-name') || "{{ __('Role') }}";
      el.textContent = name;
      document.getElementById('selectedRoleMeta')?.classList.remove('d-none');
      document.getElementById('permissionsSelectAll')?.removeAttribute('disabled');
      document.getElementById('permissionsClearAll')?.removeAttribute('disabled');
    }

    function updateCounts(filteredCount) {
      var assigned = document.getElementById('selectedRoleAssignedCount');
      var visible = document.getElementById('selectedRoleVisibleCount');
      if (assigned) assigned.textContent = String(selectedPermissionIds.size);
      if (visible) visible.textContent = String(typeof filteredCount === 'number' ? filteredCount : allPermissions.length);
    }

    function renderPermissions() {
      var list = document.getElementById('permissionsList');
      if (!list) return;

      if (!selectedRoleId) {
        list.innerHTML = '';
        setEmptyState("{{ __('Select a role on the left to view & edit its permissions.') }}");
        updateCounts(0);
        return;
      }

      var q = (document.getElementById('permissionsSearch')?.value || '').trim().toLowerCase();
      var filtered = allPermissions.filter(function (p) {
        if (!q) return true;
        return String(p.name || '').toLowerCase().indexOf(q) !== -1;
      });

      if (filtered.length === 0) {
        list.innerHTML = '';
        setEmptyState("{{ __('No permissions match your search.') }}");
        updateCounts(0);
        return;
      }

      setEmptyState('');
      updateCounts(filtered.length);

      var html = '';
      for (var i = 0; i < filtered.length; i++) {
        var p = filtered[i];
        var pid = Number(p.id);
        var checked = selectedPermissionIds.has(pid);
        html += '<div class="col-12 col-md-6">'
          + '<div class="form-check">'
          + '<input class="form-check-input" type="checkbox" id="perm_cb_' + pid + '" data-permission-id="' + pid + '" ' + (checked ? 'checked' : '') + (selectedRoleId ? '' : 'disabled') + '>'
          + '<label class="form-check-label" for="perm_cb_' + pid + '">' + escapeHtml(p.name || '') + '</label>'
          + '</div>'
          + '</div>';
      }
      list.innerHTML = html;
    }

    function fetchJson(url, opts) {
      opts = opts || {};
      opts.headers = Object.assign({
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }, opts.headers || {});

      return fetch(url, opts).then(function (res) {
        if (!res.ok) {
          return res.json().catch(function () { return null; }).then(function (data) {
            var msg = (data && data.message) ? data.message : ('Request failed (' + res.status + ')');
            throw new Error(msg);
          });
        }
        return res.json();
      });
    }

    function loadAllPermissions() {
      return fetchJson("{{ route('tenant.settings.roles.permissions.index', ['business' => $tenant->slug]) }}")
        .then(function (data) {
          allPermissions = (data && data.permissions) ? data.permissions : [];
        });
    }

    function loadRolePermissions(roleId) {
      setLoading(true);
      setSaveStatus('idle', '');
      return fetchJson("{{ route('tenant.settings.roles.permissions.show', ['business' => $tenant->slug, 'role' => 0]) }}".replace('/0/', '/' + roleId + '/'))
        .then(function (data) {
          var ids = (data && data.permission_ids) ? data.permission_ids : [];
          selectedPermissionIds = new Set(ids.map(function (v) { return Number(v); }));
        })
        .finally(function () {
          setLoading(false);
          renderPermissions();
        });
    }

    function runSync() {
      if (!selectedRoleId) return Promise.resolve();
      isSyncing = true;
      pendingSync = false;
      setSaveStatus('saving');
      var payload = {
        permission_ids: Array.from(selectedPermissionIds)
      };

      return fetchJson(
        "{{ route('tenant.settings.roles.permissions.sync', ['business' => $tenant->slug, 'role' => 0]) }}".replace('/0/', '/' + selectedRoleId + '/'),
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
          },
          body: JSON.stringify(payload)
        }
      ).then(function (data) {
        var ids = (data && data.permission_ids) ? data.permission_ids : [];
        selectedPermissionIds = new Set(ids.map(function (v) { return Number(v); }));
        setSaveStatus('saved');
      }).catch(function (e) {
        setSaveStatus('error', e && e.message ? e.message : "{{ __('Failed to save permissions.') }}");
        renderPermissions();
      }).finally(function () {
        isSyncing = false;
        if (pendingSync) {
          runSync();
        }
      });
    }

    function syncRolePermissions() {
      if (!selectedRoleId) return Promise.resolve();
      if (isSyncing) {
        pendingSync = true;
        return Promise.resolve();
      }
      return runSync();
    }

    function setSelectedRole(roleId, roleName) {
      selectedRoleId = Number(roleId);
      document.getElementById('selectedRoleName').setAttribute('data-role-name', roleName || '');
      updateHeader();

      return loadRolePermissions(selectedRoleId);
    }

    var dt = $table.DataTable({
      processing: true,
      serverSide: true,
      pageLength: 25,
      ajax: "{{ route('tenant.settings.roles.datatable', ['business' => $tenant->slug]) }}",
      order: [[1, 'asc']],
      columns: [
        { data: 'id', name: 'id', width: '90px' },
        { data: 'name', name: 'name' },
        { data: 'permissions_count', name: 'permissions_count', width: '180px', orderable: false, searchable: false },
        { data: 'users_count', name: 'users_count', width: '180px', orderable: false, searchable: false },
        { data: 'actions_display', name: 'actions_display', orderable: false, searchable: false, className: 'text-end', width: '220px' }
      ],
      rowCallback: function (row, data) {
        if (!row || !data) return;
        row.style.cursor = 'pointer';
        if (Number(data.id) === Number(selectedRoleId)) {
          row.classList.add('table-primary');
        } else {
          row.classList.remove('table-primary');
        }
      }
    });

    $table.on('click', 'tbody tr', function () {
      var rowData = dt.row(this).data();
      if (!rowData || !rowData.id) return;
      setSelectedRole(rowData.id, rowData.name || '');
      dt.rows().invalidate().draw(false);
    });

    $table.on('click', 'tbody tr a, tbody tr button, tbody tr form', function (e) {
      e.stopPropagation();
    });

    document.getElementById('permissionsSearch')?.addEventListener('input', function () {
      renderPermissions();
    });

    document.getElementById('permissionsList')?.addEventListener('change', function (e) {
      var target = e && e.target ? e.target : null;
      if (!target || !target.matches('input[type="checkbox"][data-permission-id]')) return;
      if (!selectedRoleId) return;

      var pid = Number(target.getAttribute('data-permission-id'));
      if (!pid) return;

      if (target.checked) {
        selectedPermissionIds.add(pid);
      } else {
        selectedPermissionIds.delete(pid);
      }

      syncRolePermissions();
    });

    function getFilteredPermissions() {
      var q = (document.getElementById('permissionsSearch')?.value || '').trim().toLowerCase();
      return allPermissions.filter(function (p) {
        if (!q) return true;
        return String(p.name || '').toLowerCase().indexOf(q) !== -1;
      });
    }

    document.getElementById('permissionsSelectAll')?.addEventListener('click', function () {
      if (!selectedRoleId) return;
      var filtered = getFilteredPermissions();
      filtered.forEach(function (p) {
        selectedPermissionIds.add(Number(p.id));
      });
      renderPermissions();
      syncRolePermissions();
    });

    document.getElementById('permissionsClearAll')?.addEventListener('click', function () {
      if (!selectedRoleId) return;
      var filtered = getFilteredPermissions();
      filtered.forEach(function (p) {
        selectedPermissionIds.delete(Number(p.id));
      });
      renderPermissions();
      syncRolePermissions();
    });

    loadAllPermissions().then(function () {
      renderPermissions();
    }).catch(function () {
      setEmptyState("{{ __('Failed to load permissions.') }}");
    });
  })();
</script>
@endpush

@section('content')
	<div class="container-fluid p-3">
		@if (session('status'))
			<div class="notice notice-success">
				<p>{{ (string) session('status') }}</p>
			</div>
		@endif

		@if ($errors->any())
			<div class="notice notice-error">
				<p>{{ __( 'Please fix the errors below.' ) }}</p>
			</div>
		@endif

		<div class="row g-3">
			<div class="col-12 col-xl-6">
				<x-settings.card :title="__('Roles')">
					<div class="d-flex justify-content-end">
						<a class="btn btn-primary" href="{{ route('tenant.settings.roles.create', ['business' => $tenant->slug]) }}">{{ __('Add Role') }}</a>
					</div>

					<div class="mt-3 table-responsive">
						<table class="table table-sm align-middle mb-0" id="rolesTable">
							<thead class="bg-light">
								<tr>
									<th style="width: 90px;">{{ __('ID') }}</th>
									<th>{{ __('Name') }}</th>
									<th style="width: 180px;">{{ __('Permissions') }}</th>
									<th style="width: 180px;">{{ __('Users') }}</th>
									<th class="text-end" style="width: 220px;">{{ __('Actions') }}</th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
				</x-settings.card>
			</div>

			<div class="col-12 col-xl-6">
				<x-settings.card>
					<x-slot name="title">
						<div class="d-flex align-items-center justify-content-between gap-2">
							<span id="selectedRoleName">{{ __('Select a role') }}</span>
							<span class="small text-muted d-none" id="selectedRoleMeta">
								<span>{{ __('Assigned') }}: <span id="selectedRoleAssignedCount">0</span></span>
								<span class="mx-2">·</span>
								<span>{{ __('Visible') }}: <span id="selectedRoleVisibleCount">0</span></span>
							</span>
						</div>
					</x-slot>

					<div id="permissionsPanelHeader">
						<div class="d-flex align-items-center gap-2">
							<div class="flex-grow-1">
								<input type="text" class="form-control" id="permissionsSearch" placeholder="{{ __('Search permissions...') }}" autocomplete="off">
							</div>
							<div class="btn-group" role="group" aria-label="{{ __('Permission bulk actions') }}">
								<button type="button" class="btn btn-outline-secondary" id="permissionsSelectAll" disabled>{{ __('Select all') }}</button>
								<button type="button" class="btn btn-outline-secondary" id="permissionsClearAll" disabled>{{ __('Clear') }}</button>
							</div>
						</div>
						<div class="d-flex align-items-center justify-content-between mt-2">
							<div class="text-muted small d-none" id="permissionsLoading">{{ __('Loading...') }}</div>
							<div class="text-muted small" id="permissionsSaveStatus"></div>
						</div>
						<div class="text-muted small mt-2" id="permissionsEmpty"></div>
					</div>

					<div id="permissionsPanelBody">
						<div class="row g-2" id="permissionsList"></div>
					</div>
				</x-settings.card>
			</div>
		</div>
	</div>
@endsection
