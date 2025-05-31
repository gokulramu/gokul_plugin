<?php
add_action('admin_menu', function(){
    // Top-level menu (if not already present)
    if (!isset($GLOBALS['admin_page_hooks']['gokul-plugin-main'])) {
        add_menu_page(
            'Gokul Plugin',
            'Gokul Plugin',
            'manage_options',
            'gokul-plugin-main',
            '', // No page, just for grouping
            'dashicons-admin-generic',
            2
        );
    }

    // Submenu: Walmart Products
    add_submenu_page(
        'gokul-plugin-main',
        'Walmart Products',
        'Walmart Products',
        'manage_options',
        'walmart-import-page',
        function(){ include __DIR__ . '/walmart-import-page.php'; }
    );

    // Add your other submenus here, e.g.:
    // add_submenu_page('gokul-plugin-main', ... Marketplace API ...);
    // add_submenu_page('gokul-plugin-main', ... Employee Manager ...);
});