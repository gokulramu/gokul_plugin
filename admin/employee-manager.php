<?php
function gokul_employee_manager_admin() {
    $tabs = [
        'list' => 'Employee List',
        'add'  => 'Add Employee',
        'logs' => 'Employee Logs'
    ];
    $active = isset($_GET['tab']) && isset($tabs[$_GET['tab']]) ? $_GET['tab'] : 'list';

    echo '<div class="wrap"><h1>Employee Manager</h1><nav class="gokul-tabs">';
    foreach ($tabs as $k => $v) {
        $url = admin_url('admin.php?page=gokul_employee_manager&tab='.$k);
        echo '<a class="'.($active==$k?'gokul-tab-active':'').'" href="'.$url.'">'.$v.'</a>';
    }
    echo '</nav><div class="gokul-tab-content">';
    if ($active==='list') {
        $users = get_users(['role'=>'gokul_employee']);
        if (empty($users)) $users = [(object)['ID'=>1,'user_login'=>'sampleemp','user_email'=>'emp@example.com']];
        echo '<table class="widefat striped"><tr><th>Username</th><th>Email</th><th>Actions</th></tr>';
        foreach ($users as $user) {
            echo '<tr>
                <td>'.esc_html($user->user_login).'</td>
                <td>'.esc_html($user->user_email).'</td>
                <td><a href="'.admin_url('user-edit.php?user_id='.$user->ID).'" class="button">Edit</a></td>
            </tr>';
        }
        echo '</table>';
    } elseif ($active==='add') {
        if ($_SERVER['REQUEST_METHOD']==='POST' && current_user_can('manage_options')) {
            $username = sanitize_user($_POST['gokul_new_user']);
            $email = sanitize_email($_POST['gokul_new_email']);
            $pass = sanitize_text_field($_POST['gokul_new_pass']);
            $user_id = wp_create_user($username, $pass, $email);
            if (!is_wp_error($user_id)) {
                $user = new WP_User($user_id);
                $user->set_role('gokul_employee');
                Gokul_Logger::log("Created employee $username");
                echo '<div class="updated"><p>Employee added!</p></div>';
            } else {
                echo '<div class="error"><p>'.esc_html($user_id->get_error_message()).'</p></div>';
            }
        }
        echo '<form method="post" class="gokul-form" style="max-width:320px;">
            <input type="text" name="gokul_new_user" placeholder="Username" required style="width:100%;margin-bottom:10px;">
            <input type="email" name="gokul_new_email" placeholder="Email" required style="width:100%;margin-bottom:10px;">
            <input type="text" name="gokul_new_pass" placeholder="Password" required style="width:100%;margin-bottom:10px;">
            <button type="submit" class="button button-primary" style="width:100%;">Add Employee</button>
        </form>';
    } elseif ($active==='logs') {
        $log = Gokul_Logger::get_log('employee');
        echo '<pre style="max-height:300px;overflow:auto;background:#f9f9f9;padding:12px;">'.esc_html($log ? $log : "No logs yet.").'</pre>';
    }
    echo '</div></div>';
}