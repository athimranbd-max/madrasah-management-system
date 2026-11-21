<?php
defined( 'ABSPATH' ) || exit;

class MMS_Ajax {

    public static function init() {
        // Sub-Admins Management (Main Admin only)
        add_action( 'wp_ajax_mms_save_subadmin', array( __CLASS__, 'save_subadmin' ) );
        add_action( 'wp_ajax_mms_delete_subadmin', array( __CLASS__, 'delete_subadmin' ) );

        // Students
        add_action( 'wp_ajax_mms_save_student', array( __CLASS__, 'save_student' ) );
        add_action( 'wp_ajax_mms_delete_student', array( __CLASS__, 'delete_student' ) );
        add_action( 'wp_ajax_mms_get_students_for_attendance', array( __CLASS__, 'get_students_for_attendance' ) );
        add_action( 'wp_ajax_mms_save_student_attendance', array( __CLASS__, 'save_student_attendance' ) );
        add_action( 'wp_ajax_mms_get_student_ledger', array( __CLASS__, 'get_student_ledger' ) );

        // Fees
        add_action( 'wp_ajax_mms_save_student_fee', array( __CLASS__, 'save_student_fee' ) );
        add_action( 'wp_ajax_mms_delete_student_fee', array( __CLASS__, 'delete_student_fee' ) );
        add_action( 'wp_ajax_mms_get_money_receipt', array( __CLASS__, 'get_money_receipt' ) );

        // Teachers
        add_action( 'wp_ajax_mms_save_teacher', array( __CLASS__, 'save_teacher' ) );
        add_action( 'wp_ajax_mms_delete_teacher', array( __CLASS__, 'delete_teacher' ) );
        add_action( 'wp_ajax_mms_save_teacher_attendance', array( __CLASS__, 'save_teacher_attendance' ) );
        add_action( 'wp_ajax_mms_save_teacher_salary', array( __CLASS__, 'save_teacher_salary' ) );
        add_action( 'wp_ajax_mms_delete_teacher_salary', array( __CLASS__, 'delete_teacher_salary' ) );
        add_action( 'wp_ajax_mms_get_salary_slip', array( __CLASS__, 'get_salary_slip' ) );

        // Expenses
        add_action( 'wp_ajax_mms_save_expense', array( __CLASS__, 'save_expense' ) );
        add_action( 'wp_ajax_mms_delete_expense', array( __CLASS__, 'delete_expense' ) );

        // Settings
        add_action( 'wp_ajax_mms_save_settings', array( __CLASS__, 'save_settings' ) );
    }

    private static function verify_nonce() {
        check_ajax_referer( 'mms_admin_nonce', 'nonce' );
    }

    private static function is_main_admin() {
        return current_user_can( 'manage_options' );
    }

    /* -------------------------------------------------------------
     * SUB-ADMIN MANAGEMENT (Main Admin Only)
     * ----------------------------------------------------------- */
    public static function save_subadmin() {
        self::verify_nonce();
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন সাব-অ্যাডমিন তৈরি করতে পারেন।' ) );
        }

        $user_id        = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
        $raw_username   = isset( $_POST['username'] ) ? trim( $_POST['username'] ) : '';
        $name           = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $password       = isset( $_POST['password'] ) ? trim( $_POST['password'] ) : '';
        $assigned_class = isset( $_POST['assigned_class'] ) ? sanitize_text_field( $_POST['assigned_class'] ) : '';
        $designation    = isset( $_POST['designation'] ) ? sanitize_text_field( $_POST['designation'] ) : '';
        $email_input    = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

        if ( empty( $raw_username ) || empty( $name ) || empty( $assigned_class ) ) {
            wp_send_json_error( array( 'message' => 'শিক্ষকের পুরো নাম, ইউজার আইডি ও দায়িত্বপ্রাপ্ত শ্রেণি নির্বাচন আবশ্যক!' ) );
        }

        // Clean username: lowercase, letters, numbers, hyphens and underscores
        $username = sanitize_user( $raw_username, true );
        if ( empty( $username ) ) {
            $username = preg_replace( '/[^a-z0-9_-]/', '', strtolower( sanitize_title( $raw_username ) ) );
            if ( empty( $username ) ) {
                $username = 'subadmin_' . wp_rand( 1000, 9999 );
            }
        }

        if ( $user_id > 0 ) {
            // Update existing sub-admin
            $user_data = array(
                'ID'           => $user_id,
                'display_name' => $name,
            );
            if ( ! empty( $password ) ) {
                $user_data['user_pass'] = $password;
            }
            if ( ! empty( $email_input ) && is_email( $email_input ) ) {
                $user_data['user_email'] = $email_input;
            }
            $updated = wp_update_user( $user_data );
            if ( is_wp_error( $updated ) ) {
                wp_send_json_error( array( 'message' => $updated->get_error_message() ) );
            }

            update_user_meta( $user_id, 'mms_assigned_class', $assigned_class );
            update_user_meta( $user_id, 'mms_designation', $designation );

            wp_send_json_success( array( 'message' => 'সাব-অ্যাডমিন তথ্য সফলভাবে আপডেট হয়েছে!' ) );
        } else {
            // Create new sub-admin
            if ( empty( $password ) ) {
                wp_send_json_error( array( 'message' => 'লগইন পাসওয়ার্ড প্রদান করা আবশ্যক!' ) );
            }

            if ( username_exists( $username ) ) {
                wp_send_json_error( array( 'message' => 'ইউজার আইডি "' . esc_html( $username ) . '" ইতিমধ্যে বিদ্যমান! দয়া করে অন্য ইউজার আইডি দিন।' ) );
            }

            // Generate safe email
            if ( ! empty( $email_input ) && is_email( $email_input ) ) {
                if ( email_exists( $email_input ) ) {
                    wp_send_json_error( array( 'message' => 'এই ইমেইলটি ইতিমধ্যে ব্যবহৃত হয়েছে! অন্য ইমেইল দিন।' ) );
                }
                $user_email = $email_input;
            } else {
                $host = ! empty( $_SERVER['HTTP_HOST'] ) ? preg_replace( '/[^a-zA-Z0-9\.\-]/', '', $_SERVER['HTTP_HOST'] ) : 'madrasah.local';
                if ( empty( $host ) || strpos( $host, '.' ) === false ) {
                    $host = 'madrasah.local';
                }
                $clean_slug = preg_replace( '/[^a-z0-9]/', '', strtolower( $username ) );
                if ( empty( $clean_slug ) ) $clean_slug = 'user' . wp_rand( 100, 999 );
                $user_email = $clean_slug . '@' . $host;
                $counter = 1;
                while ( email_exists( $user_email ) ) {
                    $user_email = $clean_slug . '_' . $counter . '@' . $host;
                    $counter++;
                }
            }

            $userdata = array(
                'user_login'   => $username,
                'user_pass'    => $password,
                'user_email'   => $user_email,
                'display_name' => $name,
                'role'         => 'mms_subadmin',
            );

            $new_user_id = wp_insert_user( $userdata );

            if ( is_wp_error( $new_user_id ) ) {
                wp_send_json_error( array( 'message' => 'ইউজার তৈরি করা সম্ভব হয়নি: ' . $new_user_id->get_error_message() ) );
            }

            update_user_meta( $new_user_id, 'mms_assigned_class', $assigned_class );
            update_user_meta( $new_user_id, 'mms_designation', $designation );

            wp_send_json_success( array( 
                'message'  => 'নতুন সাব-অ্যাডমিন (' . $name . ') সফলভাবে তৈরি করা হয়েছে!',
                'user_id'  => $new_user_id,
                'username' => $username,
            ) );
        }
    }

    public static function delete_subadmin() {
        self::verify_nonce();
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন ইউজার মুছতে পারেন।' ) );
        }

        $user_id = absint( $_POST['user_id'] );
        if ( $user_id === get_current_user_id() ) {
            wp_send_json_error( array( 'message' => 'আপনি নিজের অ্যাকাউন্ট মুছতে পারবেন না!' ) );
        }

        require_once( ABSPATH . 'wp-admin/includes/user.php' );
        $deleted = wp_delete_user( $user_id );

        if ( $deleted ) {
            wp_send_json_success( array( 'message' => 'সাব-অ্যাডমিন অ্যাকাউন্ট মুছে ফেলা হয়েছে!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'ইউজার মোছা সম্ভব হয়নি!' ) );
        }
    }

    /* -------------------------------------------------------------
     * STUDENTS
     * ----------------------------------------------------------- */
    public static function save_student() {
        self::verify_nonce();
        global $wpdb;
        $table = $wpdb->prefix . 'mms_students';

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        // Sub-admin CANNOT add new students
        if ( $id === 0 && ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! সাব-অ্যাডমিন নতুন শিক্ষার্থী যুক্ত বা তৈরি করতে পারবেন না।' ) );
        }

        $name_bn       = sanitize_text_field( $_POST['name_bn'] );
        $name_en       = sanitize_text_field( $_POST['name_en'] );
        $class_name    = sanitize_text_field( $_POST['class_name'] );
        $roll_no       = absint( $_POST['roll_no'] );
        $guardian_name = sanitize_text_field( $_POST['guardian_name'] );
        $phone         = sanitize_text_field( $_POST['phone'] );
        $monthly_fee   = floatval( $_POST['monthly_fee'] );
        $admission_date= ! empty( $_POST['admission_date'] ) ? sanitize_text_field( $_POST['admission_date'] ) : current_time( 'Y-m-d' );
        $address       = sanitize_textarea_field( $_POST['address'] );
        $status        = sanitize_text_field( $_POST['status'] );

        if ( empty( $name_bn ) || empty( $class_name ) ) {
            wp_send_json_error( array( 'message' => 'শিক্ষার্থীর নাম ও শ্রেণি আবশ্যক!' ) );
        }

        $data = array(
            'name_bn'        => $name_bn,
            'name_en'        => $name_en,
            'class_name'     => $class_name,
            'roll_no'        => $roll_no,
            'guardian_name'  => $guardian_name,
            'phone'          => $phone,
            'monthly_fee'    => $monthly_fee,
            'admission_date' => $admission_date,
            'address'        => $address,
            'status'         => $status ? $status : 'active',
        );

        if ( $id > 0 ) {
            $wpdb->update( $table, $data, array( 'id' => $id ) );
            wp_send_json_success( array( 'message' => 'শিক্ষার্থীর তথ্য সফলভাবে আপডেট হয়েছে!' ) );
        } else {
            $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) + 1;
            $student_id = 'STU-' . date( 'Y' ) . '-' . str_pad( $count, 4, '0', STR_PAD_LEFT );
            $data['student_id'] = $student_id;
            
            $wpdb->insert( $table, $data );
            wp_send_json_success( array( 'message' => 'শিক্ষার্থী সফলভাবে ভর্তি করা হয়েছে!' ) );
        }
    }

    public static function delete_student() {
        self::verify_nonce();
        // Strictly Main Admin Only
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন শিক্ষার্থী মুছে ফেলতে পারবেন।' ) );
        }

        global $wpdb;
        $id = absint( $_POST['id'] );
        if ( $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'mms_students', array( 'id' => $id ) );
            wp_send_json_success( array( 'message' => 'শিক্ষার্থী মুছে ফেলা হয়েছে!' ) );
        }
        wp_send_json_error( array( 'message' => 'অকার্যকর অনুরোধ!' ) );
    }

    public static function get_students_for_attendance() {
        self::verify_nonce();
        global $wpdb;

        $class_name = sanitize_text_field( $_POST['class_name'] );
        $date       = sanitize_text_field( $_POST['date'] );

        // If sub-admin has an assigned class, check it
        $assigned = MMS_Admin::get_assigned_class();
        if ( ! self::is_main_admin() && $assigned !== 'all' && $class_name !== $assigned ) {
            wp_send_json_error( array( 'message' => 'আপনার শুধুমাত্র ' . $assigned . ' শ্রেণির হাজিরা নেওয়ার অনুমতি আছে।' ) );
        }

        $table_students   = $wpdb->prefix . 'mms_students';
        $table_attendance = $wpdb->prefix . 'mms_student_attendance';

        $students = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.id, s.student_id, s.name_bn, s.roll_no, 
                    COALESCE(a.status, 'present') as attendance_status, 
                    COALESCE(a.remarks, '') as remarks
             FROM $table_students s
             LEFT JOIN $table_attendance a ON s.id = a.student_id AND a.attendance_date = %s
             WHERE s.class_name = %s AND s.status = 'active'
             ORDER BY s.roll_no ASC",
            $date,
            $class_name
        ) );

        wp_send_json_success( array( 'students' => $students ) );
    }

    public static function save_student_attendance() {
        self::verify_nonce();
        global $wpdb;
        $table = $wpdb->prefix . 'mms_student_attendance';

        $class_name  = sanitize_text_field( $_POST['class_name'] );
        $date        = sanitize_text_field( $_POST['date'] );
        $attendances = isset( $_POST['attendance'] ) ? (array) $_POST['attendance'] : array();

        if ( empty( $class_name ) || empty( $date ) ) {
            wp_send_json_error( array( 'message' => 'শ্রেণি ও তারিখ আবশ্যক!' ) );
        }

        // Check sub-admin assigned class
        $assigned = MMS_Admin::get_assigned_class();
        if ( ! self::is_main_admin() && $assigned !== 'all' && $class_name !== $assigned ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! আপনি শুধুমাত্র ' . $assigned . ' শ্রেণির হাজিরা দিতে পারবেন।' ) );
        }

        foreach ( $attendances as $student_id => $info ) {
            $student_id = absint( $student_id );
            $status     = sanitize_text_field( $info['status'] );
            $remarks    = sanitize_text_field( $info['remarks'] );

            $wpdb->replace(
                $table,
                array(
                    'student_id'      => $student_id,
                    'class_name'      => $class_name,
                    'attendance_date' => $date,
                    'status'          => $status,
                    'remarks'         => $remarks,
                ),
                array( '%d', '%s', '%s', '%s', '%s' )
            );
        }

        wp_send_json_success( array( 'message' => 'হাজিরা সফলভাবে সংরক্ষিত হয়েছে!' ) );
    }

    /* -------------------------------------------------------------
     * STUDENT FEES
     * ----------------------------------------------------------- */
    public static function save_student_fee() {
        self::verify_nonce();
        global $wpdb;
        $table = $wpdb->prefix . 'mms_student_fees';

        $student_id     = absint( $_POST['student_id'] );
        $fee_type       = sanitize_text_field( $_POST['fee_type'] );
        $month_year     = sanitize_text_field( $_POST['month_year'] );
        $amount         = floatval( $_POST['amount'] );
        $paid_date      = ! empty( $_POST['paid_date'] ) ? sanitize_text_field( $_POST['paid_date'] ) : current_time( 'Y-m-d' );
        $payment_method = sanitize_text_field( $_POST['payment_method'] );
        $remarks        = sanitize_textarea_field( $_POST['remarks'] );

        if ( $student_id <= 0 || $amount <= 0 ) {
            wp_send_json_error( array( 'message' => 'সঠিক শিক্ষার্থী ও ফি-এর পরিমাণ প্রদান করুন!' ) );
        }

        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) + 1;
        $voucher_no = 'MR-' . date( 'ym' ) . '-' . str_pad( $count, 4, '0', STR_PAD_LEFT );

        $data = array(
            'voucher_no'     => $voucher_no,
            'student_id'     => $student_id,
            'fee_type'       => $fee_type,
            'month_year'     => $month_year,
            'amount'         => $amount,
            'paid_date'      => $paid_date,
            'payment_method' => $payment_method ? $payment_method : 'Cash',
            'remarks'        => $remarks,
            'created_by'     => get_current_user_id(),
        );

        $inserted = $wpdb->insert( $table, $data );

        if ( $inserted ) {
            $fee_id = $wpdb->insert_id;
            wp_send_json_success( array(
                'message'    => 'ফি সফলভাবে জমা হয়েছে!',
                'fee_id'     => $fee_id,
                'voucher_no' => $voucher_no
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'ফি জমা করা সম্ভব হয়নি!' ) );
        }
    }

    public static function delete_student_fee() {
        self::verify_nonce();
        // Strictly Main Admin Only
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন ফি রেকর্ড মুছতে পারবেন।' ) );
        }

        global $wpdb;
        $id = absint( $_POST['id'] );
        if ( $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'mms_student_fees', array( 'id' => $id ) );
            wp_send_json_success( array( 'message' => 'ফি রেকর্ড মুছে ফেলা হয়েছে!' ) );
        }
        wp_send_json_error( array( 'message' => 'অকার্যকর অনুরোধ!' ) );
    }

    public static function get_student_ledger() {
        self::verify_nonce();
        global $wpdb;

        $student_id = absint( $_POST['student_id'] );
        $year = isset( $_POST['year'] ) ? absint( $_POST['year'] ) : date( 'Y' );

        $table_students = $wpdb->prefix . 'mms_students';
        $table_fees = $wpdb->prefix . 'mms_student_fees';

        $student = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_students WHERE id = %d", $student_id ) );

        if ( ! $student ) {
            wp_send_json_error( array( 'message' => 'শিক্ষার্থী পাওয়া যায়নি!' ) );
        }

        $settings = get_option( 'mms_settings', array() );
        $currency = isset( $settings['currency'] ) ? $settings['currency'] : '৳';

        // Fetch all tuition fees for this student
        $all_fees = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_fees WHERE student_id = %d ORDER BY paid_date DESC",
            $student_id
        ) );

        $months_bn = array(
            1  => array( 'name' => 'জানুয়ারি', 'en' => 'January' ),
            2  => array( 'name' => 'ফেব্রুয়ারি', 'en' => 'February' ),
            3  => array( 'name' => 'মার্চ', 'en' => 'March' ),
            4  => array( 'name' => 'এপ্রিল', 'en' => 'April' ),
            5  => array( 'name' => 'মে', 'en' => 'May' ),
            6  => array( 'name' => 'জুন', 'en' => 'June' ),
            7  => array( 'name' => 'জুলাই', 'en' => 'July' ),
            8  => array( 'name' => 'আগস্ট', 'en' => 'August' ),
            9  => array( 'name' => 'সেপ্টেম্বর', 'en' => 'September' ),
            10 => array( 'name' => 'অক্টোবর', 'en' => 'October' ),
            11 => array( 'name' => 'নভেম্বর', 'en' => 'November' ),
            12 => array( 'name' => 'ডিসেম্বর', 'en' => 'December' ),
        );

        $current_year = date( 'Y' );
        $current_month = date( 'n' );

        $paid_tuition_total = 0;
        $due_tuition_total = 0;
        $other_fees_total = 0;

        // Month status mapping
        $month_status = array();
        foreach ( $months_bn as $m_num => $m_data ) {
            $month_status[$m_num] = array(
                'name'    => $m_data['name'] . ' ' . $year,
                'status'  => ( $year < $current_year || ( $year == $current_year && $m_num <= $current_month ) ) ? 'due' : 'upcoming',
                'amount'  => $student->monthly_fee,
                'paid_amount' => 0,
                'paid_date'   => '',
                'voucher_no'  => '',
                'method'      => '',
                'fee_id'      => 0,
            );
        }

        foreach ( $all_fees as $f ) {
            if ( $f->fee_type === 'মাসিক বেতন' ) {
                foreach ( $months_bn as $m_num => $m_data ) {
                    // Check if month name or English name matches in month_year or paid_date
                    if ( stripos( $f->month_year, $m_data['name'] ) !== false || 
                         stripos( $f->month_year, $m_data['en'] ) !== false ||
                         date( 'Y-n', strtotime( $f->paid_date ) ) === ($year . '-' . $m_num) ) {
                        $month_status[$m_num]['status']      = 'paid';
                        $month_status[$m_num]['paid_amount'] = $f->amount;
                        $month_status[$m_num]['paid_date']   = $f->paid_date;
                        $month_status[$m_num]['voucher_no']  = $f->voucher_no;
                        $month_status[$m_num]['method']      = $f->payment_method;
                        $month_status[$m_num]['fee_id']      = $f->id;
                        $paid_tuition_total += $f->amount;
                        break;
                    }
                }
            } else {
                $other_fees_total += $f->amount;
            }
        }

        // Calculate dues
        foreach ( $month_status as $m_num => $m_info ) {
            if ( $m_info['status'] === 'due' ) {
                $due_tuition_total += $m_info['amount'];
            }
        }

        ob_start();
        ?>
        <div class="mms-student-ledger-wrap">
            <!-- Student Header Profile Box -->
            <div class="mms-profile-header-box" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:15px; margin-bottom:18px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h2 style="margin:0 0 4px 0; font-size:20px; color:#1e3a8a;">
                            <?php echo esc_html( $student->name_bn ); ?>
                            <span style="font-size:13px; font-weight:normal; color:#64748b; font-family:monospace;">(<?php echo esc_html( $student->student_id ); ?>)</span>
                        </h2>
                        <div style="font-size:13px; color:#475569; display:flex; gap:15px; flex-wrap:wrap;">
                            <span>শ্রেণি: <strong><?php echo esc_html( $student->class_name ); ?></strong></span>
                            <span>রোল নং: <strong><?php echo esc_html( $student->roll_no ); ?></strong></span>
                            <span>অভিভাবকের নাম: <strong><?php echo esc_html( $student->guardian_name ? $student->guardian_name : '-' ); ?></strong></span>
                            <span>মোবাইল: <strong style="font-family:monospace;"><?php echo esc_html( $student->phone ? $student->phone : '-' ); ?></strong></span>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <span class="badge badge-class" style="font-size:13px;">মাসিক বেতন: <?php echo $currency . ' ' . number_format( $student->monthly_fee, 2 ); ?></span>
                    </div>
                </div>
            </div>

            <!-- 3 Stat Badges: Paid, Due, Other -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:20px;">
                <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; padding:12px; text-align:center;">
                    <div style="font-size:12px; color:#065f46;">মোট পরিশোধিত বেতন (<?php echo $year; ?>)</div>
                    <div style="font-size:20px; font-weight:700; color:#047857;"><?php echo $currency . ' ' . number_format( $paid_tuition_total, 2 ); ?></div>
                </div>
                <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px; text-align:center;">
                    <div style="font-size:12px; color:#991b1b;">মোট বকেয়া বেতন (চলতি সময় পর্যন্ত)</div>
                    <div style="font-size:20px; font-weight:700; color:#b91c1c;"><?php echo $currency . ' ' . number_format( $due_tuition_total, 2 ); ?></div>
                </div>
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px; text-align:center;">
                    <div style="font-size:12px; color:#1e40af;">পরীক্ষার ফি ও অন্যান্য আদায়</div>
                    <div style="font-size:20px; font-weight:700; color:#1d4ed8;"><?php echo $currency . ' ' . number_format( $other_fees_total, 2 ); ?></div>
                </div>
            </div>

            <!-- Month-by-Month Fee Register Table -->
            <h3 style="font-size:16px; margin:0 0 10px 0; color:#0f172a; border-bottom:2px solid #0284c7; padding-bottom:6px;">
                📅 <?php echo $year; ?> সালের মাসিক বেতন পরিশোধ ও বকেয়া বিবরণী (১২ মাস):
            </h3>
            <div class="table-responsive">
                <table class="mms-table" style="font-size:13px;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th>মাস</th>
                            <th>নির্ধারিত ফি</th>
                            <th>স্ট্যাটাস</th>
                            <th>পরিশোধের তারিখ</th>
                            <th>রশিদ নং</th>
                            <th>মাধ্যম</th>
                            <th class="text-center">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $month_status as $m_num => $m_info ) : ?>
                            <tr style="<?php echo $m_info['status'] === 'due' ? 'background:#fff7ed;' : ''; ?>">
                                <td><strong><?php echo esc_html( $m_info['name'] ); ?></strong></td>
                                <td><?php echo $currency . ' ' . number_format( $m_info['amount'], 2 ); ?></td>
                                <td>
                                    <?php if ( $m_info['status'] === 'paid' ) : ?>
                                        <span class="badge badge-success">✓ পরিশোধিত (Paid)</span>
                                    <?php elseif ( $m_info['status'] === 'due' ) : ?>
                                        <span class="badge badge-danger">✗ বকেয়া (Due)</span>
                                    <?php else : ?>
                                        <span class="badge" style="background:#f1f5f9; color:#64748b;">আসন্ন (Upcoming)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $m_info['paid_date'] ? date( 'd-m-Y', strtotime( $m_info['paid_date'] ) ) : '-'; ?></td>
                                <td>
                                    <?php if ( $m_info['voucher_no'] ) : ?>
                                        <strong style="color:#2563eb; font-family:monospace;"><?php echo esc_html( $m_info['voucher_no'] ); ?></strong>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $m_info['method'] ? $m_info['method'] : '-' ); ?></td>
                                <td class="text-center">
                                    <?php if ( $m_info['status'] === 'paid' && $m_info['fee_id'] ) : ?>
                                        <button class="mms-btn-sm mms-btn-primary mms-view-receipt" data-id="<?php echo esc_attr( $m_info['fee_id'] ); ?>">🖨️ রশিদ প্রিন্ট</button>
                                    <?php elseif ( $m_info['status'] === 'due' ) : ?>
                                        <button class="mms-btn-sm mms-btn-success mms-quick-collect" 
                                                data-student-id="<?php echo esc_attr( $student->id ); ?>" 
                                                data-fee="<?php echo esc_attr( $student->monthly_fee ); ?>"
                                                data-month="<?php echo esc_attr( $m_info['name'] ); ?>">
                                            ৳ জমা নিন
                                        </button>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Complete History of All Fee Transactions -->
            <?php if ( ! empty( $all_fees ) ) : ?>
                <h3 style="font-size:16px; margin:24px 0 10px 0; color:#0f172a; border-bottom:2px solid #64748b; padding-bottom:6px;">
                    📜 শিক্ষার্থীর সকল ফি জমা দেওয়ার রেকর্ড (ইতিহাস):
                </h3>
                <div class="table-responsive">
                    <table class="mms-table" style="font-size:12px;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th>রশিদ নং</th>
                                <th>ফি ধরণ</th>
                                <th>মাস/বিবরণ</th>
                                <th>পরিমাণ</th>
                                <th>পেমেন্ট মাধ্যম</th>
                                <th>তারিখ</th>
                                <th>রশিদ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $all_fees as $af ) : ?>
                                <tr>
                                    <td><strong style="font-family:monospace; color:#2563eb;"><?php echo esc_html( $af->voucher_no ); ?></strong></td>
                                    <td><span class="badge badge-info"><?php echo esc_html( $af->fee_type ); ?></span></td>
                                    <td><?php echo esc_html( $af->month_year ? $af->month_year : '-' ); ?></td>
                                    <td><strong><?php echo $currency . ' ' . number_format( $af->amount, 2 ); ?></strong></td>
                                    <td><?php echo esc_html( $af->payment_method ); ?></td>
                                    <td><?php echo date( 'd-m-Y', strtotime( $af->paid_date ) ); ?></td>
                                    <td>
                                        <button class="mms-btn-sm mms-btn-primary mms-view-receipt" data-id="<?php echo esc_attr( $af->id ); ?>">🖨️ রশিদ</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success( array( 'html' => $html ) );
    }


    /* -------------------------------------------------------------
     * TEACHERS & SALARY (Main Admin Only)
     * ----------------------------------------------------------- */
    public static function save_teacher() {
        self::verify_nonce();
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন শিক্ষক যুক্ত করতে পারেন।' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mms_teachers';

        $id             = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $name           = sanitize_text_field( $_POST['name'] );
        $designation    = sanitize_text_field( $_POST['designation'] );
        $phone          = sanitize_text_field( $_POST['phone'] );
        $monthly_salary = floatval( $_POST['monthly_salary'] );
        $joining_date   = ! empty( $_POST['joining_date'] ) ? sanitize_text_field( $_POST['joining_date'] ) : current_time( 'Y-m-d' );
        $address        = sanitize_textarea_field( $_POST['address'] );
        $status         = sanitize_text_field( $_POST['status'] );

        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => 'শিক্ষকের নাম আবশ্যক!' ) );
        }

        $data = array(
            'name'           => $name,
            'designation'    => $designation ? $designation : 'সহকারী শিক্ষক',
            'phone'          => $phone,
            'monthly_salary' => $monthly_salary,
            'joining_date'   => $joining_date,
            'address'        => $address,
            'status'         => $status ? $status : 'active',
        );

        if ( $id > 0 ) {
            $wpdb->update( $table, $data, array( 'id' => $id ) );
            wp_send_json_success( array( 'message' => 'শিক্ষকের তথ্য আপডেট হয়েছে!' ) );
        } else {
            $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) + 1;
            $data['teacher_id'] = 'TCH-' . str_pad( $count, 3, '0', STR_PAD_LEFT );
            $wpdb->insert( $table, $data );
            wp_send_json_success( array( 'message' => 'নতুন শিক্ষক সফলভাবে যুক্ত হয়েছে!' ) );
        }
    }

    public static function delete_teacher() {
        self::verify_nonce();
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন শিক্ষক মুছতে পারেন।' ) );
        }

        global $wpdb;
        $id = absint( $_POST['id'] );
        if ( $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'mms_teachers', array( 'id' => $id ) );
            wp_send_json_success( array( 'message' => 'শিক্ষক তথ্য মুছে ফেলা হয়েছে!' ) );
        }
        wp_send_json_error( array( 'message' => 'অকার্যকর অনুরোধ!' ) );
    }

    public static function save_teacher_attendance() {
        self::verify_nonce();
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন শিক্ষকদের হাজিরা নিতে পারেন।' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mms_teacher_attendance';

        $date        = sanitize_text_field( $_POST['date'] );
        $attendances = isset( $_POST['attendance'] ) ? (array) $_POST['attendance'] : array();

        if ( empty( $date ) ) {
            wp_send_json_error( array( 'message' => 'তারিখ আবশ্যক!' ) );
        }

        foreach ( $attendances as $teacher_id => $info ) {
            $teacher_id = absint( $teacher_id );
            $status     = sanitize_text_field( $info['status'] );
            $remarks    = sanitize_text_field( $info['remarks'] );

            $wpdb->replace(
                $table,
                array(
                    'teacher_id'      => $teacher_id,
                    'attendance_date' => $date,
                    'status'          => $status,
                    'remarks'         => $remarks,
                ),
                array( '%d', '%s', '%s', '%s' )
            );
        }

        wp_send_json_success( array( 'message' => 'শিক্ষকদের হাজিরা সফলভাবে সংরক্ষিত হয়েছে!' ) );
    }

    public static function save_teacher_salary() {
        self::verify_nonce();
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন বেতন পরিশোধ করতে পারেন।' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mms_teacher_salaries';

        $teacher_id     = absint( $_POST['teacher_id'] );
        $month_year     = sanitize_text_field( $_POST['month_year'] );
        $basic_salary   = floatval( $_POST['basic_salary'] );
        $bonus          = floatval( $_POST['bonus'] );
        $deduction      = floatval( $_POST['deduction'] );
        $net_paid       = floatval( $_POST['net_paid'] );
        $payment_date   = ! empty( $_POST['payment_date'] ) ? sanitize_text_field( $_POST['payment_date'] ) : current_time( 'Y-m-d' );
        $payment_method = sanitize_text_field( $_POST['payment_method'] );
        $remarks        = sanitize_textarea_field( $_POST['remarks'] );

        if ( $teacher_id <= 0 || $net_paid <= 0 ) {
            wp_send_json_error( array( 'message' => 'সঠিক শিক্ষক ও প্রদেয় বেতনের পরিমাণ প্রদান করুন!' ) );
        }

        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) + 1;
        $voucher_no = 'SAL-' . date( 'ym' ) . '-' . str_pad( $count, 4, '0', STR_PAD_LEFT );

        $data = array(
            'voucher_no'     => $voucher_no,
            'teacher_id'     => $teacher_id,
            'month_year'     => $month_year,
            'basic_salary'   => $basic_salary,
            'bonus'          => $bonus,
            'deduction'      => $deduction,
            'net_paid'       => $net_paid,
            'payment_date'   => $payment_date,
            'payment_method' => $payment_method ? $payment_method : 'Cash',
            'remarks'        => $remarks,
            'created_by'     => get_current_user_id(),
        );

        $inserted = $wpdb->insert( $table, $data );

        if ( $inserted ) {
            wp_send_json_success( array(
                'message'   => 'বেতন সফলভাবে পরিশোধ করা হয়েছে!',
                'salary_id' => $wpdb->insert_id
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'বেতন রেকর্ড সংরক্ষণ সম্ভব হয়নি!' ) );
        }
    }

    public static function delete_teacher_salary() {
        self::verify_nonce();
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন বেতন রেকর্ড মুছতে পারবেন।' ) );
        }

        global $wpdb;
        $id = absint( $_POST['id'] );
        if ( $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'mms_teacher_salaries', array( 'id' => $id ) );
            wp_send_json_success( array( 'message' => 'বেতন রেকর্ড মুছে ফেলা হয়েছে!' ) );
        }
        wp_send_json_error( array( 'message' => 'অকার্যকর অনুরোধ!' ) );
    }

    public static function get_salary_slip() {
        self::verify_nonce();
        $salary_id = absint( $_POST['salary_id'] );
        $html = MMS_Receipt::get_salary_slip_html( $salary_id );
        wp_send_json_success( array( 'html' => $html ) );
    }

    /* -------------------------------------------------------------
     * EXPENSES (Main Admin Only)
     * ----------------------------------------------------------- */
    public static function save_expense() {
        self::verify_nonce();
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন মাদ্রাসার খরচ এন্ট্রি করতে পারবেন।' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mms_expenses';

        $title        = sanitize_text_field( $_POST['title'] );
        $category     = sanitize_text_field( $_POST['category'] );
        $amount       = floatval( $_POST['amount'] );
        $expense_date = ! empty( $_POST['expense_date'] ) ? sanitize_text_field( $_POST['expense_date'] ) : current_time( 'Y-m-d' );
        $paid_to      = sanitize_text_field( $_POST['paid_to'] );
        $remarks      = sanitize_textarea_field( $_POST['remarks'] );

        if ( empty( $title ) || $amount <= 0 ) {
            wp_send_json_error( array( 'message' => 'খরচের বিবরণ ও সঠিক পরিমাণ লিখুন!' ) );
        }

        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) + 1;
        $voucher_no = 'EXP-' . date( 'ym' ) . '-' . str_pad( $count, 4, '0', STR_PAD_LEFT );

        $data = array(
            'voucher_no'   => $voucher_no,
            'title'        => $title,
            'category'     => $category ? $category : 'অন্যান্য খরচ',
            'amount'       => $amount,
            'expense_date' => $expense_date,
            'paid_to'      => $paid_to,
            'remarks'      => $remarks,
            'created_by'   => get_current_user_id(),
        );

        $inserted = $wpdb->insert( $table, $data );

        if ( $inserted ) {
            wp_send_json_success( array( 'message' => 'খরচ সফলভাবে এন্ট্রি করা হয়েছে!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'খরচ এন্ট্রি করা সম্ভব হয়নি!' ) );
        }
    }

    public static function delete_expense() {
        self::verify_nonce();
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন খরচের রেকর্ড মুছতে পারবেন।' ) );
        }

        global $wpdb;
        $id = absint( $_POST['id'] );
        if ( $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'mms_expenses', array( 'id' => $id ) );
            wp_send_json_success( array( 'message' => 'খরচের রেকর্ড মুছে ফেলা হয়েছে!' ) );
        }
        wp_send_json_error( array( 'message' => 'অকার্যকর অনুরোধ!' ) );
    }

    /* -------------------------------------------------------------
     * SETTINGS (Main Admin Only)
     * ----------------------------------------------------------- */
    public static function save_settings() {
        self::verify_nonce();
        if ( ! self::is_main_admin() ) {
            wp_send_json_error( array( 'message' => 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন সেটিংস পরিবর্তন করতে পারেন।' ) );
        }

        $settings = array(
            'madrasah_name'    => sanitize_text_field( $_POST['madrasah_name'] ),
            'madrasah_tagline' => sanitize_text_field( $_POST['madrasah_tagline'] ),
            'address'          => sanitize_textarea_field( $_POST['address'] ),
            'phone'            => sanitize_text_field( $_POST['phone'] ),
            'email'            => sanitize_email( $_POST['email'] ),
            'currency'         => sanitize_text_field( $_POST['currency'] ),
            'principal_title'  => sanitize_text_field( $_POST['principal_title'] ),
            'accountant_title' => sanitize_text_field( $_POST['accountant_title'] ),
        );
        update_option( 'mms_settings', $settings );

        if ( isset( $_POST['classes'] ) ) {
            $raw_classes = explode( "\n", str_replace( "\r", "", sanitize_textarea_field( $_POST['classes'] ) ) );
            $classes = array();
            foreach ( $raw_classes as $c ) {
                $c = trim( $c );
                if ( ! empty( $c ) ) $classes[] = $c;
            }
            if ( ! empty( $classes ) ) update_option( 'mms_classes', $classes );
        }

        if ( isset( $_POST['fee_types'] ) ) {
            $raw_fee_types = explode( "\n", str_replace( "\r", "", sanitize_textarea_field( $_POST['fee_types'] ) ) );
            $fee_types = array();
            foreach ( $raw_fee_types as $f ) {
                $f = trim( $f );
                if ( ! empty( $f ) ) $fee_types[] = $f;
            }
            if ( ! empty( $fee_types ) ) update_option( 'mms_fee_types', $fee_types );
        }

        if ( isset( $_POST['expense_categories'] ) ) {
            $raw_cats = explode( "\n", str_replace( "\r", "", sanitize_textarea_field( $_POST['expense_categories'] ) ) );
            $cats = array();
            foreach ( $raw_cats as $cat ) {
                $cat = trim( $cat );
                if ( ! empty( $cat ) ) $cats[] = $cat;
            }
            if ( ! empty( $cats ) ) update_option( 'mms_expense_categories', $cats );
        }

        wp_send_json_success( array( 'message' => 'সেটিংস সফলভাবে সংরক্ষিত হয়েছে!' ) );
    }
}
