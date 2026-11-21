<?php
defined( 'ABSPATH' ) || exit;

class MMS_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menus' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    public static function is_main_admin() {
        return current_user_can( 'manage_options' );
    }

    public static function get_assigned_class( $user_id = 0 ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        $assigned = get_user_meta( $user_id, 'mms_assigned_class', true );
        return ! empty( $assigned ) ? $assigned : 'all';
    }

    public static function register_admin_menus() {
        // Minimum capability to access MMS
        $base_cap = current_user_can( 'manage_options' ) ? 'manage_options' : 'mms_access';

        // Main Menu
        add_menu_page(
            'মাদ্রাসা ড্যাশবোর্ড',
            'মাদ্রাসা ম্যানেজমেন্ট',
            $base_cap,
            'mms-dashboard',
            array( __CLASS__, 'render_dashboard_page' ),
            'dashicons-welcome-learn-more',
            25
        );

        // Submenus accessible by BOTH Main Admin & Sub-Admin
        add_submenu_page(
            'mms-dashboard',
            'ড্যাশবোর্ড',
            'ড্যাশবোর্ড',
            $base_cap,
            'mms-dashboard',
            array( __CLASS__, 'render_dashboard_page' )
        );

        add_submenu_page(
            'mms-dashboard',
            'শিক্ষার্থী তালিকা',
            'শিক্ষার্থী তালিকা',
            $base_cap,
            'mms-students',
            array( __CLASS__, 'render_students_page' )
        );

        add_submenu_page(
            'mms-dashboard',
            'শিক্ষার্থী হাজিরা',
            'শিক্ষার্থী হাজিরা',
            $base_cap,
            'mms-student-attendance',
            array( __CLASS__, 'render_student_attendance_page' )
        );

        add_submenu_page(
            'mms-dashboard',
            'বেতন ও ফি আদায়',
            'বেতন ও ফি আদায়',
            $base_cap,
            'mms-student-fees',
            array( __CLASS__, 'render_student_fees_page' )
        );

        // Submenus accessible ONLY by Main Admin
        $main_admin_cap = 'manage_options';

        add_submenu_page(
            'mms-dashboard',
            'সাব-অ্যাডমিন ব্যবস্থাপনা',
            'সাব-অ্যাডমিন (শ্রেণি শিক্ষক)',
            $main_admin_cap,
            'mms-subadmins',
            array( __CLASS__, 'render_subadmins_page' )
        );

        add_submenu_page(
            'mms-dashboard',
            'শিক্ষক ও স্টাফ',
            'শিক্ষক ও স্টাফ',
            $main_admin_cap,
            'mms-teachers',
            array( __CLASS__, 'render_teachers_page' )
        );

        add_submenu_page(
            'mms-dashboard',
            'শিক্ষক হাজিরা',
            'শিক্ষক হাজিরা',
            $main_admin_cap,
            'mms-teacher-attendance',
            array( __CLASS__, 'render_teacher_attendance_page' )
        );

        add_submenu_page(
            'mms-dashboard',
            'শিক্ষকদের বেতন',
            'শিক্ষকদের বেতন',
            $main_admin_cap,
            'mms-teacher-salaries',
            array( __CLASS__, 'render_teacher_salaries_page' )
        );

        add_submenu_page(
            'mms-dashboard',
            'মাদ্রাসার খরচ',
            'মাদ্রাসার খরচ',
            $main_admin_cap,
            'mms-expenses',
            array( __CLASS__, 'render_expenses_page' )
        );

        add_submenu_page(
            'mms-dashboard',
            'আয়-ব্যয় ও রিপোর্ট',
            'আয়-ব্যয় রিপোর্ট',
            $main_admin_cap,
            'mms-reports',
            array( __CLASS__, 'render_reports_page' )
        );

        add_submenu_page(
            'mms-dashboard',
            'মাদ্রাসা সেটিংস',
            'সেটিংস',
            $main_admin_cap,
            'mms-settings',
            array( __CLASS__, 'render_settings_page' )
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'mms-' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'mms-admin-css',
            MMS_PLUGIN_URL . 'assets/css/mms-admin.css',
            array(),
            MMS_VERSION
        );

        wp_enqueue_style(
            'mms-print-css',
            MMS_PLUGIN_URL . 'assets/css/mms-print.css',
            array(),
            MMS_VERSION,
            'print'
        );

        wp_enqueue_style(
            'mms-bengali-font',
            'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap',
            array(),
            null
        );

        wp_enqueue_script(
            'mms-admin-js',
            MMS_PLUGIN_URL . 'assets/js/mms-admin.js',
            array( 'jquery' ),
            MMS_VERSION,
            true
        );

        $settings = get_option( 'mms_settings', array() );
        $currency = isset( $settings['currency'] ) ? $settings['currency'] : '৳';
        $is_main_admin = self::is_main_admin();
        $assigned_class = self::get_assigned_class();

        wp_localize_script( 'mms-admin-js', 'mmsData', array(
            'ajax_url'       => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'mms_admin_nonce' ),
            'currency'       => $currency,
            'is_main_admin'  => $is_main_admin,
            'assigned_class' => $assigned_class,
            'strings'        => array(
                'confirm_delete' => 'আপনি কি নিশ্চিত যে এটি মুছে ফেলতে চান?',
                'permission_err' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন এই কাজটি করতে পারেন।',
                'saved'          => 'সফলভাবে সংরক্ষিত হয়েছে!',
                'error'          => 'কিছু ভুল হয়েছে, অনুগ্রহ করে আবার চেষ্টা করুন।',
                'loading'        => 'লোড হচ্ছে...',
            )
        ) );
    }

    // Page Render callbacks
    public static function render_dashboard_page() {
        require_once MMS_PLUGIN_DIR . 'views/dashboard.php';
    }

    public static function render_students_page() {
        require_once MMS_PLUGIN_DIR . 'views/students.php';
    }

    public static function render_student_attendance_page() {
        require_once MMS_PLUGIN_DIR . 'views/student-attendance.php';
    }

    public static function render_student_fees_page() {
        require_once MMS_PLUGIN_DIR . 'views/student-fees.php';
    }

    public static function render_subadmins_page() {
        require_once MMS_PLUGIN_DIR . 'views/subadmins.php';
    }

    public static function render_teachers_page() {
        require_once MMS_PLUGIN_DIR . 'views/teachers.php';
    }

    public static function render_teacher_attendance_page() {
        require_once MMS_PLUGIN_DIR . 'views/teacher-attendance.php';
    }

    public static function render_teacher_salaries_page() {
        require_once MMS_PLUGIN_DIR . 'views/teacher-salaries.php';
    }

    public static function render_expenses_page() {
        require_once MMS_PLUGIN_DIR . 'views/expenses.php';
    }

    public static function render_reports_page() {
        require_once MMS_PLUGIN_DIR . 'views/reports.php';
    }

    public static function render_settings_page() {
        require_once MMS_PLUGIN_DIR . 'views/settings.php';
    }
}
