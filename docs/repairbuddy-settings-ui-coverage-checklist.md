# RepairBuddy Settings UI Coverage Checklist

Format:
`Setting -> Screen -> Section -> Field type`

## General
- `menu_name -> Settings -> General -> text`
- `business_name -> Settings -> General -> text`
- `business_phone -> Settings -> General -> text`
- `business_address -> Settings -> General -> text`
- `logo_url -> Settings -> General -> text (url)`
- `email -> Settings -> General -> email`
- `case_number_prefix -> Settings -> General -> text`
- `case_number_length -> Settings -> General -> number`
- `email_customer -> Settings -> General -> checkbox`
- `attach_pdf -> Settings -> General -> checkbox`
- `next_service_date_toggle -> Settings -> General -> checkbox`
- `gdpr_acceptance_text -> Settings -> General -> textarea`
- `gdpr_link_label -> Settings -> General -> text`
- `gdpr_link_url -> Settings -> General -> text (url)`
- `default_country -> Settings -> General -> text`
- `disable_parts_use_woo_products -> Settings -> General -> checkbox`
- `disable_status_check_by_serial -> Settings -> General -> checkbox`

## Currency
- `currency -> Settings -> Currency -> text`
- `currency_position -> Settings -> Currency -> select`
- `thousand_separator -> Settings -> Currency -> text`
- `decimal_separator -> Settings -> Currency -> text`
- `number_of_decimals -> Settings -> Currency -> number`

## Invoices & Reports
- `add_qr_code_to_invoice -> Settings -> Invoices & Reports -> checkbox`
- `invoice_footer_message -> Settings -> Invoices & Reports -> text`
- `invoice_print_type -> Settings -> Invoices & Reports -> select`
- `display_pickup_date -> Settings -> Invoices & Reports -> checkbox`
- `display_delivery_date -> Settings -> Invoices & Reports -> checkbox`
- `display_next_service_date -> Settings -> Invoices & Reports -> checkbox`
- `invoice_disclaimer_terms -> Settings -> Invoices & Reports -> textarea`
- `repair_order_type -> Settings -> Invoices & Reports -> select`
- `terms_url -> Settings -> Invoices & Reports -> text (url)`
- `repair_order_print_size -> Settings -> Invoices & Reports -> select`
- `display_business_address_details -> Settings -> Invoices & Reports -> checkbox`
- `display_customer_email_address_details -> Settings -> Invoices & Reports -> checkbox`
- `repair_order_footer_message -> Settings -> Invoices & Reports -> text`

## Job Statuses
- `job_statuses_table -> Settings -> Job Statuses -> table (CRUD UI, actions disabled)`
- `add_new_status -> Settings -> Job Statuses -> modal (submit disabled)`
- `status_considered_completed -> Settings -> Job Statuses -> select`
- `status_considered_cancelled -> Settings -> Job Statuses -> select`

## Payments
- `payment_statuses_table -> Settings -> Payments -> table (CRUD UI, actions disabled)`
- `payment_methods -> Settings -> Payments -> multi-checkbox`

## Reviews
- `request_feedback_by_sms -> Settings -> Reviews -> checkbox`
- `request_feedback_by_email -> Settings -> Reviews -> checkbox`
- `feedback_page_selection -> Settings -> Reviews -> select (mock)`
- `send_review_request_if_job_status_is -> Settings -> Reviews -> select`
- `auto_feedback_request_interval -> Settings -> Reviews -> number`
- `review_email_subject -> Settings -> Reviews -> text`
- `review_email_message_template -> Settings -> Reviews -> textarea`
- `review_sms_message_template -> Settings -> Reviews -> textarea`

## Estimates
- `estimate_email_subject_to_customer -> Settings -> Estimates -> text`
- `estimate_email_body_to_customer -> Settings -> Estimates -> textarea`
- `disable_estimates -> Settings -> Estimates -> checkbox`
- `booking_quote_forms_send_to_jobs -> Settings -> Estimates -> checkbox`
- `approve_reject_email_subject_to_admin -> Settings -> Estimates -> text`
- `approve_reject_email_body_to_admin -> Settings -> Estimates -> textarea`

## My Account
- `disable_booking -> Settings -> My Account -> checkbox`
- `disable_estimates -> Settings -> My Account -> checkbox`
- `disable_reviews -> Settings -> My Account -> checkbox`
- `booking_form_type -> Settings -> My Account -> select`

## Devices & Brands
- `enable_pin_code_field -> Settings -> Devices & Brands -> checkbox`
- `show_pin_code_in_invoices_emails_status_check -> Settings -> Devices & Brands -> checkbox`
- `use_woo_products_as_devices -> Settings -> Devices & Brands -> checkbox`
- `labels_note/pin/device/brand/type/imei -> Settings -> Devices & Brands -> text`
- `additional_device_fields -> Settings -> Devices & Brands -> table (repeater UI, actions disabled)`
- `pickup_delivery_toggle -> Settings -> Devices & Brands -> checkbox`
- `pickup_charge -> Settings -> Devices & Brands -> text/number`
- `delivery_charge -> Settings -> Devices & Brands -> text/number`
- `rental_toggle -> Settings -> Devices & Brands -> checkbox`
- `rental_per_day -> Settings -> Devices & Brands -> text/number`
- `rental_per_week -> Settings -> Devices & Brands -> text/number`

## Pages Setup
- `dashboard_page -> Settings -> Pages Setup -> select (mock)`
- `status_check_page -> Settings -> Pages Setup -> select (mock)`
- `feedback_page -> Settings -> Pages Setup -> select (mock)`
- `booking_page -> Settings -> Pages Setup -> select (mock)`
- `services_page -> Settings -> Pages Setup -> select (mock)`
- `parts_page -> Settings -> Pages Setup -> select (mock)`
- `redirect_after_login -> Settings -> Pages Setup -> select (mock)`
- `enable_registration -> Settings -> Pages Setup -> checkbox`

## SMS
- `activate_sms_for_selective_statuses -> Settings -> SMS -> checkbox`
- `gateway_selection -> Settings -> SMS -> select`
- `gateway_credentials -> Settings -> SMS -> text (conditional fields)`
- `send_when_status_changed_to -> Settings -> SMS -> multi-checkbox`
- `test_sms_number -> Settings -> SMS -> text`
- `test_sms_message -> Settings -> SMS -> text`

## Taxes
- `taxes_table -> Settings -> Taxes -> table (CRUD UI, actions disabled)`
- `enable_taxes -> Settings -> Taxes -> checkbox`
- `default_tax -> Settings -> Taxes -> select`
- `invoice_amounts_inclusive_exclusive -> Settings -> Taxes -> select`

## Service Settings
- `sidebar_description -> Settings -> Service Settings -> textarea`
- `disable_booking_on_service_page -> Settings -> Service Settings -> checkbox`
- `booking_form_type -> Settings -> Service Settings -> select`

## Time Logs
- `disable_time_log -> Settings -> Time Logs -> checkbox`
- `default_tax_for_hours -> Settings -> Time Logs -> select`
- `enable_time_log_for_statuses -> Settings -> Time Logs -> multi-checkbox`
- `activities -> Settings -> Time Logs -> textarea`

## Maintenance Reminders
- `reminders_table -> Settings -> Maintenance Reminders -> table (CRUD UI, actions disabled)`
- `add_reminder_modal -> Settings -> Maintenance Reminders -> modal (submit disabled)`
- `test_reminder_modal -> Settings -> Maintenance Reminders -> modal (submit disabled)`

## Styling & Labels
- `label_delivery/pickup/next_service/case_number -> Settings -> Styling & Labels -> text`
- `color_primary/secondary -> Settings -> Styling & Labels -> color + text`

## Signature Workflow
- `pickup_signature_enabled -> Settings -> Signature Workflow -> checkbox`
- `pickup_trigger_status -> Settings -> Signature Workflow -> select`
- `pickup_email_subject -> Settings -> Signature Workflow -> text`
- `pickup_email_template -> Settings -> Signature Workflow -> textarea`
- `pickup_sms_text -> Settings -> Signature Workflow -> textarea`
- `pickup_status_after_submission -> Settings -> Signature Workflow -> select`
- `delivery_signature_enabled -> Settings -> Signature Workflow -> checkbox`
- `delivery_trigger_status -> Settings -> Signature Workflow -> select`
- `delivery_email_subject -> Settings -> Signature Workflow -> text`
- `delivery_email_template -> Settings -> Signature Workflow -> textarea`
- `delivery_sms_text -> Settings -> Signature Workflow -> textarea`
- `delivery_status_after_submission -> Settings -> Signature Workflow -> select`

## Booking
- `booking_email_templates_customer/admin -> Settings -> Booking -> text/textarea`
- `send_booking_quote_to_jobs -> Settings -> Booking -> checkbox`
- `turn_off_other_device_brand -> Settings -> Booking -> checkbox`
- `turn_off_other_service -> Settings -> Booking -> checkbox`
- `turn_off_service_price -> Settings -> Booking -> checkbox`
- `turn_off_id_imei_in_booking -> Settings -> Booking -> checkbox`
- `default_type/brand/device -> Settings -> Booking -> text`
