<?php
    defined( 'ABSPATH' ) || exit;

    $_sitename      = get_bloginfo('name');
    $_thepagetitle  = $page_title . ' - ' . $_sitename;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $_thepagetitle ); ?></title>

    <!-- Inter Font from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <?php wp_head(); ?>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php echo wp_kses( wc_cr_add_js_fields_for_currency_formating(), $allowedHTML ); ?>