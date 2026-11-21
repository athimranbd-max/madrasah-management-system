<?php
defined( 'ABSPATH' ) || exit;

class MMS_DB {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // 1. Students Table
        $table_students = $wpdb->prefix . 'mms_students';
        $sql_students = "CREATE TABLE $table_students (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id varchar(50) NOT NULL,
            name_bn varchar(255) NOT NULL,
            name_en varchar(255) DEFAULT '',
            class_name varchar(100) NOT NULL,
            roll_no int(11) NOT NULL DEFAULT 1,
            guardian_name varchar(255) DEFAULT '',
            phone varchar(30) DEFAULT '',
            monthly_fee decimal(10,2) NOT NULL DEFAULT 0.00,
            admission_date date DEFAULT NULL,
            address text DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY student_id (student_id),
            KEY class_name (class_name)
        ) $charset_collate;";
        dbDelta( $sql_students );

        // 2. Teachers Table
        $table_teachers = $wpdb->prefix . 'mms_teachers';
        $sql_teachers = "CREATE TABLE $table_teachers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            teacher_id varchar(50) NOT NULL,
            name varchar(255) NOT NULL,
            designation varchar(150) NOT NULL DEFAULT 'সহকারী শিক্ষক',
            phone varchar(30) DEFAULT '',
            monthly_salary decimal(10,2) NOT NULL DEFAULT 0.00,
            joining_date date DEFAULT NULL,
            address text DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY teacher_id (teacher_id)
        ) $charset_collate;";
        dbDelta( $sql_teachers );

        // 3. Student Attendance Table
        $table_student_attendance = $wpdb->prefix . 'mms_student_attendance';
        $sql_student_attendance = "CREATE TABLE $table_student_attendance (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            class_name varchar(100) NOT NULL,
            attendance_date date NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'present',
            remarks varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY student_date (student_id, attendance_date),
            KEY attendance_date (attendance_date),
            KEY class_name (class_name)
        ) $charset_collate;";
        dbDelta( $sql_student_attendance );

        // 4. Teacher Attendance Table
        $table_teacher_attendance = $wpdb->prefix . 'mms_teacher_attendance';
        $sql_teacher_attendance = "CREATE TABLE $table_teacher_attendance (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            teacher_id bigint(20) NOT NULL,
            attendance_date date NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'present',
            remarks varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY teacher_date (teacher_id, attendance_date),
            KEY attendance_date (attendance_date)
        ) $charset_collate;";
        dbDelta( $sql_teacher_attendance );

        // 5. Student Fees & Collections Table
        $table_student_fees = $wpdb->prefix . 'mms_student_fees';
        $sql_student_fees = "CREATE TABLE $table_student_fees (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            voucher_no varchar(50) NOT NULL,
            student_id bigint(20) NOT NULL,
            fee_type varchar(50) NOT NULL DEFAULT 'মাসিক বেতন',
            month_year varchar(30) DEFAULT '',
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            paid_date date NOT NULL,
            payment_method varchar(50) NOT NULL DEFAULT 'Cash',
            remarks text DEFAULT '',
            created_by bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY voucher_no (voucher_no),
            KEY student_id (student_id),
            KEY fee_type (fee_type),
            KEY paid_date (paid_date)
        ) $charset_collate;";
        dbDelta( $sql_student_fees );

        // 6. Teacher Salaries Table
        $table_teacher_salaries = $wpdb->prefix . 'mms_teacher_salaries';
        $sql_teacher_salaries = "CREATE TABLE $table_teacher_salaries (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            voucher_no varchar(50) NOT NULL,
            teacher_id bigint(20) NOT NULL,
            month_year varchar(30) NOT NULL,
            basic_salary decimal(10,2) NOT NULL DEFAULT 0.00,
            bonus decimal(10,2) NOT NULL DEFAULT 0.00,
            deduction decimal(10,2) NOT NULL DEFAULT 0.00,
            net_paid decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_date date NOT NULL,
            payment_method varchar(50) NOT NULL DEFAULT 'Cash',
            remarks text DEFAULT '',
            created_by bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY voucher_no (voucher_no),
            KEY teacher_id (teacher_id),
            KEY payment_date (payment_date)
        ) $charset_collate;";
        dbDelta( $sql_teacher_salaries );

        // 7. Madrasah Expenses Table
        $table_expenses = $wpdb->prefix . 'mms_expenses';
        $sql_expenses = "CREATE TABLE $table_expenses (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            voucher_no varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            category varchar(100) NOT NULL DEFAULT 'অন্যান্য খরচ',
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            expense_date date NOT NULL,
            paid_to varchar(255) DEFAULT '',
            remarks text DEFAULT '',
            created_by bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY voucher_no (voucher_no),
            KEY category (category),
            KEY expense_date (expense_date)
        ) $charset_collate;";
        dbDelta( $sql_expenses );

        // Register Custom Sub-Admin Role
        self::register_subadmin_role();

        // Initialize Default Options if not present
        self::init_default_options();
    }

    public static function register_subadmin_role() {
        // Sub-Admin has access to view assigned class, mark attendance, collect fees
        // CANNOT add users, delete users, delete students, delete records, or change settings
        add_role(
            'mms_subadmin',
            'মাদ্রাসা সাব-অ্যাডমিন (শ্রেণি শিক্ষক)',
            array(
                'read'                 => true,
                'mms_access'           => true,
                'mms_mark_attendance'  => true,
                'mms_collect_fees'     => true,
                'mms_view_assigned'    => true,
                'manage_options'       => false,
                'delete_users'         => false,
                'create_users'         => false,
            )
        );

        // Ensure Administrator has full mms access
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'mms_access' );
            $admin->add_cap( 'mms_full_admin' );
        }
    }

    public static function init_default_options() {
        if ( ! get_option( 'mms_classes' ) ) {
            $default_classes = array(
                'শিশু ১',
                'শিশু ২',
                '১ম শ্রেণি',
                '২য় শ্রেণি',
                '৩য় শ্রেণি'
            );
            update_option( 'mms_classes', $default_classes );
        }

        if ( ! get_option( 'mms_fee_types' ) ) {
            $default_fee_types = array(
                'মাসিক বেতন',
                'পরীক্ষার ফি',
                'ভর্তি ফি',
                'সেশন ফি',
                'বই ও খাতা বাবদ',
                'অন্যান্য ফি'
            );
            update_option( 'mms_fee_types', $default_fee_types );
        }

        if ( ! get_option( 'mms_expense_categories' ) ) {
            $default_categories = array(
                'বিদ্যুৎ ও গ্যাস বিল',
                'খাবার/বোর্ডিং খরচ',
                'শিক্ষক ও স্টাফ নাস্তা/আপ্যায়ন',
                'বই ও স্টেশনারি',
                'মেরামত ও সংস্কার',
                'মাদ্রাসার ভাড়া',
                'অনুদান/সাহায্য',
                'অন্যান্য খরচ'
            );
            update_option( 'mms_expense_categories', $default_categories );
        }

        if ( ! get_option( 'mms_settings' ) ) {
            $default_settings = array(
                'madrasah_name'    => 'ফরমান আলী মণ্ডল রওজাতুল আতফাল মাদরাসা',
                'madrasah_tagline' => 'দ্বীনি ও আধুনিক শিক্ষার আদর্শ প্রতিষ্ঠান',
                'address'          => 'বাংলাদেশ',
                'phone'            => '01700000000',
                'email'            => 'info@madrasah.com',
                'currency'         => '৳',
                'principal_title'  => 'মুহতামিম / অধ্যক্ষ',
                'accountant_title' => 'হিসাবরক্ষক'
            );
            update_option( 'mms_settings', $default_settings );
        }
    }
}
