<?php
class Gokul_Employee {
    public static function add_role() {
        add_role('gokul_employee', 'Employee', ['read'=>true]);
    }
    public static function remove_role() {
        remove_role('gokul_employee');
    }
    public static function add_admin_cap() {
        $role = get_role('administrator');
        if ($role && !$role->has_cap('gokul_employee')) $role->add_cap('gokul_employee');
    }
}