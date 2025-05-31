<?php
if (!current_user_can('manage_options')) return;

// Don't require logger here, we use on-page debug log
// require_once plugin_dir_path(dirname(__FILE__)) . '/inc/gokul-walmart-logger.php';

$debug_log = [];
function gokul_debug_log($message, $data = null) {
    global $debug_log;
    $entry = '[' . date('H:i:s') . '] ' . $message;
    if ($data !== null) {
        $entry .= ' | ' . print_r($data, true);
    }
    $debug_log[] = $entry;
}
gokul_debug_log('Raw Walmart API product:', $api_product);
// --- LOG: Script execution started ---
gokul_debug_log('Script Start: walmart-import-page.php loaded.');

global $wpdb;
$table = $wpdb->prefix . 'gokul_products';

// --- LOG: Table name ---
gokul_debug_log('Using products table', $table);

$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// --- LOG: Pagination ---
gokul_debug_log('Pagination', [
    'page' => $page,
    'per_page' => $per_page,
    'offset' => $offset
]);

// Handle bulk delete (selected or all)
if (isset($_POST['bulk_delete'])) {
    gokul_debug_log('Bulk delete triggered.', $_POST);
    if (check_admin_referer('gokul_walmart_bulk_delete')) {
        gokul_debug_log('Nonce check passed for bulk delete.');
        $delete_ids = isset($_POST['product_ids']) ? array_map('sanitize_text_field', $_POST['product_ids']) : [];
        if (isset($_POST['delete_all']) && $_POST['delete_all'] === '1') {
            // Delete all products
            $wpdb->query("TRUNCATE TABLE $table");
            gokul_debug_log('Bulk deleted ALL Walmart products from database.');
            echo '<div class="notice notice-success"><p>All Walmart products deleted!</p></div>';
        } elseif (!empty($delete_ids)) {
            // Delete selected
            $placeholders = implode(',', array_fill(0, count($delete_ids), '%s'));
            $sql = "DELETE FROM $table WHERE sku IN ($placeholders)";
            $wpdb->query($wpdb->prepare($sql, ...$delete_ids));
            gokul_debug_log('Bulk deleted selected Walmart products.', $delete_ids);
            echo '<div class="notice notice-success"><p>Selected products deleted!</p></div>';
        } else {
            gokul_debug_log('Bulk delete triggered with no IDs or delete_all flag.');
        }
    } else {
        gokul_debug_log('Nonce check failed for bulk delete.');
    }
} else {
    gokul_debug_log('Bulk delete not triggered.');
}

$where = [];
$params = [];

// SKU search
$sku_search = isset($_GET['sku']) ? trim($_GET['sku']) : '';
if ($sku_search !== '') {
    $where[] = "sku LIKE %s";
    $params[] = '%' . $wpdb->esc_like($sku_search) . '%';
    gokul_debug_log('SKU search filter applied', $sku_search);
} else {
    gokul_debug_log('No SKU search filter.');
}

// Status filter
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
if ($status_filter !== '') {
    $where[] = "status = %s";
    $params[] = $status_filter;
    gokul_debug_log('Status filter applied', $status_filter);
} else {
    gokul_debug_log('No status filter.');
}

// Stock filter (order by)
$stock_order = isset($_GET['orderby']) ? $_GET['orderby'] : '';
$order_sql = '';
if ($stock_order === 'stock_asc') {
    $order_sql = 'ORDER BY stock ASC';
    gokul_debug_log('Ordering by stock ASC');
} elseif ($stock_order === 'stock_desc') {
    $order_sql = 'ORDER BY stock DESC';
    gokul_debug_log('Ordering by stock DESC');
} else {
    $order_sql = 'ORDER BY sku ASC';
    gokul_debug_log('Ordering by SKU ASC');
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
gokul_debug_log('WHERE SQL', $where_sql);

// Count and fetch
$sql_total = "SELECT COUNT(*) FROM $table $where_sql";
gokul_debug_log('SQL total count', $sql_total);

if ($params) {
    gokul_debug_log('SQL total count params', $params);
    $total_products = $wpdb->get_var($wpdb->prepare($sql_total, ...$params));
} else {
    $total_products = $wpdb->get_var($sql_total);
}
gokul_debug_log('Total products count', $total_products);

$sql = "SELECT * FROM $table $where_sql $order_sql LIMIT %d OFFSET %d";
$params2 = $params;
$params2[] = $per_page;
$params2[] = $offset;
gokul_debug_log('SQL product fetch', $sql);
gokul_debug_log('SQL product fetch params', $params2);

$products = $wpdb->get_results($wpdb->prepare($sql, ...$params2));
gokul_debug_log('Fetched products count', count($products));
gokul_debug_log('Fetched products sample', array_slice($products, 0, 3));

$total_pages = max(1, ceil($total_products / $per_page));
$current_url = admin_url('admin.php?page=gokul_walmart_products');

// For pagination links with filtering preserved
function gokul_pagination_links($current_url, $page, $total_pages) {
    $query_args = $_GET;
    $html = '<div class="tablenav-pages" style="margin:10px 0;">';
    if ($page > 1) {
        $query_args['paged'] = $page-1;
        $html .= '<a class="prev-page button" href="' . esc_url(add_query_arg($query_args, $current_url)) . '">«</a> ';
    }
    $html .= '<span class="paging-input">' . esc_html($page) . ' of <span class="total-pages">' . esc_html($total_pages) . '</span></span>';
    if ($page < $total_pages) {
        $query_args['paged'] = $page+1;
        $html .= ' <a class="next-page button" href="' . esc_url(add_query_arg($query_args, $current_url)) . '">»</a>';
    }
    $html .= '</div>';
    return $html;
}

// Status values for filter (could be dynamic)
$status_options = [
    '' => 'All',
    'active' => 'Active',
    'inactive' => 'Inactive',
];
?>
<div class="wrap">
    <h1>Walmart Products</h1>
    <div id="walmart-import-progress-section" style="margin-bottom:20px;">
        <button id="walmart-import-btn" class="button button-primary">Start Import</button>
        <div id="walmart-import-progress" style="margin-top:10px;max-width:500px;">
            <div style="width: 100%; background: #eee; border-radius: 3px;">
                <div id="walmart-progress-bar" style="width:0%;background:#0073aa;height:24px;color:#fff;text-align:center;border-radius:3px;transition:width 0.5s;"></div>
            </div>
            <div id="walmart-progress-status" style="margin-top:10px;">
                <span id="walmart-progress-imported">0</span> /
                <span id="walmart-progress-total">0</span> imported.
                <span id="walmart-progress-pending">0</span> pending.
                <span id="walmart-progress-message"></span>
            </div>
            <div id="walmart-api-log" style="margin-top:10px; font-size:12px; color:#555; white-space:pre-wrap;"></div>
        </div>
    </div>
    <!-- Filter Form -->
    <form method="get" style="margin-bottom:20px;" id="walmart-filter-form">
        <input type="hidden" name="page" value="gokul_walmart_products" />
        <input type="text" name="sku" placeholder="Search SKU..." value="<?php echo esc_attr($sku_search); ?>" />
        <select name="status">
            <?php foreach($status_options as $val => $label): ?>
                <option value="<?php echo esc_attr($val); ?>" <?php selected($status_filter, $val); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="orderby">
            <option value="">Order By</option>
            <option value="stock_asc" <?php selected($stock_order, 'stock_asc'); ?>>Stock: Low to High</option>
            <option value="stock_desc" <?php selected($stock_order, 'stock_desc'); ?>>Stock: High to Low</option>
        </select>
        <button class="button" type="submit">Filter</button>
        <a class="button" href="<?php echo esc_url($current_url); ?>">Reset</a>
    </form>
    <!-- Bulk Delete Form -->
    <form method="post" id="walmart-bulk-delete-form" onsubmit="return confirm('Are you sure you want to delete the selected products? This action cannot be undone.');">
        <?php wp_nonce_field('gokul_walmart_bulk_delete'); ?>
        <div style="margin-bottom:10px;">
            <button type="submit" name="bulk_delete" class="button button-danger" style="background:#d63638;color:#fff;">Delete Selected</button>
            <button type="submit" name="bulk_delete" value="1" class="button button-danger" style="background:#b30000;color:#fff;" onclick="return confirm('This will delete ALL products from the database. Are you sure?');">
                <input type="hidden" name="delete_all" value="1">Delete ALL Products
            </button>
        </div>
        <!-- Pagination Top -->
        <?php echo gokul_pagination_links($current_url, $page, $total_pages); ?>
        <h2>Product List</h2>
        <table class="widefat striped" id="walmart-products-table">
            <thead>
                <tr>
                    <th style="width:30px;"><input type="checkbox" id="walmart-select-all"></th>
                    <th>SKU (Product ID)</th>
                    <th>Thumbnail</th>
                    <th>Title</th>
                    <th>GTIN</th>
                    <th>Product Link</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Lifecycle</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($products)): foreach ($products as $product): ?>
                <?php gokul_debug_log('Displaying Product Row', (array) $product); ?>
                <tr>
                    <td><input type="checkbox" name="product_ids[]" value="<?php echo esc_attr($product->sku); ?>" class="walmart-row-checkbox"></td>
                    <td><?php echo esc_html($product->sku); ?></td>
                    <td>
                        <?php if (!empty($product->thumbnail)): ?>
                            <img src="<?php echo esc_url($product->thumbnail); ?>" style="height:40px;width:auto;">
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($product->title); ?></td>
                    <td><?php echo esc_html($product->gtin); ?></td>
                    <td>
                        <?php if (!empty($product->product_link)): ?>
                            <a href="<?php echo esc_url($product->product_link); ?>" target="_blank">View</a>
                        <?php endif; ?>
                    </td>
                    <td><?php echo isset($product->stock) ? intval($product->stock) : 0; ?></td>
                    <td><?php echo esc_html($product->status); ?></td>
                    <td><?php echo esc_html($product->lifecycle); ?></td>
                </tr>
            <?php endforeach; else: ?>
                <?php gokul_debug_log('No products found on this page.'); ?>
                <tr><td colspan="9">No products found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <!-- Pagination Bottom -->
        <?php echo gokul_pagination_links($current_url, $page, $total_pages); ?>
    </form>
    <div style="margin-top:40px;">
        <h3>Debug Log:</h3>
        <pre style="background:#f9f9f9;border:1px solid #ccc;padding:10px;max-height:300px;overflow:auto;"><?php
            foreach ($debug_log as $line) echo esc_html($line) . "\n";
        ?></pre>
    </div>
</div>
<script>
(function($){
    let interval = null;
    function fetchProgress(){
        $.get(ajaxurl, { action: 'gokul_walmart_import_progress' }, function(data){
            if(!data || typeof data !== 'object') return;
            var imported = data.imported || 0;
            var total = data.total || 0;
            var error = data.error || '';
            var done = !data.in_progress && total > 0;
            var percent = total ? Math.floor(imported * 100 / total) : 0;

            $("#walmart-progress-bar")
                .css('width', percent + '%')
                .text(percent + '%');

            $("#walmart-progress-imported").text(imported);
            $("#walmart-progress-total").text(total);
            $("#walmart-progress-pending").text(Math.max(0, total - imported));
            $("#walmart-progress-message").html(
                error ? '<span style="color:red">Error: '+error+'</span>' :
                (done ? '<b>Import Complete!</b>' : (data.in_progress ? 'Importing...' : 'Idle'))
            );
            // API log/status
            var log = '';
            if (data.api_status && Array.isArray(data.api_status)) {
                log = data.api_status.map(function(item, idx){
                    return (
                        '['+(item.time || '')+'] ' +
                        (item.endpoint ? item.endpoint + ' ' : '') +
                        (item.status ? ('['+item.status+'] ') : '') +
                        (item.message ? item.message : '')
                    );
                }).join("\n");
            }
            $("#walmart-api-log").text(log);

            if(done && interval) clearInterval(interval);
        });
    }

    $("#walmart-import-btn").on('click', function(){
        $(this).prop('disabled',true).text('Importing...');
        $.post(ajaxurl, { action: 'gokul_walmart_start_import' }, function(resp){
            if(resp.success){
                fetchProgress();
                interval = setInterval(fetchProgress, 2000);
            } else {
                alert(resp.data || "Failed to start import.");
                $("#walmart-import-btn").prop('disabled',false).text('Start Import');
            }
        });
    });

    // Select all checkboxes
    $('#walmart-select-all').on('click', function(){
        $('.walmart-row-checkbox').prop('checked', this.checked);
    });

    // If any individual box is unchecked, uncheck 'select all'
    $(document).on('click', '.walmart-row-checkbox', function(){
        if(!this.checked) {
            $('#walmart-select-all').prop('checked', false);
        } else if ($('.walmart-row-checkbox:checked').length === $('.walmart-row-checkbox').length) {
            $('#walmart-select-all').prop('checked', true);
        }
    });

    fetchProgress();
    interval = setInterval(fetchProgress, 2000);
})(jQuery);
</script>