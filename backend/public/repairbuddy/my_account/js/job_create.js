(function () {
    const config = window.RBJobCreateConfig || {
        enablePinCodeField: false,
        deviceLabelMap: {},
        translations: {}
    };

    var partsTable = document.getElementById('partsTable');
    var servicesTable = document.getElementById('servicesTable');
    var otherItemsTable = document.getElementById('otherItemsTable');
    var partsSelect = document.getElementById('parts_select');
    var devicePartsSelects = document.getElementById('devicePartsSelects');
    var deviceServicesSelects = document.getElementById('deviceServicesSelects');
    var servicesSelect = document.getElementById('services_select');
    var addServiceLineBtn = document.getElementById('addServiceLineBtn');
    var addOtherItemLineBtn = document.getElementById('addOtherItemLineBtn');

    var partRows = [];
    var serviceRows = [];
    var otherRows = [];

    function newRowId(prefix) {
        var p = (prefix || 'row');
        return p + '-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    function ensureOtherEmptyState() {
        if (!otherItemsTable) return;
        var tbody = otherItemsTable.querySelector('tbody');
        if (!tbody) return;
        var hasRows = tbody.querySelectorAll('tr:not(.other-empty-row)').length > 0;
        var emptyRow = tbody.querySelector('.other-empty-row');
        if (hasRows) {
            if (emptyRow) emptyRow.remove();
        } else {
            if (!emptyRow) {
                var tr = document.createElement('tr');
                tr.className = 'other-empty-row';
                tr.innerHTML = '<td colspan="7" class="text-center text-muted py-3">' + (config.translations.noOtherItems || 'No other items') + '</td>';
                tbody.appendChild(tr);
            }
        }
    }

    function initDevicePartsSelect2(selectEl, placeholderText) {
        if (!selectEl) return;
        if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
            return;
        }
        var $sel = window.jQuery(selectEl);
        if ($sel.hasClass('select2-hidden-accessible')) {
            return;
        }
        $sel.select2({
            width: '100%',
            theme: 'bootstrap-5',
            placeholder: placeholderText || 'Search and select...',
            allowClear: true
        });
    }

    function initDeviceServicesSelect2(selectEl, placeholderText) {
        if (!selectEl) return;
        if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
            return;
        }
        var $sel = window.jQuery(selectEl);
        if ($sel.hasClass('select2-hidden-accessible')) {
            return;
        }
        $sel.select2({
            width: '100%',
            theme: 'bootstrap-5',
            placeholder: placeholderText || 'Search and select...',
            allowClear: true
        });
    }

    function renderDeviceServicesSelects() {
        if (!deviceServicesSelects) return;
        var devices = getSelectedDeviceLabels();
        deviceServicesSelects.innerHTML = '';

        if (devices.length === 0) {
            return;
        }

        var optionsHtml = cloneServicesOptionsHtml();

        devices.forEach(function (d) {
            var col = document.createElement('div');
            col.className = 'col-md-6';

            var label = document.createElement('label');
            label.className = 'form-label';
            label.textContent = 'Service for ' + d.label;

            var sel = document.createElement('select');
            sel.className = 'form-select js-device-service-select';
            sel.dataset.deviceId = d.deviceId;
            sel.dataset.deviceLabel = d.label;
            sel.innerHTML = optionsHtml;

            col.appendChild(label);
            col.appendChild(sel);
            deviceServicesSelects.appendChild(col);

            initDeviceServicesSelect2(sel, 'Select services for ' + d.label);

            function handleServiceSelected() {
                var serviceName = '';
                if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                    serviceName = window.jQuery(sel).val() || '';
                } else {
                    serviceName = sel.value || '';
                }
                if (serviceName === '') return;

                var deviceIdForRow = d.deviceId ? String(d.deviceId) : '';

                serviceRows.push({
                    id: newRowId('service'),
                    name: serviceName,
                    code: '',
                    device_id: deviceIdForRow,
                    device: d.label,
                    qty: '1',
                    price: '0'
                });
                renderServiceRows();

                if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                    window.jQuery(sel).val(null).trigger('change');
                } else {
                    sel.value = '';
                }
            }

            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                window.jQuery(sel)
                    .off('change.rbDeviceService')
                    .on('change.rbDeviceService', handleServiceSelected)
                    .off('select2:select.rbDeviceService')
                    .on('select2:select.rbDeviceService', handleServiceSelected);
            } else {
                sel.addEventListener('change', handleServiceSelected);
            }
        });
    }

    function clonePartsOptionsHtml() {
        if (!partsSelect) return '';
        return partsSelect.innerHTML || '';
    }

    function cloneServicesOptionsHtml() {
        if (!servicesSelect) return '';
        return servicesSelect.innerHTML || '';
    }

    function getSelectedDeviceLabels() {
        var devicesTable = document.getElementById('devicesTable');
        if (!devicesTable) return [];
        var labels = [];
        devicesTable.querySelectorAll('tbody tr:not(.devices-empty-row)').forEach(function (tr) {
            var deviceId = tr.querySelector('input[name="job_device_customer_device_id[]"]')?.value || '';
            if (!deviceId) {
                deviceId = tr.querySelector('.device-label')?.dataset?.value || '';
            }
            if (!deviceId) return;
            var label = (config.deviceLabelMap && config.deviceLabelMap[deviceId]) ? config.deviceLabelMap[deviceId] : (tr.querySelector('.device-label')?.textContent || '');
            label = (label || '').trim();
            if (label === '' || label === '—') {
                label = config.translations.device || 'Device';
            }
            labels.push({ deviceId: deviceId, label: label });
        });
        return labels;
    }

    function buildDeviceOptionsHtml(selectedDeviceId) {
        var devices = getSelectedDeviceLabels();
        if (!Array.isArray(devices) || devices.length === 0) {
            return '<option value="">—</option>';
        }

        var selId = selectedDeviceId ? String(selectedDeviceId) : '';
        return devices
            .map(function (d) {
                var id = String(d.deviceId || '');
                var label = String(d.label || '');
                var selected = selId !== '' && id === selId ? ' selected' : '';
                return '<option value="' + id.replace(/"/g, '&quot;') + '"' + selected + '>' + label + '</option>';
            })
            .join('');
    }

    function buildDeviceOptionsHtmlAllowBlank(selectedDeviceId) {
        var devices = getSelectedDeviceLabels();
        var selId = selectedDeviceId ? String(selectedDeviceId) : '';
        var options = ['<option value=""' + (selId === '' ? ' selected' : '') + '>—</option>'];

        if (!Array.isArray(devices) || devices.length === 0) {
            return options.join('');
        }

        devices.forEach(function (d) {
            var id = String(d.deviceId || '');
            var label = String(d.label || '');
            var selected = selId !== '' && id === selId ? ' selected' : '';
            options.push('<option value="' + id.replace(/"/g, '&quot;') + '"' + selected + '>' + label + '</option>');
        });

        return options.join('');
    }

    function initRowDeviceSelect2(selectEl) {
        if (!selectEl) return;
        if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
            return;
        }
        var $sel = window.jQuery(selectEl);
        if ($sel.hasClass('select2-hidden-accessible')) {
            return;
        }
        $sel.select2({
            width: '100%',
            theme: 'bootstrap-5',
            placeholder: 'Select device...',
            allowClear: true
        });
    }

    function refreshRowDeviceSelectOptions(rootEl) {
        if (!rootEl) return;
        rootEl.querySelectorAll('select.js-part-device, select.js-service-device, select.js-other-device').forEach(function (sel) {
            var current = sel.value || '';
            var nextOptionsHtml = buildDeviceOptionsHtml(current);

            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                var $sel = window.jQuery(sel);

                if ($sel.hasClass('select2-hidden-accessible')) {
                    try {
                        $sel.select2('destroy');
                    } catch (e) {
                    }
                }

                sel.innerHTML = nextOptionsHtml;
                if (current) {
                    sel.value = current;
                }
                initRowDeviceSelect2(sel);

                if ($sel.hasClass('select2-hidden-accessible')) {
                    $sel.trigger('change');
                }
            } else {
                sel.innerHTML = nextOptionsHtml;
                if (current) {
                    sel.value = current;
                }
            }
        });
    }

    function renderDevicePartsSelects() {
        if (!devicePartsSelects) return;
        var devices = getSelectedDeviceLabels();
        devicePartsSelects.innerHTML = '';

        if (devices.length === 0) {
            return;
        }

        var optionsHtml = clonePartsOptionsHtml();

        devices.forEach(function (d, idx) {
            var col = document.createElement('div');
            col.className = 'col-md-6';

            var label = document.createElement('label');
            label.className = 'form-label';
            label.textContent = 'Part for ' + d.label;

            var sel = document.createElement('select');
            sel.className = 'form-select js-device-part-select';
            sel.dataset.deviceId = d.deviceId;
            sel.dataset.deviceLabel = d.label;
            sel.innerHTML = optionsHtml;

            col.appendChild(label);
            col.appendChild(sel);
            devicePartsSelects.appendChild(col);

            initDevicePartsSelect2(sel, 'Select parts for ' + d.label);

            function handlePartSelected() {
                var partName = '';
                if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                    partName = window.jQuery(sel).val() || '';
                } else {
                    partName = sel.value || '';
                }
                if (partName === '') return;

                var deviceIdForRow = d.deviceId ? String(d.deviceId) : '';

                partRows.push({
                    id: newRowId('part'),
                    name: partName,
                    code: '',
                    capacity: '',
                    device_id: deviceIdForRow,
                    device: d.label,
                    qty: '1',
                    price: '0'
                });
                renderPartRows();

                if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                    window.jQuery(sel).val(null).trigger('change');
                } else {
                    sel.value = '';
                }
            }

            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                window.jQuery(sel)
                    .off('change.rbDevicePart')
                    .on('change.rbDevicePart', handlePartSelected)
                    .off('select2:select.rbDevicePart')
                    .on('select2:select.rbDevicePart', handlePartSelected);
            } else {
                sel.addEventListener('change', handlePartSelected);
            }
        });
    }

    if (addOtherItemLineBtn) {
        addOtherItemLineBtn.addEventListener('click', function () {
            otherRows.push({
                id: newRowId('other'),
                name: '',
                code: '',
                device_id: '',
                device: '',
                qty: '1',
                price: '0'
            });
            renderOtherRows();
        });
    }

    function ensurePartsEmptyState() {
        if (!partsTable) return;
        var tbody = partsTable.querySelector('tbody');
        if (!tbody) return;
        var hasRows = tbody.querySelectorAll('tr:not(.parts-empty-row)').length > 0;
        var emptyRow = tbody.querySelector('.parts-empty-row');
        if (hasRows) {
            if (emptyRow) emptyRow.remove();
        } else {
            if (!emptyRow) {
                var tr = document.createElement('tr');
                tr.className = 'parts-empty-row';
                tr.innerHTML = '<td colspan="8" class="text-center text-muted py-3">' + (config.translations.noPartsSelected || 'No parts selected') + '</td>';
                tbody.appendChild(tr);
            }
        }
    }

    function formatCents(centsStr) {
        var n = parseInt(centsStr || '0', 10);
        if (!Number.isFinite(n)) n = 0;
        return String(n);
    }

    function normalizeQty(qtyStr) {
        var n = parseInt(qtyStr || '0', 10);
        if (!Number.isFinite(n) || n < 0) n = 0;
        return String(n);
    }

    function calcTotalCents(qtyStr, priceStr) {
        var q = parseInt(qtyStr || '0', 10);
        var p = parseInt(priceStr || '0', 10);
        if (!Number.isFinite(q)) q = 0;
        if (!Number.isFinite(p)) p = 0;
        return String(q * p);
    }

    function ensureServicesEmptyState() {
        if (!servicesTable) return;
        var tbody = servicesTable.querySelector('tbody');
        if (!tbody) return;
        var hasRows = tbody.querySelectorAll('tr:not(.services-empty-row)').length > 0;
        var emptyRow = tbody.querySelector('.services-empty-row');
        if (hasRows) {
            if (emptyRow) emptyRow.remove();
        } else {
            if (!emptyRow) {
                var tr = document.createElement('tr');
                tr.className = 'services-empty-row';
                tr.innerHTML = '<td colspan="7" class="text-center text-muted py-3">' + (config.translations.noServicesSelected || 'No services selected') + '</td>';
                tbody.appendChild(tr);
            }
        }
    }

    function renderServiceRows() {
        if (!servicesTable) return;
        var tbody = servicesTable.querySelector('tbody');
        if (!tbody) return;
        tbody.querySelectorAll('tr:not(.services-empty-row)').forEach(function (tr) { tr.remove(); });

        serviceRows.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.dataset.rowId = row.id;
            tr.innerHTML = ''
                + '<td class="js-service-name"></td>'
                + '<td class="js-service-code"></td>'
                + '<td><select class="form-select form-select-sm js-service-device" aria-label="Device">' + buildDeviceOptionsHtml(row.device_id) + '</select></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-service-qty" value="0" /></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-service-price" value="0" /></td>'
                + '<td class="service-total text-end"></td>'
                + '<td class="text-end">'
                + '  <button type="button" class="btn btn-outline-danger btn-sm removeService" aria-label="Remove"><i class="bi bi-trash"></i></button>'
                + '</td>';

            tr.querySelector('.js-service-name').textContent = row.name || '';
            tr.querySelector('.js-service-code').textContent = row.code || '';
            var devSel = tr.querySelector('.js-service-device');
            if (devSel) {
                var current = row.device_id ? String(row.device_id) : '';
                if (current !== '') {
                    devSel.value = current;
                } else {
                    var opt0 = devSel.querySelector('option');
                    if (opt0) {
                        devSel.value = opt0.value;
                    }
                }
            }
            tr.querySelector('.js-service-qty').value = normalizeQty(row.qty);
            tr.querySelector('.js-service-price').value = formatCents(row.price);
            tr.querySelector('.service-total').textContent = calcTotalCents(row.qty, row.price);
            tbody.appendChild(tr);
        });

        ensureServicesEmptyState();
    }

    function renderPartRows() {
        if (!partsTable) return;
        var tbody = partsTable.querySelector('tbody');
        if (!tbody) return;
        tbody.querySelectorAll('tr:not(.parts-empty-row)').forEach(function (tr) { tr.remove(); });

        partRows.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.dataset.rowId = row.id;
            tr.innerHTML = ''
                + '<td class="js-part-name"></td>'
                + '<td class="js-part-code"></td>'
                + '<td class="js-part-capacity"></td>'
                + '<td><select class="form-select form-select-sm js-part-device" aria-label="Device">' + buildDeviceOptionsHtml(row.device_id) + '</select></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-part-qty" value="0" /></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-part-price" value="0" /></td>'
                + '<td class="part-total text-end"></td>'
                + '<td class="text-end">'
                + '  <button type="button" class="btn btn-outline-danger btn-sm removePart" aria-label="Remove"><i class="bi bi-trash"></i></button>'
                + '</td>';

            tr.querySelector('.js-part-name').textContent = row.name || '';
            tr.querySelector('.js-part-code').textContent = row.code || '';
            tr.querySelector('.js-part-capacity').textContent = row.capacity || '';
            var devSel = tr.querySelector('.js-part-device');
            if (devSel) {
                var current = row.device_id ? String(row.device_id) : '';
                if (current !== '') {
                    devSel.value = current;
                } else {
                    var opt0 = devSel.querySelector('option');
                    if (opt0) {
                        devSel.value = opt0.value;
                    }
                }
            }
            tr.querySelector('.js-part-qty').value = normalizeQty(row.qty);
            tr.querySelector('.js-part-price').value = formatCents(row.price);
            tr.querySelector('.part-total').textContent = calcTotalCents(row.qty, row.price);
            tbody.appendChild(tr);
        });

        ensurePartsEmptyState();
    }

    function renderOtherRows() {
        if (!otherItemsTable) return;
        var tbody = otherItemsTable.querySelector('tbody');
        if (!tbody) return;
        tbody.querySelectorAll('tr:not(.other-empty-row)').forEach(function (tr) { tr.remove(); });

        otherRows.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.dataset.rowId = row.id;
            tr.innerHTML = ''
                + '<td><input type="text" class="form-control form-control-sm js-other-name" value="" /></td>'
                + '<td><input type="text" class="form-control form-control-sm js-other-code" value="" /></td>'
                + '<td><select class="form-select form-select-sm js-other-device" aria-label="Device">' + buildDeviceOptionsHtmlAllowBlank(row.device_id) + '</select></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-other-qty" value="0" /></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-other-price" value="0" /></td>'
                + '<td class="other-total text-end"></td>'
                + '<td class="text-end">'
                + '  <button type="button" class="btn btn-outline-danger btn-sm removeOther" aria-label="Remove"><i class="bi bi-trash"></i></button>'
                + '</td>';

            tr.querySelector('.js-other-name').value = row.name || '';
            tr.querySelector('.js-other-code').value = row.code || '';
            var devSel = tr.querySelector('.js-other-device');
            if (devSel) {
                var current = row.device_id ? String(row.device_id) : '';
                devSel.value = current;
                initRowDeviceSelect2(devSel);
            }
            tr.querySelector('.js-other-qty').value = normalizeQty(row.qty);
            tr.querySelector('.js-other-price').value = formatCents(row.price);
            tr.querySelector('.other-total').textContent = calcTotalCents(row.qty, row.price);
            tbody.appendChild(tr);
        });

        ensureOtherEmptyState();
    }

    // Devices & Modal
    var deviceAddBtn = document.getElementById('addDeviceLine');
    var devicesTable = document.getElementById('devicesTable');
    var deviceModalEl = document.getElementById('deviceModal');
    var deviceModal = null;
    var deviceModalDevice = document.getElementById('device_modal_device');
    var deviceModalImei = document.getElementById('device_modal_imei');
    var deviceModalNote = document.getElementById('device_modal_note');
    var deviceModalSave = document.getElementById('deviceModalSave');
    var deviceModalPassword = document.getElementById('device_modal_password');
    var editingDeviceRow = null;

    function ensureDeviceModal() {
        if (!deviceModalEl || !window.bootstrap || !window.bootstrap.Modal) return null;
        if (!deviceModal) deviceModal = new window.bootstrap.Modal(deviceModalEl);
        return deviceModal;
    }

    function initDeviceModalSelect2() {
        if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') return;
        if (!deviceModalEl || !deviceModalDevice) return;
        var $sel = window.jQuery(deviceModalDevice);
        if ($sel.hasClass('select2-hidden-accessible')) return;
        $sel.select2({
            width: '100%',
            theme: 'bootstrap-5',
            dropdownParent: window.jQuery(deviceModalEl),
            placeholder: config.translations.selectDevice || 'Select Device',
            allowClear: true
        });
    }

    function resetDeviceModal() {
        if (deviceModalDevice) deviceModalDevice.value = '';
        if (deviceModalImei) deviceModalImei.value = '';
        if (deviceModalNote) deviceModalNote.value = '';
        if (deviceModalPassword) deviceModalPassword.value = '';
        editingDeviceRow = null;
    }

    function openDeviceModalForAdd() {
        resetDeviceModal();
        var m = ensureDeviceModal();
        initDeviceModalSelect2();
        if (m) m.show();
    }

    function openDeviceModalForEdit(tr) {
        if (!tr) return;
        editingDeviceRow = tr;
        initDeviceModalSelect2();
        var devId = tr.querySelector('input[name="job_device_customer_device_id[]"]')?.value || '';
        var imeiVal = tr.querySelector('input[name="job_device_serial[]"]')?.value || '';
        var noteVal = tr.querySelector('input[name="job_device_notes[]"]')?.value || '';
        var pwdVal = config.enablePinCodeField ? (tr.querySelector('input[name="job_device_pin[]"]')?.value || '') : '';

        if (deviceModalDevice) {
            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                window.jQuery(deviceModalDevice).val(devId || '').trigger('change');
            } else {
                deviceModalDevice.value = devId;
            }
        }
        if (deviceModalImei) deviceModalImei.value = imeiVal;
        if (deviceModalNote) deviceModalNote.value = noteVal;
        if (deviceModalPassword) deviceModalPassword.value = pwdVal;
        var m = ensureDeviceModal();
        if (m) m.show();
    }

    function renderDeviceRowSummary(tr) {
        if (!tr) return;
        var devId = tr.querySelector('input[name="job_device_customer_device_id[]"]')?.value || '';
        var imeiVal = tr.querySelector('input[name="job_device_serial[]"]')?.value || '';
        var noteVal = tr.querySelector('input[name="job_device_notes[]"]')?.value || '';
        var pwdVal = config.enablePinCodeField ? (tr.querySelector('input[name="job_device_pin[]"]')?.value || '') : '';

        var labelCell = tr.querySelector('.device-label');
        var imeiCell = tr.querySelector('.device-imei');
        var noteCell = tr.querySelector('.device-note');
        var pwdCell = tr.querySelector('.device-password');

        var labelText = devId && config.deviceLabelMap[devId] ? config.deviceLabelMap[devId] : '';
        if (labelCell) {
            labelCell.dataset.value = devId;
            labelCell.textContent = labelText !== '' ? labelText : '—';
        }
        if (imeiCell) {
            imeiCell.dataset.value = imeiVal;
            imeiCell.textContent = imeiVal !== '' ? imeiVal : '—';
        }
        if (pwdCell) {
            pwdCell.dataset.value = pwdVal;
            pwdCell.textContent = pwdVal !== '' ? pwdVal : '—';
        }
        if (noteCell) {
            noteCell.dataset.value = noteVal;
            var span = noteCell.querySelector('span');
            if (span) {
                span.textContent = noteVal !== '' ? noteVal : '—';
                span.title = noteVal;
            } else {
                noteCell.textContent = noteVal !== '' ? noteVal : '—';
            }
        }
    }

    function buildDeviceRow(values) {
        var tr = document.createElement('tr');
        tr.innerHTML = ''
            + '<td class="device-label" data-value=""></td>'
            + '<td class="device-imei" data-value=""></td>'
            + (config.enablePinCodeField ? '<td class="device-password" data-value=""></td>' : '')
            + '<td class="device-note" data-value=""><span class="d-inline-block text-truncate" style="max-width: 420px;"></span></td>'
            + '<td class="text-end">'
            + '  <button type="button" class="btn btn-outline-primary btn-sm editDeviceLine" aria-label="Edit"><i class="bi bi-pencil"></i></button>'
            + '  <button type="button" class="btn btn-outline-danger btn-sm removeDeviceLine" aria-label="Remove"><i class="bi bi-trash"></i></button>'
            + '  <input type="hidden" name="job_device_customer_device_id[]" value="" />'
            + '  <input type="hidden" name="job_device_serial[]" value="" />'
            + (config.enablePinCodeField ? '  <input type="hidden" name="job_device_pin[]" value="" />' : '')
            + '  <input type="hidden" name="job_device_notes[]" value="" />'
            + '</td>';

        tr.querySelector('input[name="job_device_customer_device_id[]"]').value = values.deviceId || '';
        tr.querySelector('input[name="job_device_serial[]"]').value = values.imei || '';
        tr.querySelector('input[name="job_device_notes[]"]').value = values.note || '';
        if (config.enablePinCodeField) {
            tr.querySelector('input[name="job_device_pin[]"]').value = values.password || '';
        }
        renderDeviceRowSummary(tr);
        return tr;
    }

    function ensureDevicesEmptyState() {
        if (!devicesTable) return;
        var tbody = devicesTable.querySelector('tbody');
        if (!tbody) return;
        var hasRealRows = tbody.querySelectorAll('tr:not(.devices-empty-row)').length > 0;
        var emptyRow = tbody.querySelector('.devices-empty-row');
        if (hasRealRows) {
            if (emptyRow) emptyRow.remove();
        } else {
            if (!emptyRow) {
                var tr = document.createElement('tr');
                tr.className = 'devices-empty-row';
                tr.innerHTML = '<td colspan="' + (config.enablePinCodeField ? 5 : 4) + '" class="text-center text-muted py-4">No devices added yet.</td>';
                tbody.appendChild(tr);
            }
        }
    }

    function saveDeviceModal() {
        if (!devicesTable) return;
        var tbody = devicesTable.querySelector('tbody');
        if (!tbody) return;

        var values = {
            deviceId: (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function' && deviceModalDevice)
                ? (window.jQuery(deviceModalDevice).val() || '')
                : (deviceModalDevice ? deviceModalDevice.value : ''),
            imei: deviceModalImei ? deviceModalImei.value : '',
            note: deviceModalNote ? deviceModalNote.value : '',
            password: deviceModalPassword ? deviceModalPassword.value : ''
        };

        var targetRow = editingDeviceRow;
        if (!targetRow) {
            var emptyRow = tbody.querySelector('.devices-empty-row');
            if (emptyRow) emptyRow.remove();
            targetRow = buildDeviceRow(values);
            tbody.appendChild(targetRow);
            ensureDevicesEmptyState();
        } else {
            targetRow.querySelector('input[name="job_device_customer_device_id[]"]').value = values.deviceId;
            targetRow.querySelector('input[name="job_device_serial[]"]').value = values.imei;
            targetRow.querySelector('input[name="job_device_notes[]"]').value = values.note;
            if (config.enablePinCodeField) {
                targetRow.querySelector('input[name="job_device_pin[]"]').value = values.password;
            }
            renderDeviceRowSummary(targetRow);
        }

        editingDeviceRow = null;
        ensureDevicesEmptyState();
        renderDevicePartsSelects();
        renderDeviceServicesSelects();
        renderPartRows();
        renderServiceRows();
        renderOtherRows();
        refreshRowDeviceSelectOptions(document);
        var m = ensureDeviceModal();
        if (m) m.hide();
    }

    // Extra Fields & Modal
    var extraAddBtn = document.getElementById('addExtraLine');
    var extraTable = document.getElementById('extraTable');
    var extraModalEl = document.getElementById('extraModal');
    var extraModal = null;
    var extraModalDate = document.getElementById('extra_modal_date');
    var extraModalLabel = document.getElementById('extra_modal_label');
    var extraModalData = document.getElementById('extra_modal_data');
    var extraModalDesc = document.getElementById('extra_modal_desc');
    var extraModalVis = document.getElementById('extra_modal_vis');
    var extraModalFile = document.getElementById('extra_modal_file');
    var extraModalSave = document.getElementById('extraModalSave');
    var editingExtraRow = null;

    function ensureExtraModal() {
        if (!extraModalEl || !window.bootstrap || !window.bootstrap.Modal) return null;
        if (!extraModal) extraModal = new window.bootstrap.Modal(extraModalEl);
        return extraModal;
    }

    function truncateText(str, max) {
        if (typeof str !== 'string') return '';
        if (str.length <= max) return str;
        return str.slice(0, max - 1) + '…';
    }

    function resetExtraModal() {
        if (extraModalDate) extraModalDate.value = '';
        if (extraModalLabel) extraModalLabel.value = '';
        if (extraModalData) extraModalData.value = '';
        if (extraModalDesc) extraModalDesc.value = '';
        if (extraModalVis) extraModalVis.value = 'private';
        if (extraModalFile) extraModalFile.value = '';
        editingExtraRow = null;
    }

    function openExtraModalForAdd() {
        resetExtraModal();
        var m = ensureExtraModal();
        if (m) m.show();
    }

    function openExtraModalForEdit(tr) {
        if (!tr) return;
        editingExtraRow = tr;
        var dateVal = tr.querySelector('input[name="extra_item_occurred_at[]"]')?.value || '';
        var labelVal = tr.querySelector('input[name="extra_item_label[]"]')?.value || '';
        var dataVal = tr.querySelector('input[name="extra_item_data_text[]"]')?.value || '';
        var descVal = tr.querySelector('input[name="extra_item_description[]"]')?.value || '';
        var visVal = tr.querySelector('input[name="extra_item_visibility[]"]')?.value || 'private';

        if (extraModalDate) extraModalDate.value = dateVal;
        if (extraModalLabel) extraModalLabel.value = labelVal;
        if (extraModalData) extraModalData.value = dataVal;
        if (extraModalDesc) extraModalDesc.value = descVal;
        if (extraModalVis) extraModalVis.value = visVal;
        if (extraModalFile) extraModalFile.value = '';
        var m = ensureExtraModal();
        if (m) m.show();
    }

    function renderExtraRowSummary(tr) {
        if (!tr) return;
        var dateVal = tr.querySelector('input[name="extra_item_occurred_at[]"]')?.value || '';
        var labelVal = tr.querySelector('input[name="extra_item_label[]"]')?.value || '';
        var dataVal = tr.querySelector('input[name="extra_item_data_text[]"]')?.value || '';
        var descVal = tr.querySelector('input[name="extra_item_description[]"]')?.value || '';
        var visVal = tr.querySelector('input[name="extra_item_visibility[]"]')?.value || 'private';

        var dateCell = tr.querySelector('.extra-date');
        var labelCell = tr.querySelector('.extra-label');
        var dataCell = tr.querySelector('.extra-data');
        var visCell = tr.querySelector('.extra-vis');

        if (dateCell) {
            dateCell.dataset.value = dateVal;
            dateCell.textContent = dateVal !== '' ? dateVal : '—';
        }
        if (labelCell) {
            labelCell.dataset.value = labelVal;
            labelCell.textContent = labelVal !== '' ? labelVal : '—';
        }
        if (dataCell) {
            dataCell.dataset.value = dataVal;
            dataCell.dataset.desc = descVal;
            var span = dataCell.querySelector('span');
            if (span) {
                span.textContent = dataVal !== '' ? truncateText(dataVal, 120) : '—';
                span.title = dataVal;
            }
        }
        if (visCell) {
            visCell.dataset.value = visVal;
            visCell.textContent = visVal === 'public' ? (config.translations.public || 'Public') : (config.translations.private || 'Private');
        }
    }

    function ensureExtraRowFileInput(tr) {
        if (!tr) return;
        var existing = tr.querySelector('input[name="extra_item_file[]"]');
        if (existing) return existing;
        var input = document.createElement('input');
        input.type = 'file';
        input.name = 'extra_item_file[]';
        input.className = 'd-none';
        tr.appendChild(input);
        return input;
    }

    function updateExtraFileCell(tr, fileName) {
        var cell = tr.querySelector('.extra-file');
        if (!cell) return;
        cell.dataset.value = fileName || '';
        cell.textContent = fileName ? fileName : '—';
        cell.title = fileName || '';
    }

    function buildExtraRow(values) {
        var tr = document.createElement('tr');
        tr.innerHTML = ''
            + '<td class="extra-date" data-value=""></td>'
            + '<td class="extra-label" data-value=""></td>'
            + '<td class="extra-data" data-value="" data-desc=""><span class="d-inline-block text-truncate" style="max-width: 420px;"></span></td>'
            + '<td class="extra-vis" data-value=""></td>'
            + '<td class="extra-file" data-value="">—</td>'
            + '<td class="text-end">'
            + '  <button type="button" class="btn btn-outline-primary btn-sm editExtraLine" aria-label="Edit"><i class="bi bi-pencil"></i></button>'
            + '  <button type="button" class="btn btn-outline-danger btn-sm removeExtraLine" aria-label="Remove"><i class="bi bi-trash"></i></button>'
            + '  <input type="hidden" name="extra_item_occurred_at[]" value="" />'
            + '  <input type="hidden" name="extra_item_label[]" value="" />'
            + '  <input type="hidden" name="extra_item_data_text[]" value="" />'
            + '  <input type="hidden" name="extra_item_description[]" value="" />'
            + '  <input type="hidden" name="extra_item_visibility[]" value="private" />'
            + '</td>';

        tr.querySelector('input[name="extra_item_occurred_at[]"]').value = values.date || '';
        tr.querySelector('input[name="extra_item_label[]"]').value = values.label || '';
        tr.querySelector('input[name="extra_item_data_text[]"]').value = values.data || '';
        tr.querySelector('input[name="extra_item_description[]"]').value = values.desc || '';
        tr.querySelector('input[name="extra_item_visibility[]"]').value = values.vis || 'private';

        renderExtraRowSummary(tr);
        updateExtraFileCell(tr, values.fileName || '');
        return tr;
    }

    function ensureExtrasEmptyState() {
        if (!extraTable) return;
        var tbody = extraTable.querySelector('tbody');
        if (!tbody) return;
        var hasRealRows = tbody.querySelectorAll('tr:not(.extras-empty-row)').length > 0;
        var emptyRow = tbody.querySelector('.extras-empty-row');
        if (hasRealRows) {
            if (emptyRow) emptyRow.remove();
        } else {
            if (!emptyRow) {
                var tr = document.createElement('tr');
                tr.className = 'extras-empty-row';
                tr.innerHTML = '<td colspan="6" class="text-center text-muted py-4">No extra fields added yet.</td>';
                tbody.appendChild(tr);
            }
        }
    }

    function saveExtraModal() {
        if (!extraTable) return;
        var tbody = extraTable.querySelector('tbody');
        if (!tbody) return;

        var values = {
            date: extraModalDate ? extraModalDate.value : '',
            label: extraModalLabel ? extraModalLabel.value : '',
            data: extraModalData ? extraModalData.value : '',
            desc: extraModalDesc ? extraModalDesc.value : '',
            vis: extraModalVis ? extraModalVis.value : 'private',
            fileName: ''
        };

        var targetRow = editingExtraRow;
        if (!targetRow) {
            targetRow = buildExtraRow(values);
            tbody.appendChild(targetRow);
            ensureExtrasEmptyState();
        } else {
            targetRow.querySelector('input[name="extra_item_occurred_at[]"]').value = values.date;
            targetRow.querySelector('input[name="extra_item_label[]"]').value = values.label;
            targetRow.querySelector('input[name="extra_item_data_text[]"]').value = values.data;
            targetRow.querySelector('input[name="extra_item_description[]"]').value = values.desc;
            targetRow.querySelector('input[name="extra_item_visibility[]"]').value = values.vis;
            renderExtraRowSummary(targetRow);
        }

        var selectedFile = extraModalFile && extraModalFile.files && extraModalFile.files[0] ? extraModalFile.files[0] : null;
        if (selectedFile) {
            var rowFileInput = ensureExtraRowFileInput(targetRow);
            try { rowFileInput.files = extraModalFile.files; } catch (e) { }
            updateExtraFileCell(targetRow, selectedFile.name);
        }

        editingExtraRow = null;
        ensureExtrasEmptyState();
        var m = ensureExtraModal();
        if (m) m.hide();
    }

    // Event listeners for Items
    if (partsTable) {
        partsTable.addEventListener('input', function (e) {
            var tr = e.target.closest('tr');
            if (!tr) return;
            var rowId = tr.dataset.rowId || '';
            if (!rowId) return;
            var row = partRows.find(function (r) { return r.id === rowId; });
            if (!row) return;
            var devSel = tr.querySelector('.js-part-device');
            if (devSel) {
                var devId = devSel.value || '';
                row.device_id = devId;
                if (devId && config.deviceLabelMap && config.deviceLabelMap[devId]) {
                    row.device = config.deviceLabelMap[devId];
                } else {
                    var opt = devSel.options && devSel.selectedIndex >= 0 ? devSel.options[devSel.selectedIndex] : null;
                    row.device = opt ? (opt.textContent || '') : '';
                }
            }
            row.qty = normalizeQty(tr.querySelector('.js-part-qty')?.value || '0');
            row.price = formatCents(tr.querySelector('.js-part-price')?.value || '0');
            var totalCell = tr.querySelector('.part-total');
            if (totalCell) totalCell.textContent = calcTotalCents(row.qty, row.price);
        });

        partsTable.addEventListener('click', function (e) {
            var rm = e.target.closest('.removePart');
            if (!rm) return;
            var tr = rm.closest('tr');
            var rowId = tr ? tr.dataset.rowId : '';
            if (!rowId) return;
            partRows = partRows.filter(function (r) { return r.id !== rowId; });
            renderPartRows();
        });
    }

    if (servicesTable) {
        servicesTable.addEventListener('input', function (e) {
            var tr = e.target.closest('tr');
            if (!tr) return;
            var rowId = tr.dataset.rowId || '';
            if (!rowId) return;
            var row = serviceRows.find(function (r) { return r.id === rowId; });
            if (!row) return;
            var devSel = tr.querySelector('.js-service-device');
            if (devSel) {
                var devId = devSel.value || '';
                row.device_id = devId;
                if (devId && config.deviceLabelMap && config.deviceLabelMap[devId]) {
                    row.device = config.deviceLabelMap[devId];
                } else {
                    var opt = devSel.options && devSel.selectedIndex >= 0 ? devSel.options[devSel.selectedIndex] : null;
                    row.device = opt ? (opt.textContent || '') : '';
                }
            }
            row.qty = normalizeQty(tr.querySelector('.js-service-qty')?.value || '0');
            row.price = formatCents(tr.querySelector('.js-service-price')?.value || '0');
            var totalCell = tr.querySelector('.service-total');
            if (totalCell) totalCell.textContent = calcTotalCents(row.qty, row.price);
        });

        servicesTable.addEventListener('click', function (e) {
            var rm = e.target.closest('.removeService');
            if (!rm) return;
            var tr = rm.closest('tr');
            var rowId = tr ? tr.dataset.rowId : '';
            if (!rowId) return;
            serviceRows = serviceRows.filter(function (r) { return r.id !== rowId; });
            renderServiceRows();
        });
    }

    if (otherItemsTable) {
        otherItemsTable.addEventListener('input', function (e) {
            var tr = e.target.closest('tr');
            if (!tr) return;
            var rowId = tr.dataset.rowId || '';
            if (!rowId) return;
            var row = otherRows.find(function (r) { return r.id === rowId; });
            if (!row) return;
            row.name = tr.querySelector('.js-other-name')?.value || '';
            row.code = tr.querySelector('.js-other-code')?.value || '';
            var devSel = tr.querySelector('.js-other-device');
            if (devSel) {
                var devId = devSel.value || '';
                row.device_id = devId;
                if (devId && config.deviceLabelMap && config.deviceLabelMap[devId]) {
                    row.device = config.deviceLabelMap[devId];
                } else {
                    var opt = devSel.options && devSel.selectedIndex >= 0 ? devSel.options[devSel.selectedIndex] : null;
                    row.device = opt ? (opt.textContent || '') : '';
                }
            }
            row.qty = normalizeQty(tr.querySelector('.js-other-qty')?.value || '0');
            row.price = formatCents(tr.querySelector('.js-other-price')?.value || '0');
            var totalCell = tr.querySelector('.other-total');
            if (totalCell) totalCell.textContent = calcTotalCents(row.qty, row.price);
        });

        otherItemsTable.addEventListener('click', function (e) {
            var rm = e.target.closest('.removeOther');
            if (!rm) return;
            var tr = rm.closest('tr');
            var rowId = tr ? tr.dataset.rowId : '';
            if (!rowId) return;
            otherRows = otherRows.filter(function (r) { return r.id !== rowId; });
            renderOtherRows();
        });
    }

    // Devices & Extra Field Event Listeners
    if (deviceAddBtn) deviceAddBtn.addEventListener('click', openDeviceModalForAdd);
    if (deviceModalSave) deviceModalSave.addEventListener('click', saveDeviceModal);
    if (devicesTable) {
        devicesTable.addEventListener('click', function (e) {
            var editBtn = e.target.closest('.editDeviceLine');
            if (editBtn) { openDeviceModalForEdit(editBtn.closest('tr')); return; }
            var rmBtn = e.target.closest('.removeDeviceLine');
            if (rmBtn) {
                var tr = rmBtn.closest('tr');
                if (tr) {
                    tr.remove();
                    ensureDevicesEmptyState();
                    renderDevicePartsSelects();
                    renderDeviceServicesSelects();
                    renderPartRows();
                    renderServiceRows();
                    renderOtherRows();
                    refreshRowDeviceSelectOptions(document);
                }
            }
        });
    }

    if (extraAddBtn) extraAddBtn.addEventListener('click', openExtraModalForAdd);
    if (extraModalSave) extraModalSave.addEventListener('click', saveExtraModal);
    if (extraTable) {
        extraTable.addEventListener('click', function (e) {
            var editBtn = e.target.closest('.editExtraLine');
            if (editBtn) { openExtraModalForEdit(editBtn.closest('tr')); return; }
            var rmBtn = e.target.closest('.removeExtraLine');
            if (rmBtn) {
                var tr = rmBtn.closest('tr');
                if (tr) tr.remove();
            }
        });
    }

    // Quick Connect Modals
    (function () {
        window.RBQuickCustomerModal = window.RBQuickCustomerModal || {};
        window.RBQuickCustomerModal.createUrl = config.quickCustomerCreateUrl;
        window.RBQuickCustomerModal.targetSelectId = 'customer_id';

        window.RBQuickTechnicianModal = window.RBQuickTechnicianModal || {};
        window.RBQuickTechnicianModal.createUrl = config.quickTechnicianCreateUrl;
        window.RBQuickTechnicianModal.targetSelectId = 'technician_ids';

        var openCustBtn = document.getElementById('rb_open_quick_customer');
        if (openCustBtn) {
            openCustBtn.addEventListener('click', function () {
                if (window.RBQuickCustomerModal && typeof window.RBQuickCustomerModal.open === 'function') {
                    window.RBQuickCustomerModal.open({});
                }
            });
        }

        var openTechBtn = document.getElementById('rb_open_quick_technician');
        if (openTechBtn) {
            openTechBtn.addEventListener('click', function () {
                if (window.RBQuickTechnicianModal && typeof window.RBQuickTechnicianModal.open === 'function') {
                    window.RBQuickTechnicianModal.open({});
                }
            });
        }
    })();

    // Initializations
    function initSelect2() {
        if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
            setTimeout(initSelect2, 50);
            return;
        }

        window.jQuery(function () {
            var $customer = window.jQuery('#customer_id');
            var $techs = window.jQuery('#technician_ids');

            function initOnce($el, opts) {
                if (!$el || !$el.length) return;
                if ($el.hasClass('select2-hidden-accessible')) return;
                $el.select2(opts);
            }

            initOnce($customer, {
                width: '100%',
                theme: 'bootstrap-5',
                placeholder: config.translations.select || 'Select...',
                allowClear: true
            });

            initOnce($techs, {
                width: '100%',
                theme: 'bootstrap-5',
                placeholder: config.translations.select || 'Select...'
            });
        });
    }

    // Run Initial Renders
    ensureDevicesEmptyState();
    renderPartRows();
    renderServiceRows();
    renderOtherRows();
    renderDevicePartsSelects();
    renderDeviceServicesSelects();
    initSelect2();

})();
