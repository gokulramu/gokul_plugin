<?php
/*
Plugin Name: GOKUL PLUGIN
Description: Modular Employee Manager and Marketplace APIs with frontend login/corner and scalable structure.
Version: 1.0
Author: gokul
*/

if (!defined('ABSPATH')) exit;

if (!defined('GOKUL_PATH')) define('GOKUL_PATH', plugin_dir_path(__FILE__));
if (!defined('GOKUL_URL'))  define('GOKUL_URL',  plugin_dir_url(__FILE__));

// ---- Logging Helper ----
if (!function_exists('gokul_log_message')) {
    function gokul_log_message($message, $context = '') {
        $log_dir = GOKUL_PATH . 'logs/';
        if (!file_exists($log_dir)) mkdir($log_dir, 0755, true);
        $log_file = $log_dir . 'gokul_debug.log';
        $date = date('Y-m-d H:i:s');
        $msg = "[$date][$context] $message\n";
        file_put_contents($log_file, $msg, FILE_APPEND);
    }
}

// Start session early (only if session logic is required elsewhere)
function gokul_start_session() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
        gokul_log_message('Session started.', 'init');
    }
}
add_action('init', 'gokul_start_session', 1);

// --- Activation Hook: Create Table, Pages, and Roles ---
register_activation_hook(__FILE__, function() {
    error_log('GOKUL PLUGIN ACTIVATION HOOK RUNNING'); // Debug
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    gokul_log_message('Activation hook triggered, session started.', 'activation');

    // 1. Create the orders & products tables if they do not exist
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $products = $wpdb->prefix . 'gokul_products';
    $domestic = $wpdb->prefix . 'gokul_domestic_orders';
    $international = $wpdb->prefix . 'gokul_international_orders';

    $sql = "
        CREATE TABLE IF NOT EXISTS $products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sku VARCHAR(255) NOT NULL UNIQUE,
            title VARCHAR(255),
            gtin VARCHAR(50),
            status VARCHAR(50),
            lifecycle VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;

        CREATE TABLE IF NOT EXISTS $domestic (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id varchar(64) NOT NULL,
            customer_name varchar(255) NOT NULL,
            platform varchar(64) NOT NULL,
            order_date datetime NOT NULL,
            status varchar(64) NOT NULL,
            total decimal(10,2) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE IF NOT EXISTS $international (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(255) NOT NULL UNIQUE,
            customer_name VARCHAR(255),
            platform VARCHAR(50),
            order_date DATETIME,
            status VARCHAR(50),
            total DECIMAL(10,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    gokul_log_message("dbDelta run for orders/products tables", 'db');

    if ($wpdb->last_error) {
        error_log('GOKUL PLUGIN DB ERROR: ' . $wpdb->last_error);
        gokul_log_message('DB ERROR: ' . $wpdb->last_error, 'activation');
    } else {
        error_log('GOKUL PLUGIN TABLE CREATION SUCCESS');
        gokul_log_message('Table creation success', 'activation');
    }

    // 2. Create or update required pages
    $pages = [
        ['employee-login',    'Employee Login',    '[gokul_employee_login]'],
        ['employee-corner',   'Employee Corner',   '[gokul_employee_corner]'],
        ['domestic-orders',   'Domestic Orders',   '[gokul_domestic_orders]'],
        ['international-orders', 'International Orders', '[gokul_international_orders]'],
        ['products', 'Products', '[gokul_products]'],
    ];
    foreach ($pages as $page) {
        gokul_create_or_update_page($page[0], $page[1], $page[2]);
    }

    // 3. Create roles and capabilities
    gokul_log_message('Adding roles and admin caps.', 'activation');
    if (class_exists('Gokul_Employee')) {
        Gokul_Employee::add_role();
        Gokul_Employee::add_admin_cap();
        gokul_log_message('Roles and admin caps added.', 'activation');
    }
});
register_deactivation_hook(__FILE__, function() {
    if (class_exists('Gokul_Employee')) {
        Gokul_Employee::remove_role();
    }
});

/**
 * Create or update a page by slug and set its content.
 */
function gokul_create_or_update_page($slug, $title, $shortcode) {
    gokul_log_message("Attempting to create/update page: $slug", 'page');
    $page = get_page_by_path($slug);
    $page_data = [
        'post_title'   => $title,
        'post_content' => $shortcode,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_name'    => $slug
    ];

    if ($page) {
        $page_data['ID'] = $page->ID;
        wp_update_post($page_data);
        gokul_log_message("Page updated: $slug (ID: {$page->ID})", 'page');
    } else {
        $id = wp_insert_post($page_data);
        gokul_log_message("Page created: $slug (ID: $id)", 'page');
    }
}

// Register 'gokul_product' custom post type for products
function gokul_register_product_post_type() {
    register_post_type('gokul_product', array(
        'labels' => array(
            'name' => 'Products',
            'singular_name' => 'Product',
        ),
        'public' => true,
        'has_archive' => true,
        'show_in_menu' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-products',
    ));
}
add_action('init', 'gokul_register_product_post_type');

// Example: Add SKU as custom field (meta box)
function gokul_add_product_meta_boxes() {
    add_meta_box('gokul_product_sku', 'Product SKU', 'gokul_product_sku_callback', 'gokul_product', 'side');
}
add_action('add_meta_boxes', 'gokul_add_product_meta_boxes');

function gokul_product_sku_callback($post) {
    $sku = get_post_meta($post->ID, '_gokul_product_sku', true);
    echo '<input type="text" name="gokul_product_sku" value="' . esc_attr($sku) . '" />';
}

function gokul_save_product_meta($post_id) {
    if (array_key_exists('gokul_product_sku', $_POST)) {
        update_post_meta($post_id, '_gokul_product_sku', sanitize_text_field($_POST['gokul_product_sku']));
    }
}
add_action('save_post_gokul_product', 'gokul_save_product_meta');

// Load logger and main class files
require_once GOKUL_PATH . 'includes/logger.php';
require_once GOKUL_PATH . 'includes/class-employee.php';
require_once GOKUL_PATH . 'includes/employee-login-handler.php';
require_once GOKUL_PATH . 'includes/class-flipkart-api.php';
require_once GOKUL_PATH . 'includes/class-walmart-api.php';

// Load API handlers
foreach (glob(GOKUL_PATH . 'includes/api/*.php') as $api_file) {
    require_once $api_file;
}
require_once GOKUL_PATH . 'includes/shortcodes/domestic-orders-shortcode.php';

// Load admin pages
require_once GOKUL_PATH . 'admin/employee-manager.php';
require_once GOKUL_PATH . 'admin/marketplace-apis.php';
require_once GOKUL_PATH . 'admin/products.php';
require_once GOKUL_PATH . 'admin/orders.php';
require_once GOKUL_PATH . 'admin/flipkart-orders.php';
require_once GOKUL_PATH . 'admin/domestic-orders.php';
require_once GOKUL_PATH . 'admin/walmart-manual-fetch.php';

// Enqueue styles
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_style('gokul-admin-style', GOKUL_URL . 'assets/style.css');
});
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('gokul-frontend-style', GOKUL_URL . 'assets/style.css');
});

// --- Frontend Shortcodes ---
add_shortcode('gokul_employee_login', function() {
    ob_start();
    include GOKUL_PATH . 'templates/employee-login.php';
    return ob_get_clean();
});
add_shortcode('gokul_employee_corner', function() {
    ob_start();
    include GOKUL_PATH . 'templates/employee-corner.php';
    return ob_get_clean();
});
add_shortcode('gokul_domestic_orders', function() {
    ob_start();
    include GOKUL_PATH . 'templates/domestic-orders.php';
    return ob_get_clean();
});
add_shortcode('gokul_international_orders', function() {
    ob_start();
    include GOKUL_PATH . 'templates/international-orders.php';
    return ob_get_clean();
});
add_shortcode('gokul_products', function() {
    ob_start();
    include GOKUL_PATH . 'templates/products.php';
    return ob_get_clean();
});

// Admin bar filter
add_filter('show_admin_bar', function($show) {
    if (is_user_logged_in() && current_user_can('gokul_employee') && !current_user_can('administrator')) {
        return false;
    }
    return $show;
});

// Ensure early session start (redundant, but safe)
add_action('plugins_loaded', function() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
        gokul_log_message('Session started (plugins_loaded).', 'init');
    }
}, 1);

// Clear session on logout
add_action('wp_logout', function() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
        gokul_log_message('Session destroyed on logout.', 'logout');
    }
});

// Admin menu (all in one place, no duplication)
add_action('admin_menu', function() {
    add_menu_page('GOKUL PLUGIN', 'GOKUL PLUGIN', 'manage_options', 'gokul_plugin', function() {
        echo '<h1>GOKUL PLUGIN</h1>';
    }, 'dashicons-superhero-alt', 3);

    add_submenu_page('gokul_plugin', 'Employee Manager', 'Employee Manager', 'manage_options', 'gokul_employee_manager', 'gokul_employee_manager_admin');
    add_submenu_page('gokul_plugin', 'Marketplace APIs', 'Marketplace APIs', 'manage_options', 'gokul_marketplace_apis', 'gokul_marketplace_apis_admin');
    add_submenu_page('gokul_plugin', 'Products', 'Products', 'manage_options', 'gokul_products', 'gokul_products_admin');
    add_submenu_page('gokul_plugin', 'Orders', 'Orders', 'manage_options', 'gokul_orders', 'gokul_orders_admin');
    add_submenu_page('gokul_plugin', 'Flipkart Orders', 'Flipkart Orders', 'manage_options', 'gokul-flipkart-orders', 'gokul_flipkart_orders_admin');
    add_submenu_page('gokul_plugin', 'Domestic Orders', 'Domestic Orders', 'manage_options', 'gokul-domestic-orders', 'gokul_domestic_orders_admin');
    add_submenu_page(
        'gokul_plugin',
        'Walmart Products',
        'Walmart Products',
        'manage_options',
        'gokul_walmart_products',
        'gokul_walmart_products_admin'
    );
    // Debug page (admin only, never globally loaded)
    add_submenu_page(
        'gokul_plugin',
        'Walmart Debug Import',
        'Walmart Debug Import',
        'manage_options',
        'gokul_walmart_products',
        function() {
            include GOKUL_PATH . 'admin/walmart-import-single-debug.php';
        }
    );
});

// Example: in gokul-plugin.php or admin/products.php
$walmart_api = new Gokul_Plugin_Walmart_API($client_id, $client_secret, $type, $service_name, $partner_id);
$walmart_api->schedule_background_product_import($products);