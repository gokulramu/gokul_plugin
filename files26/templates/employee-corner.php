<?php 
if (!defined('ABSPATH')) exit;
if (!is_user_logged_in() || (!current_user_can('gokul_employee') && !current_user_can('administrator'))) {
    wp_safe_redirect(site_url('/employee-login/'));
    exit;
}

$current_user = wp_get_current_user();
$dt = new DateTime("now", new DateTimeZone("Asia/Kolkata"));
$current_time_ist = $dt->format('h:i A');
$current_date_ist = $dt->format('d M Y');
?>

<div class="gokul-corner">
    <div class="gokul-header-center">
        <h2 class="gokul-welcome">Welcome, <?php echo esc_html($current_user->display_name); ?>!</h2>
        <div class="gokul-session-box">
            <span class="session-item"><?php echo esc_html($current_user->user_login); ?></span>
            <span class="session-dot">&bull;</span>
            <span class="session-item"><?php echo esc_html($current_date_ist); ?></span>
            <span class="session-dot">&bull;</span>
            <span class="session-item"><?php echo $current_time_ist; ?></span>
        </div>
    </div>

    <div class="gokul-nav-panel">
        <h3 class="panel-title">Quick Actions</h3>
        <div class="action-buttons">
            <!-- Products (Frontend) -->
            <a href="<?php echo esc_url(site_url('/products/')); ?>" class="gokul-btn products-btn">
                <div class="btn-icon">🛒</div>
                <div class="btn-content">
                    <span class="btn-title">Products</span>
                    <span class="btn-desc">Manage all products <span style="font-size:13px">(🌐/🏠/🪢)</span></span>
                </div>
            </a>
            <!-- International Orders (Frontend) -->
            <a href="<?php echo esc_url(site_url('/international-orders/')); ?>" class="gokul-btn intl-orders-btn">
                <div class="btn-icon">🌐</div>
                <div class="btn-content">
                    <span class="btn-title">International Orders</span>
                    <span class="btn-desc">Walmart, eBay, Etsy, Amazon</span>
                </div>
            </a>
            <!-- Domestic Orders (Frontend) -->
            <a href="<?php echo esc_url(site_url('/domestic-orders/')); ?>" class="gokul-btn dom-orders-btn">
                <div class="btn-icon">🏠</div>
                <div class="btn-content">
                    <span class="btn-title">Domestic Orders</span>
                    <span class="btn-desc">Amazon, Flipkart</span>
                </div>
            </a>
            <!-- Example for future features:
            <a href="<?php //echo esc_url(site_url('/your-future-page/')); ?>" class="gokul-btn new-feature-btn">
                <div class="btn-icon">🆕</div>
                <div class="btn-content">
                    <span class="btn-title">New Feature</span>
                    <span class="btn-desc">Describe your new tool/feature here</span>
                </div>
            </a>
            -->
        </div>
    </div>
</div>

<!-- Logout button above the footer -->
<div class="gokul-logout-bar">
    <a href="<?php echo esc_url(wp_logout_url(site_url('/employee-login/'))); ?>" class="gokul-btn gokul-logout-btn">
        <span class="btn-icon">🚪</span>
        <span class="btn-title">Logout</span>
    </a>
</div>

<style>
.gokul-corner {
    max-width: 1000px;
    margin: 40px auto;
    padding: 30px;
    background: #ffffff;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.gokul-header-center {
    text-align: center;
    margin-bottom: 30px;
    border-bottom: 2px solid #edf2f7;
    padding-bottom: 22px;
}

.gokul-welcome {
    color: #2d3748;
    font-size: 28px;
    margin: 0 0 16px 0;
    font-weight: 600;
    letter-spacing: 1px;
}

.gokul-session-box {
    display: inline-flex;
    align-items: center;
    background: #f0f4f8;
    border-radius: 32px;
    padding: 8px 28px;
    font-size: 15px;
    color: #2d3748;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    gap: 10px;
}

.session-item {
    font-family: 'Courier New', monospace;
    font-size: 15px;
    background: transparent;
    color: #2d3748;
}

.session-dot {
    color: #a0aec0;
    font-size: 17px;
    padding: 0 7px;
}

.panel-title {
    color: #4a5568;
    font-size: 18px;
    margin: 0 0 15px 0;
    font-weight: 500;
}
.gokul-nav-panel { margin-top: 30px; }
.action-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    margin-top: 15px;
}
.gokul-btn {
    display: flex;
    align-items: center;
    padding: 20px;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    font-weight: 500;
    font-size: 16px;
    border: 1px solid #e2e8f0;
    background: #fff;
}
.btn-icon {
    font-size: 26px;
    margin-right: 17px;
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}
.btn-content {
    display: flex;
    flex-direction: column;
    flex: 1;
}
.btn-title { font-weight: 600; font-size: 16px; color: #2d3748; margin-bottom: 3px; }
.btn-desc {
    font-size: 13px;
    color: #1565c0;
    font-weight: bold;
}
.products-btn {
    background: #f7b924;
    color: #fff !important;
    border-color: #e1a700;
}
.products-btn .btn-icon { background: #fff3cd; color: #8a6d1e; }
.products-btn:hover {
    background: #e1a700;
    color: #fff !important;
}

.intl-orders-btn {
    background: #26c6da;
    color: #fff !important;
    border-color: #0097a7;
}
.intl-orders-btn .btn-icon { background: #e0f7fa; color: #006064; }
.intl-orders-btn:hover {
    background: #0097a7;
    color: #fff !important;
}

.dom-orders-btn {
    background: #66bb6a;
    color: #fff !important;
    border-color: #388e3c;
}
.dom-orders-btn .btn-icon { background: #e8f5e9; color: #2e7d32; }
.dom-orders-btn:hover {
    background: #388e3c;
    color: #fff !important;
}

.gokul-logout-bar {
    width: 100%;
    display: flex;
    justify-content: flex-end;
    margin: 30px 0 0 0;
    padding: 12px 0 0 0;
    border-top: 1px solid #f0f0f0;
}
.gokul-logout-btn {
    background: #f44336;
    color: #fff !important;
    font-weight: 600;
    border-radius: 7px;
    padding: 10px 28px;
    margin-right: 40px;
    border: none;
    box-shadow: 0 1px 6px rgba(220,53,69,0.08);
}
.gokul-logout-btn:hover {
    background: #b71c1c;
}
@media (max-width: 768px) {
    .gokul-corner {
        margin: 20px;
        padding: 20px;
    }
    .gokul-header-center {
        text-align: center;
    }
    .action-buttons {
        grid-template-columns: 1fr;
    }
    .gokul-btn {
        width: 100%;
    }
    .gokul-logout-bar {
        margin-right: 0;
        justify-content: center;
    }
}
</style>