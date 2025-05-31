<?php
if (!defined('ABSPATH')) exit;

define('GOKUL_MARKETPLACE_ACCOUNTS_OPTION', 'gokul_plugin_marketplace_accounts');

// Helper: Get all accounts
function gokul_marketplace_get_accounts() {
    $accounts = get_option(GOKUL_MARKETPLACE_ACCOUNTS_OPTION);
    if (!$accounts) $accounts = [];
    return $accounts;
}

// Handle add/edit/delete/test actions (no output here)
$test_results = [];
$tested_marketplace = null;
$tested_account_index = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gokul_marketplace_action'])) {
    $accounts = gokul_marketplace_get_accounts();

    // Add account
    if ($_POST['gokul_marketplace_action'] === 'add_account') {
        $marketplace = sanitize_text_field($_POST['marketplace']);
        $type = sanitize_text_field($_POST['type']);
        $new_account = [
            'account_name' => sanitize_text_field($_POST['account_name']),
            'client_id' => sanitize_text_field($_POST['client_id']),
            'client_secret' => sanitize_text_field($_POST['client_secret']),
            'type' => $type
        ];
        if (!isset($accounts[$marketplace])) $accounts[$marketplace] = [];
        $accounts[$marketplace][] = $new_account;
        update_option(GOKUL_MARKETPLACE_ACCOUNTS_OPTION, $accounts, true);
        $test_results['log'] = 'Account added!';
    }

    // Edit account
    if ($_POST['gokul_marketplace_action'] === 'edit_account') {
        $marketplace = sanitize_text_field($_POST['marketplace']);
        $account_index = intval($_POST['account_index']);
        if (isset($accounts[$marketplace][$account_index])) {
            $accounts[$marketplace][$account_index]['account_name'] = sanitize_text_field($_POST['account_name']);
            $accounts[$marketplace][$account_index]['client_id'] = sanitize_text_field($_POST['client_id']);
            $accounts[$marketplace][$account_index]['client_secret'] = sanitize_text_field($_POST['client_secret']);
            $accounts[$marketplace][$account_index]['type'] = sanitize_text_field($_POST['type']);
            update_option(GOKUL_MARKETPLACE_ACCOUNTS_OPTION, $accounts, true);
            $test_results['log'] = 'Account updated!';
        }
    }

    // Delete account
    if ($_POST['gokul_marketplace_action'] === 'delete_account' && isset($_POST['marketplace'], $_POST['account_index'])) {
        $marketplace = sanitize_text_field($_POST['marketplace']);
        $account_index = intval($_POST['account_index']);
        if (isset($accounts[$marketplace][$account_index])) {
            array_splice($accounts[$marketplace], $account_index, 1);
            update_option(GOKUL_MARKETPLACE_ACCOUNTS_OPTION, $accounts, true);
            $test_results['log'] = 'Account deleted!';
        }
    }

    // Test logic: on POST, run the test API call and store the results
    if ($_POST['gokul_marketplace_action'] === 'test_account') {
        $marketplace = sanitize_text_field($_POST['marketplace']);
        $account_index = intval($_POST['account_index']);
        $accounts = gokul_marketplace_get_accounts();
        $account = $accounts[$marketplace][$account_index] ?? null;
        $class = 'Gokul_Plugin_' . ucfirst($marketplace) . '_API';
        if ($account && class_exists($class) && method_exists($class, 'test_account')) {
            $client_id = $account['client_id'];
            $client_secret = $account['client_secret'];
            $type = $account['type'];
            $service_name = $account['service_name'] ?? '';
            $partner_id = $account['partner_id'] ?? '';
            $api = new $class($client_id, $client_secret, $type, $service_name, $partner_id);
            $test_results = $api->test_account();
            // Add log if failed
            if (!$test_results['success']) {
                $test_results['log'] = $test_results['message'];
            }
        } else {
            $test_results = [
                'success' => false,
                'log' => 'API class or test_account method not implemented for ' . esc_html($marketplace)
            ];
        }
        $tested_marketplace = $marketplace;
        $tested_account_index = $account_index;
    }
}

function gokul_marketplace_apis_admin() {
    global $test_results, $tested_marketplace, $tested_account_index;
    $accounts = gokul_marketplace_get_accounts();

    ?>
    <div class="wrap">
        <h1>Marketplace APIs</h1>
        <p>Manage all your marketplace API accounts for both Domestic and International channels.</p>

        <?php
        // Show log if present
        if (isset($test_results['log']) && $test_results['log']) {
            echo '<div class="notice notice-error"><strong>Log:</strong> ' . esc_html($test_results['log']) . '</div>';
        }
        ?>

        <h2>Add New Account</h2>
        <form method="post" class="gokul-add-account-form" style="margin-bottom:40px;">
            <input type="hidden" name="gokul_marketplace_action" value="add_account">
            <table>
                <tr>
                    <td>Marketplace:</td>
                    <td>
                        <select name="marketplace" required>
                            <option value="flipkart">Flipkart</option>
                            <option value="amazon">Amazon</option>
                            <option value="walmart">Walmart</option>
                            <option value="ebay">eBay</option>
                            <option value="etsy">Etsy</option>
                        </select>
                    </td>
                    <td>Type:</td>
                    <td>
                        <select name="type" required>
                            <option value="domestic">Domestic</option>
                            <option value="international">International</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Account Name:</td>
                    <td><input type="text" name="account_name" required></td>
                    <td>Client ID:</td>
                    <td><input type="text" name="client_id" required></td>
                    <td>Client Secret:</td>
                    <td><input type="text" name="client_secret" required></td>
                    <td><button type="submit" class="button button-primary">Add</button></td>
                </tr>
            </table>
        </form>

        <h2>All Accounts</h2>
        <div class="gokul-marketplace-row">
        <?php foreach ($accounts as $marketplace => $acclist): ?>
            <?php foreach ($acclist as $idx => $acc): ?>
                <div class="gokul-marketplace-box" id="account_<?php echo esc_attr($marketplace . '_' . $idx); ?>">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="marketplace-title"><?php echo ucfirst($marketplace); ?></div>
                        <?php
                        // Show status light if tested
                        if (isset($tested_marketplace, $tested_account_index) && $tested_marketplace === $marketplace && $tested_account_index == $idx) {
                            if (!empty($test_results['success'])) {
                                echo '<span style="width:16px;height:16px;background:#22c55e;display:inline-block;border-radius:50%;" title="API working"></span>';
                            } else {
                                echo '<span style="width:16px;height:16px;background:#ef4444;display:inline-block;border-radius:50%;" title="API failed"></span>';
                            }
                        }
                        ?>
                    </div>
                    <div class="account-title"><?php echo esc_html($acc['account_name']); ?></div>
                    <div class="account-creds">
                        <span class="creds-label">Client ID: </span>
                        <span class="creds-value"><?php echo esc_html($acc['client_id']); ?></span>
                    </div>
                    <div style="margin-top:12px;">
                        <!-- View/Edit -->
                        <button type="button" class="button button-small" onclick="toggleEdit('<?php echo esc_js($marketplace); ?>', <?php echo $idx; ?>)">View/Edit</button>
                        <!-- Test -->
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="gokul_marketplace_action" value="test_account">
                            <input type="hidden" name="marketplace" value="<?php echo esc_attr($marketplace); ?>">
                            <input type="hidden" name="account_index" value="<?php echo esc_attr($idx); ?>">
                            <button type="submit" class="button button-small">Test</button>
                        </form>
                        <!-- Delete -->
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="gokul_marketplace_action" value="delete_account">
                            <input type="hidden" name="marketplace" value="<?php echo esc_attr($marketplace); ?>">
                            <input type="hidden" name="account_index" value="<?php echo esc_attr($idx); ?>">
                            <button type="submit" class="button button-small button-danger" onclick="return confirm('Delete this account?')">Delete</button>
                        </form>
                    </div>
                    <!-- Edit form (hidden by default) -->
                    <form method="post" class="edit-form" id="edit_<?php echo esc_attr($marketplace . '_' . $idx); ?>" style="display:none;margin-top:14px;">
                        <input type="hidden" name="gokul_marketplace_action" value="edit_account">
                        <input type="hidden" name="marketplace" value="<?php echo esc_attr($marketplace); ?>">
                        <input type="hidden" name="account_index" value="<?php echo esc_attr($idx); ?>">
                        <label>Account Name: <input type="text" name="account_name" value="<?php echo esc_attr($acc['account_name']); ?>"></label><br>
                        <label>Client ID: <input type="text" name="client_id" value="<?php echo esc_attr($acc['client_id']); ?>"></label><br>
                        <label>Client Secret: <input type="text" name="client_secret" value="<?php echo esc_attr($acc['client_secret']); ?>"></label><br>
                        <label>Type:
                            <select name="type">
                                <option value="domestic" <?php selected($acc['type'],'domestic'); ?>>Domestic</option>
                                <option value="international" <?php selected($acc['type'],'international'); ?>>International</option>
                            </select>
                        </label><br>
                        <button type="submit" class="button button-primary">Save</button>
                        <button type="button" class="button" onclick="toggleEdit('<?php echo esc_js($marketplace); ?>', <?php echo $idx; ?>)">Cancel</button>
                    </form>
                    <!-- Show detailed log if tested -->
                    <?php
                    if (isset($tested_marketplace, $tested_account_index) && $tested_marketplace === $marketplace && $tested_account_index == $idx && isset($test_results['log'])) {
                        echo '<div class="gokul-log" style="margin-top:14px;background:#f9fafb;border:1px solid #ccc;padding:10px;border-radius:6px;max-width:100%;overflow:auto;font-size:12px;">';
                        echo '<strong>Detailed Log:</strong><br>' . esc_html($test_results['log']);
                        echo '</div>';
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </div>
    </div>
    <style>
    .gokul-marketplace-row {
        display: flex;
        flex-wrap: wrap;
        gap: 22px;
        margin-top: 16px;
    }
    .gokul-marketplace-box {
        background: #f8fafc;
        border: 2px solid #e5e7eb;
        border-radius: 16px;
        padding: 18px 18px 14px 18px;
        min-width: 250px;
        flex: 1 0 250px;
        box-shadow: 0 2px 6px rgba(60,70,80,0.03);
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        max-width: 300px;
        position:relative;
    }
    .marketplace-title {
        font-size: 17px;
        font-weight: bold;
        color: #2563eb;
        margin-bottom: 5px;
        letter-spacing: 0.5px;
    }
    .account-title {
        font-weight: 600;
        color: #0369a1;
        font-size: 15px;
        margin-bottom: 7px;
    }
    .account-creds {
        font-size: 12px;
        color: #374151;
        background: #eef2ff;
        border-radius: 5px;
        padding: 2px 7px;
        display: inline-block;
    }
    .creds-label { font-weight: bold; color: #475569; }
    .creds-value { font-family: monospace; }
    .button-danger { background: #ef4444; color: #fff; border: none;}
    .button-danger:hover { background: #dc2626;}
    .edit-form label {display:block;margin-bottom:5px;}
    .gokul-log {white-space:pre-wrap;}
    </style>
    <script>
    function toggleEdit(marketplace, idx) {
        var editId = 'edit_' + marketplace + '_' + idx;
        var f = document.getElementById(editId);
        if (f.style.display === 'none') f.style.display = 'block';
        else f.style.display = 'none';
    }
    </script>
    <?php
}