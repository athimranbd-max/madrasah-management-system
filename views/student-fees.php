<?php
defined( 'ABSPATH' ) || exit;

global $wpdb;

$classes = get_option( 'mms_classes', array( 'শিশু ১', 'শিশু ২', '১ম শ্রেণি', '২য় শ্রেণি', '৩য় শ্রেণি' ) );
$fee_types = get_option( 'mms_fee_types', array( 'মাসিক বেতন', 'পরীক্ষার ফি', 'ভর্তি ফি', 'সেশন ফি', 'অন্যান্য ফি' ) );
$settings = get_option( 'mms_settings', array() );
$currency = isset( $settings['currency'] ) ? $settings['currency'] : '৳';

$is_main_admin = MMS_Admin::is_main_admin();
$assigned_class = MMS_Admin::get_assigned_class();

// Active Students for fee modal
if ( ! $is_main_admin && $assigned_class !== 'all' ) {
    $all_students = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, student_id, name_bn, class_name, roll_no, monthly_fee, phone 
         FROM {$wpdb->prefix}mms_students 
         WHERE status = 'active' AND class_name = %s 
         ORDER BY roll_no ASC",
        $assigned_class
    ) );
} else {
    $all_students = $wpdb->get_results( "SELECT id, student_id, name_bn, class_name, roll_no, monthly_fee, phone FROM {$wpdb->prefix}mms_students WHERE status = 'active' ORDER BY class_name ASC, roll_no ASC" );
}

// Filters
if ( ! $is_main_admin && $assigned_class !== 'all' ) {
    $selected_class = $assigned_class;
} else {
    $selected_class  = isset( $_GET['class'] ) ? sanitize_text_field( $_GET['class'] ) : '';
}

$selected_fee_type = isset( $_GET['fee_type'] ) ? sanitize_text_field( $_GET['fee_type'] ) : '';
$selected_method = isset( $_GET['method'] ) ? sanitize_text_field( $_GET['method'] ) : '';
$from_date       = isset( $_GET['from_date'] ) ? sanitize_text_field( $_GET['from_date'] ) : '';
$to_date         = isset( $_GET['to_date'] ) ? sanitize_text_field( $_GET['to_date'] ) : '';
$search_query    = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$active_tab      = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'history';

// Fee History Query
$table_fees = $wpdb->prefix . 'mms_student_fees';
$table_students = $wpdb->prefix . 'mms_students';

$where = array( "1=1" );
$params = array();

if ( ! empty( $selected_class ) ) {
    $where[] = "s.class_name = %s";
    $params[] = $selected_class;
}

if ( ! empty( $selected_fee_type ) ) {
    $where[] = "f.fee_type = %s";
    $params[] = $selected_fee_type;
}

if ( ! empty( $selected_method ) ) {
    $where[] = "f.payment_method = %s";
    $params[] = $selected_method;
}

if ( ! empty( $from_date ) ) {
    $where[] = "f.paid_date >= %s";
    $params[] = $from_date;
}

if ( ! empty( $to_date ) ) {
    $where[] = "f.paid_date <= %s";
    $params[] = $to_date;
}

if ( ! empty( $search_query ) ) {
    $where[] = "(s.name_bn LIKE %s OR s.roll_no LIKE %s OR s.student_id LIKE %s OR f.voucher_no LIKE %s)";
    $like = '%' . $wpdb->esc_like( $search_query ) . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = implode( " AND ", $where );

if ( ! empty( $params ) ) {
    $fees_query = $wpdb->prepare(
        "SELECT f.*, s.name_bn, s.roll_no, s.class_name, s.student_id as student_code, s.phone 
         FROM $table_fees f 
         JOIN $table_students s ON f.student_id = s.id 
         WHERE $where_sql 
         ORDER BY f.id DESC",
        $params
    );
    $filtered_sum = $wpdb->get_var( $wpdb->prepare(
        "SELECT COALESCE(SUM(f.amount), 0) FROM $table_fees f JOIN $table_students s ON f.student_id = s.id WHERE $where_sql",
        $params
    ) );
} else {
    $fees_query = "SELECT f.*, s.name_bn, s.roll_no, s.class_name, s.student_id as student_code, s.phone 
                   FROM $table_fees f 
                   JOIN $table_students s ON f.student_id = s.id 
                   WHERE $where_sql 
                   ORDER BY f.id DESC";
    $filtered_sum = $wpdb->get_var( "SELECT COALESCE(SUM(amount), 0) FROM $table_fees" );
}

$fee_records = $wpdb->get_results( $fees_query );

// Dues Calculation for Current Month
$current_month_key = date( 'Y-m' );
$paid_student_ids = $wpdb->get_col( $wpdb->prepare(
    "SELECT DISTINCT student_id FROM $table_fees WHERE fee_type = 'মাসিক বেতন' AND (month_year LIKE %s OR paid_date LIKE %s)",
    '%' . date( 'm/Y' ) . '%',
    $current_month_key . '%'
) );

if ( empty( $paid_student_ids ) ) {
    $paid_student_ids = array( 0 );
}

if ( ! $is_main_admin && $assigned_class !== 'all' ) {
    $due_students = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table_students 
         WHERE status = 'active' AND class_name = %s AND id NOT IN (" . implode( ',', array_map( 'intval', $paid_student_ids ) ) . ") 
         ORDER BY roll_no ASC",
        $assigned_class
    ) );
} else {
    $due_students = $wpdb->get_results(
        "SELECT * FROM $table_students WHERE status = 'active' AND id NOT IN (" . implode( ',', array_map( 'intval', $paid_student_ids ) ) . ") ORDER BY class_name ASC, roll_no ASC"
    );
}
?>

<div class="mms-wrap">
    <div class="mms-header">
        <div class="mms-header-title">
            <h1>শিক্ষার্থীদের বেতন ও পরীক্ষার ফি আদায়</h1>
            <p>
                মাসিক বেতন, পরীক্ষার ফি গ্রহণ, মানি রিসিট প্রিন্ট এবং বকেয়া হিসাব
                <?php if ( ! $is_main_admin && $assigned_class !== 'all' ) : ?>
                    <span class="badge badge-info" style="margin-left: 6px;">আপনার দায়িত্বপ্রাপ্ত শ্রেণি: <?php echo esc_html( $assigned_class ); ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="mms-header-actions">
            <button class="mms-btn mms-btn-success" id="mms-btn-collect-fee">৳ নতুন ফি আদায় ও রিসিট</button>
        </div>
    </div>

    <!-- Tab navigation -->
    <div class="mms-tabs">
        <a href="<?php echo admin_url( 'admin.php?page=mms-student-fees&tab=history' ); ?>" class="mms-tab-link <?php echo $active_tab !== 'dues' ? 'active' : ''; ?>">
            আদায়ের ইতিহাস ও ফিল্টারিং
        </a>
        <a href="<?php echo admin_url( 'admin.php?page=mms-student-fees&tab=dues' ); ?>" class="mms-tab-link <?php echo $active_tab === 'dues' ? 'active' : ''; ?>">
            চলতি মাসের বকেয়া তালিকা (<?php echo count( $due_students ); ?> জন)
        </a>
    </div>

    <?php if ( $active_tab !== 'dues' ) : ?>
        <!-- Advanced Multi-Filtering Card -->
        <div class="mms-card mms-filter-card">
            <form method="GET" action="" class="mms-filter-form" style="gap: 12px; align-items: flex-end;">
                <input type="hidden" name="page" value="mms-student-fees">
                <input type="hidden" name="tab" value="history">

                <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                    <label>শ্রেণি:</label>
                    <select name="class" onchange="this.form.submit()" <?php echo ( ! $is_main_admin && $assigned_class !== 'all' ) ? 'disabled' : ''; ?>>
                        <?php if ( $is_main_admin || $assigned_class === 'all' ) : ?>
                            <option value="">সকল শ্রেণি</option>
                        <?php endif; ?>
                        <?php foreach ( $classes as $c ) : ?>
                            <?php if ( $is_main_admin || $assigned_class === 'all' || $assigned_class === $c ) : ?>
                                <option value="<?php echo esc_attr( $c ); ?>" <?php selected( $selected_class, $c ); ?>><?php echo esc_html( $c ); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                    <label>ফি ধরণ:</label>
                    <select name="fee_type" onchange="this.form.submit()">
                        <option value="">সকল ফি ধরণ</option>
                        <?php foreach ( $fee_types as $ft ) : ?>
                            <option value="<?php echo esc_attr( $ft ); ?>" <?php selected( $selected_fee_type, $ft ); ?>><?php echo esc_html( $ft ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                    <label>পেমেন্ট মাধ্যম:</label>
                    <select name="method" onchange="this.form.submit()">
                        <option value="">সকল মাধ্যম</option>
                        <option value="Cash" <?php selected( $selected_method, 'Cash' ); ?>>নগদ (Cash)</option>
                        <option value="bKash" <?php selected( $selected_method, 'bKash' ); ?>>বিকাশ (bKash)</option>
                        <option value="Nagad" <?php selected( $selected_method, 'Nagad' ); ?>>নগদ (Nagad)</option>
                        <option value="Bank" <?php selected( $selected_method, 'Bank' ); ?>>ব্যাংক (Bank)</option>
                    </select>
                </div>

                <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                    <label>তারিখ হতে (From):</label>
                    <input type="date" name="from_date" value="<?php echo esc_attr( $from_date ); ?>">
                </div>

                <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                    <label>তারিখ পর্যন্ত (To):</label>
                    <input type="date" name="to_date" value="<?php echo esc_attr( $to_date ); ?>">
                </div>

                <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                    <label>সার্চ:</label>
                    <div style="display:flex; gap: 4px;">
                        <input type="text" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="নাম, রোল বা রশিদ নং...">
                        <button type="submit" class="mms-btn mms-btn-secondary">ফিল্টার</button>
                    </div>
                </div>

                <?php if ( ( $is_main_admin && $selected_class ) || $selected_fee_type || $selected_method || $from_date || $to_date || $search_query ) : ?>
                    <div>
                        <a href="<?php echo admin_url( 'admin.php?page=mms-student-fees&tab=history' ); ?>" class="mms-btn mms-btn-outline">রিসেট ✕</a>
                    </div>
                <?php endif; ?>

                <div style="margin-left: auto; font-size: 14px;">
                    ফিল্টারের মোট আদায়: <strong class="text-success" style="font-size: 16px;"><?php echo $currency . ' ' . number_format( $filtered_sum, 2 ); ?></strong>
                </div>
            </form>
        </div>

        <div class="mms-card">
            <div class="table-responsive">
                <table class="mms-table">
                    <thead>
                        <tr>
                            <th>রশিদ নং</th>
                            <th>শিক্ষার্থীর নাম</th>
                            <th>শ্রেণি</th>
                            <th>রোল</th>
                            <th>ফি ধরণ</th>
                            <th>মাস/বিবরণ</th>
                            <th>পরিমাণ</th>
                            <th>পেমেন্ট মাধ্যম</th>
                            <th>জমার তারিখ</th>
                            <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $fee_records ) ) : ?>
                            <?php foreach ( $fee_records as $fee ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $fee->voucher_no ); ?></strong></td>
                                    <td>
                                        <strong><?php echo esc_html( $fee->name_bn ); ?></strong>
                                        <br><small class="text-muted"><?php echo esc_html( $fee->student_code ); ?></small>
                                    </td>
                                    <td><span class="badge badge-class"><?php echo esc_html( $fee->class_name ); ?></span></td>
                                    <td><strong><?php echo esc_html( $fee->roll_no ); ?></strong></td>
                                    <td><span class="badge badge-info"><?php echo esc_html( $fee->fee_type ); ?></span></td>
                                    <td><?php echo esc_html( $fee->month_year ? $fee->month_year : '-' ); ?></td>
                                    <td><strong><?php echo $currency . ' ' . number_format( $fee->amount, 2 ); ?></strong></td>
                                    <td><span class="badge badge-success"><?php echo esc_html( $fee->payment_method ); ?></span></td>
                                    <td><?php echo date( 'd-m-Y', strtotime( $fee->paid_date ) ); ?></td>
                                    <td>
                                        <button class="mms-btn-sm mms-btn-primary mms-view-receipt" data-id="<?php echo esc_attr( $fee->id ); ?>">🖨️ রশিদ</button>
                                        <?php if ( $is_main_admin ) : ?>
                                            <button class="mms-btn-sm mms-btn-danger mms-delete-fee" data-id="<?php echo esc_attr( $fee->id ); ?>">মুছুন</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="10" class="text-center text-muted" style="padding: 30px;">নির্বাচিত ফিল্টারে কোনো আদায়ের রেকর্ড পাওয়া যায়নি।</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else : ?>
        <!-- Dues Tab -->
        <div class="mms-card">
            <div class="card-header flex-between">
                <h2>চলতি মাসের বকেয়া শিক্ষার্থীদের তালিকা (মাসিক বেতন)</h2>
                <span class="badge badge-warning">মোট বকেয়া শিক্ষার্থী: <?php echo count( $due_students ); ?> জন</span>
            </div>
            <div class="table-responsive">
                <table class="mms-table">
                    <thead>
                        <tr>
                            <th>আইডি</th>
                            <th>শিক্ষার্থীর নাম</th>
                            <th>শ্রেণি</th>
                            <th>রোল</th>
                            <th>অভিভাবকের নাম</th>
                            <th>অভিভাবকের মোবাইল</th>
                            <th>মাসিক বেতন</th>
                            <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $due_students ) ) : ?>
                            <?php foreach ( $due_students as $ds ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $ds->student_id ); ?></strong></td>
                                    <td><strong><?php echo esc_html( $ds->name_bn ); ?></strong></td>
                                    <td><span class="badge badge-class"><?php echo esc_html( $ds->class_name ); ?></span></td>
                                    <td><strong><?php echo esc_html( $ds->roll_no ); ?></strong></td>
                                    <td><?php echo esc_html( $ds->guardian_name ? $ds->guardian_name : '-' ); ?></td>
                                    <td><?php echo esc_html( $ds->phone ? $ds->phone : '-' ); ?></td>
                                    <td><strong class="text-danger"><?php echo $currency . ' ' . number_format( $ds->monthly_fee, 2 ); ?></strong></td>
                                    <td>
                                        <button class="mms-btn-sm mms-btn-success mms-quick-collect" 
                                                data-student-id="<?php echo esc_attr( $ds->id ); ?>"
                                                data-fee="<?php echo esc_attr( $ds->monthly_fee ); ?>">
                                            ৳ এখনই বেতন জমা নিন
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="8" class="text-center text-success" style="padding: 30px;">আলহামদুলিল্লাহ! সকল শিক্ষার্থীর বেতন পরিশোধিত।</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Collect Fee Modal -->
<div id="mms-fee-modal" class="mms-modal">
    <div class="mms-modal-content">
        <div class="mms-modal-header">
            <h3>টাকা জমা ও মানি রিসিট প্রস্তুতকরণ</h3>
            <span class="mms-modal-close">&times;</span>
        </div>
        <form id="mms-fee-form">
            <div class="mms-modal-body">
                <div class="mms-form-grid">
                    <div class="form-group full-width">
                        <label>শিক্ষার্থী নির্বাচন করুন <span class="required">*</span></label>
                        <select name="student_id" id="fee_student_id" required>
                            <option value="">-- শিক্ষার্থী নির্বাচন করুন --</option>
                            <?php foreach ( $all_students as $st ) : ?>
                                <option value="<?php echo esc_attr( $st->id ); ?>" data-fee="<?php echo esc_attr( $st->monthly_fee ); ?>">
                                    <?php echo esc_html( $st->name_bn ); ?> [<?php echo esc_html( $st->class_name ); ?> - রোল: <?php echo esc_html( $st->roll_no ); ?>] (বেতন: <?php echo $currency . $st->monthly_fee; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>ফি-এর ধরণ <span class="required">*</span></label>
                        <select name="fee_type" id="fee_type_select" required>
                            <?php foreach ( $fee_types as $ft ) : ?>
                                <option value="<?php echo esc_attr( $ft ); ?>"><?php echo esc_html( $ft ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="month_year_group">
                        <label>বেতনের মাস/বিবরণ</label>
                        <input type="text" name="month_year" id="fee_month_year" value="<?php echo date( 'F Y' ); ?>" placeholder="উদা: জানুয়ারি ২০২৬ / ১ম সাময়িক">
                    </div>

                    <div class="form-group">
                        <label>টাকার পরিমাণ (<?php echo $currency; ?>) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="amount" id="fee_amount" required placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label>জমার তারিখ <span class="required">*</span></label>
                        <input type="date" name="paid_date" id="fee_paid_date" value="<?php echo current_time( 'Y-m-d' ); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>পেমেন্ট মাধ্যম</label>
                        <select name="payment_method" id="fee_payment_method">
                            <option value="Cash">নগদ (Cash)</option>
                            <option value="bKash">বিকাশ (bKash)</option>
                            <option value="Nagad">নগদ (Nagad)</option>
                            <option value="Rocket">রকেট (Rocket)</option>
                            <option value="Bank">ব্যাংক (Bank)</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label>মন্তব্য / নোট (ঐচ্ছিক)</label>
                        <textarea name="remarks" id="fee_remarks" rows="2" placeholder="প্রয়োজনীয় কোনো নোট থাকলে লিখুন..."></textarea>
                    </div>
                </div>
            </div>
            <div class="mms-modal-footer">
                <button type="submit" class="mms-btn mms-btn-success" id="mms-save-fee-btn">💾 ফি জমা করুন ও রশিদ দেখুন</button>
                <button type="button" class="mms-btn mms-btn-outline mms-modal-close-btn">বাতিল</button>
            </div>
        </form>
    </div>
</div>

<!-- Receipt Modal -->
<div id="mms-receipt-modal" class="mms-modal">
    <div class="mms-modal-content">
        <div class="mms-modal-header">
            <h3>টাকা জমার মানি রিসিট</h3>
            <span class="mms-modal-close">&times;</span>
        </div>
        <div class="mms-modal-body" id="mms-receipt-content">
            <!-- Dynamic Receipt HTML -->
        </div>
        <div class="mms-modal-footer">
            <button class="mms-btn mms-btn-primary" onclick="window.print();">🖨️ প্রিন্ট করুন</button>
            <button class="mms-btn mms-btn-outline mms-modal-close-btn">বন্ধ করুন</button>
        </div>
    </div>
</div>
