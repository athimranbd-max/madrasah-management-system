<?php
defined( 'ABSPATH' ) || exit;

global $wpdb;

$current_month = current_time( 'Y-m' );
$today = current_time( 'Y-m-d' );
$settings = get_option( 'mms_settings', array() );
$currency = isset( $settings['currency'] ) ? $settings['currency'] : '৳';
$madrasah_name = ! empty( $settings['madrasah_name'] ) ? $settings['madrasah_name'] : 'ফরমান আলী মণ্ডল রওজাতুল আতফাল মাদরাসা';
$madrasah_tagline = ! empty( $settings['madrasah_tagline'] ) ? $settings['madrasah_tagline'] : 'দ্বীনি ও আধুনিক শিক্ষার আদর্শ প্রতিষ্ঠান';

// Counts
$total_students = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mms_students WHERE status = 'active'" );
$total_teachers = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mms_teachers WHERE status = 'active'" );

// Today Student Attendance
$today_present_students = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}mms_student_attendance WHERE attendance_date = %s AND status = 'present'",
    $today
) );

// Today Teacher Attendance
$today_present_teachers = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}mms_teacher_attendance WHERE attendance_date = %s AND status = 'present'",
    $today
) );

// This Month Collections (Student Fees)
$month_income = $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}mms_student_fees WHERE paid_date LIKE %s",
    $current_month . '%'
) );

// This Month Teacher Salaries Paid
$month_salaries = $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(net_paid), 0) FROM {$wpdb->prefix}mms_teacher_salaries WHERE payment_date LIKE %s",
    $current_month . '%'
) );

// This Month Madrasah Expenses
$month_expenses = $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}mms_expenses WHERE expense_date LIKE %s",
    $current_month . '%'
) );

$total_month_cost = $month_salaries + $month_expenses;
$net_balance = $month_income - $total_month_cost;

// Recent Fee Collections
$recent_fees = $wpdb->get_results(
    "SELECT f.*, s.name_bn, s.roll_no, s.class_name 
     FROM {$wpdb->prefix}mms_student_fees f 
     JOIN {$wpdb->prefix}mms_students s ON f.student_id = s.id 
     ORDER BY f.id DESC LIMIT 5"
);

// Recent Expenses
$recent_expenses = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}mms_expenses ORDER BY id DESC LIMIT 5"
);
?>

<div class="mms-wrap">
    <!-- Header -->
    <div class="mms-header">
        <div class="mms-header-title">
            <h1><?php echo esc_html( $madrasah_name ); ?></h1>
            <p><?php echo esc_html( $madrasah_tagline ); ?></p>
        </div>
        <div class="mms-header-actions">
            <?php if ( MMS_Admin::is_main_admin() ) : ?>
                <a href="<?php echo admin_url( 'admin.php?page=mms-students' ); ?>" class="mms-btn mms-btn-primary">+ নতুন শিক্ষার্থী</a>
            <?php endif; ?>
            <a href="<?php echo admin_url( 'admin.php?page=mms-student-fees' ); ?>" class="mms-btn mms-btn-success">৳ ফি আদায়</a>
            <?php if ( MMS_Admin::is_main_admin() ) : ?>
                <a href="<?php echo admin_url( 'admin.php?page=mms-expenses' ); ?>" class="mms-btn mms-btn-danger">+ খরচ এন্ট্রি</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="mms-grid-4">
        <div class="mms-card stat-card stat-blue">
            <div class="stat-icon">🎓</div>
            <div class="stat-info">
                <h3><?php echo number_format( $total_students ); ?> জন</h3>
                <p>মোট সক্রিয় শিক্ষার্থী</p>
                <small class="stat-sub">আজ উপস্থিত: <strong><?php echo number_format( $today_present_students ); ?></strong> জন</small>
            </div>
        </div>

        <div class="mms-card stat-card stat-green">
            <div class="stat-icon">👨‍🏫</div>
            <div class="stat-info">
                <h3><?php echo number_format( $total_teachers ); ?> জন</h3>
                <p>মোট শিক্ষক ও স্টাফ</p>
                <small class="stat-sub">আজ উপস্থিত: <strong><?php echo number_format( $today_present_teachers ); ?></strong> জন</small>
            </div>
        </div>

        <div class="mms-card stat-card stat-emerald">
            <div class="stat-icon">📥</div>
            <div class="stat-info">
                <h3><?php echo $currency . ' ' . number_format( $month_income, 2 ); ?></h3>
                <p>চলতি মাসে মোট ফি আদায়</p>
                <small class="stat-sub">মাস: <?php echo date( 'F Y' ); ?></small>
            </div>
        </div>

        <div class="mms-card stat-card stat-red">
            <div class="stat-icon">📤</div>
            <div class="stat-info">
                <h3><?php echo $currency . ' ' . number_format( $total_month_cost, 2 ); ?></h3>
                <p>চলতি মাসে মোট খরচ</p>
                <small class="stat-sub">বেতন: <?php echo $currency . ' ' . number_format( $month_salaries ); ?> | অন্যান্য: <?php echo $currency . ' ' . number_format( $month_expenses ); ?></small>
            </div>
        </div>
    </div>

    <!-- Balance Highlight Banner -->
    <div class="mms-card mms-balance-banner <?php echo $net_balance >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
        <div class="balance-content">
            <div class="balance-label">চলতি মাসের ক্যাশ ব্যালেন্স (উদ্বৃত্ত/ঘাটতি)</div>
            <div class="balance-amount"><?php echo $currency . ' ' . number_format( $net_balance, 2 ); ?></div>
        </div>
        <div class="balance-breakdown">
            <span>মোট আয়: <strong>+<?php echo $currency . ' ' . number_format( $month_income, 2 ); ?></strong></span>
            <span>মোট ব্যয়: <strong>-<?php echo $currency . ' ' . number_format( $total_month_cost, 2 ); ?></strong></span>
            <?php if ( MMS_Admin::is_main_admin() ) : ?>
                <a href="<?php echo admin_url( 'admin.php?page=mms-reports' ); ?>" class="mms-btn mms-btn-outline">বিস্তারিত আয়-ব্যয় রিপোর্ট &rarr;</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tables Grid: Recent Fees & Recent Expenses -->
    <div class="mms-grid-2">
        <!-- Recent Fees -->
        <div class="mms-card">
            <div class="card-header flex-between">
                <h2>সর্বশেষ ফি আদায় (রশিদ)</h2>
                <a href="<?php echo admin_url( 'admin.php?page=mms-student-fees' ); ?>" class="link-more">সব দেখুন &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="mms-table">
                    <thead>
                        <tr>
                            <th>রশিদ নং</th>
                            <th>শিক্ষার্থী</th>
                            <th>ফি ধরণ</th>
                            <th>পরিমাণ</th>
                            <th>রশিদ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $recent_fees ) ) : ?>
                            <?php foreach ( $recent_fees as $f ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $f->voucher_no ); ?></strong></td>
                                    <td>
                                        <div><?php echo esc_html( $f->name_bn ); ?></div>
                                        <small class="text-muted"><?php echo esc_html( $f->class_name ); ?> | রোল: <?php echo esc_html( $f->roll_no ); ?></small>
                                    </td>
                                    <td><span class="badge badge-info"><?php echo esc_html( $f->fee_type ); ?></span></td>
                                    <td><strong><?php echo $currency . ' ' . number_format( $f->amount, 2 ); ?></strong></td>
                                    <td>
                                        <button class="mms-btn-sm mms-btn-outline mms-view-receipt" data-id="<?php echo esc_attr( $f->id ); ?>">প্রিন্ট</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="5" class="text-center text-muted">কোনো রেকর্ড পাওয়া যায়নি।</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Expenses -->
        <div class="mms-card">
            <div class="card-header flex-between">
                <h2>সর্বশেষ মাদ্রাসার খরচ</h2>
                <?php if ( MMS_Admin::is_main_admin() ) : ?>
                    <a href="<?php echo admin_url( 'admin.php?page=mms-expenses' ); ?>" class="link-more">সব দেখুন &rarr;</a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="mms-table">
                    <thead>
                        <tr>
                            <th>ভাউচার</th>
                            <th>বিবরণ</th>
                            <th>ক্যাটাগরি</th>
                            <th>পরিমাণ</th>
                            <th>তারিখ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $recent_expenses ) ) : ?>
                            <?php foreach ( $recent_expenses as $e ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $e->voucher_no ); ?></strong></td>
                                    <td>
                                        <div><?php echo esc_html( $e->title ); ?></div>
                                        <?php if ( ! empty( $e->paid_to ) ) : ?><small class="text-muted">প্রাপক: <?php echo esc_html( $e->paid_to ); ?></small><?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-warning"><?php echo esc_html( $e->category ); ?></span></td>
                                    <td><strong class="text-danger"><?php echo $currency . ' ' . number_format( $e->amount, 2 ); ?></strong></td>
                                    <td><?php echo date( 'd M, Y', strtotime( $e->expense_date ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="5" class="text-center text-muted">কোনো রেকর্ড পাওয়া যায়নি।</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div id="mms-receipt-modal" class="mms-modal">
    <div class="mms-modal-content">
        <div class="mms-modal-header">
            <h3>টাকা জমার মানি রিসিট</h3>
            <span class="mms-modal-close">&times;</span>
        </div>
        <div class="mms-modal-body" id="mms-receipt-content"></div>
        <div class="mms-modal-footer">
            <button class="mms-btn mms-btn-primary" onclick="window.print();">🖨️ প্রিন্ট করুন</button>
            <button class="mms-btn mms-btn-outline mms-modal-close-btn">বন্ধ করুন</button>
        </div>
    </div>
</div>
