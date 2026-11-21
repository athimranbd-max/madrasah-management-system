<?php
defined( 'ABSPATH' ) || exit;

global $wpdb;

$classes = get_option( 'mms_classes', array( 'শিশু ১', 'শিশু ২', '১ম শ্রেণি', '২য় শ্রেণি', '৩য় শ্রেণি' ) );
$settings = get_option( 'mms_settings', array() );
$currency = isset( $settings['currency'] ) ? $settings['currency'] : '৳';

$is_main_admin = MMS_Admin::is_main_admin();
$assigned_class = MMS_Admin::get_assigned_class();

// If sub-admin, enforce their assigned class if specific
if ( ! $is_main_admin && $assigned_class !== 'all' ) {
    $selected_class = $assigned_class;
} else {
    $selected_class = isset( $_GET['class'] ) ? sanitize_text_field( $_GET['class'] ) : '';
}

$selected_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$search_query    = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

$table_students = $wpdb->prefix . 'mms_students';
$where_clauses  = array( "1=1" );
$params         = array();

if ( ! empty( $selected_class ) ) {
    $where_clauses[] = "class_name = %s";
    $params[] = $selected_class;
}

if ( ! empty( $selected_status ) ) {
    $where_clauses[] = "status = %s";
    $params[] = $selected_status;
}

if ( ! empty( $search_query ) ) {
    $where_clauses[] = "(name_bn LIKE %s OR name_en LIKE %s OR roll_no LIKE %s OR student_id LIKE %s OR phone LIKE %s OR guardian_name LIKE %s)";
    $like = '%' . $wpdb->esc_like( $search_query ) . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = implode( " AND ", $where_clauses );

if ( ! empty( $params ) ) {
    $query = $wpdb->prepare( "SELECT * FROM $table_students WHERE $where_sql ORDER BY class_name ASC, roll_no ASC", $params );
} else {
    $query = "SELECT * FROM $table_students WHERE $where_sql ORDER BY class_name ASC, roll_no ASC";
}

$students = $wpdb->get_results( $query );
$total_count = count( $students );
?>

<div class="mms-wrap">
    <div class="mms-header">
        <div class="mms-header-title">
            <h1>শিক্ষার্থী ব্যবস্থাপনা ও ভর্তি</h1>
            <p>
                শিক্ষার্থীদের প্রোফাইল, মাসিক বেতন ও বকেয়া লেজার বিবরণী
                <?php if ( ! $is_main_admin && $assigned_class !== 'all' ) : ?>
                    <span class="badge badge-info" style="margin-left: 6px;">আপনার দায়িত্বপ্রাপ্ত শ্রেণি: <?php echo esc_html( $assigned_class ); ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="mms-header-actions">
            <?php if ( $is_main_admin ) : ?>
                <button class="mms-btn mms-btn-primary" id="mms-btn-add-student">+ নতুন শিক্ষার্থী ভর্তি</button>
            <?php else : ?>
                <span class="badge badge-warning" style="font-size: 12px; padding: 6px 12px;">সাব-অ্যাডমিন মোড (নতুন ভর্তি ও ডিলিট নিষ্ক্রিয়)</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Advanced Filters & Search Card -->
    <div class="mms-card mms-filter-card">
        <form method="GET" action="" class="mms-filter-form" style="gap: 12px; align-items: flex-end;">
            <input type="hidden" name="page" value="mms-students">
            
            <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                <label>শ্রেণি নির্বাচন:</label>
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
                <label>স্ট্যাটাস:</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="">সকল স্ট্যাটাস</option>
                    <option value="active" <?php selected( $selected_status, 'active' ); ?>>শুধু সক্রিয় (Active)</option>
                    <option value="inactive" <?php selected( $selected_status, 'inactive' ); ?>>নিষ্ক্রিয় (Inactive)</option>
                </select>
            </div>

            <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                <label>অনুসন্ধান (Search):</label>
                <div style="display:flex; gap: 6px;">
                    <input type="text" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="নাম, রোল, অভিভাবক বা মোবাইল...">
                    <button type="submit" class="mms-btn mms-btn-secondary">খুঁজুন</button>
                </div>
            </div>

            <?php if ( ( $is_main_admin && $selected_class ) || $selected_status || $search_query ) : ?>
                <div>
                    <a href="<?php echo admin_url( 'admin.php?page=mms-students' ); ?>" class="mms-btn mms-btn-outline">ফিল্টার রিসেট ✕</a>
                </div>
            <?php endif; ?>

            <div style="margin-left: auto; font-size: 14px; color: var(--mms-muted);">
                ফলাফল: <strong><?php echo $total_count; ?></strong> জন শিক্ষার্থী
            </div>
        </form>
    </div>

    <!-- Students Table -->
    <div class="mms-card">
        <div class="table-responsive">
            <table class="mms-table">
                <thead>
                    <tr>
                        <th>আইডি নং</th>
                        <th>শিক্ষার্থীর নাম (ক্লিক করুন)</th>
                        <th>শ্রেণি</th>
                        <th>রোল</th>
                        <th>অভিভাবকের নাম</th>
                        <th>অভিভাবকের মোবাইল</th>
                        <th>মাসিক বেতন</th>
                        <th>ভর্তির তারিখ</th>
                        <th>স্ট্যাটাস</th>
                        <th>অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $students ) ) : ?>
                        <?php foreach ( $students as $s ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $s->student_id ); ?></strong></td>
                                <td>
                                    <a href="javascript:void(0);" class="mms-view-student-ledger" data-id="<?php echo esc_attr( $s->id ); ?>" style="text-decoration:none; color:#1e3a8a; font-weight:700;">
                                        <?php echo esc_html( $s->name_bn ); ?> 👁️
                                    </a>
                                    <?php if ( ! empty( $s->name_en ) ) : ?>
                                        <br><small class="text-muted"><?php echo esc_html( $s->name_en ); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-class"><?php echo esc_html( $s->class_name ); ?></span></td>
                                <td><strong><?php echo esc_html( $s->roll_no ); ?></strong></td>
                                <td><?php echo esc_html( $s->guardian_name ? $s->guardian_name : '-' ); ?></td>
                                <td>
                                    <span style="font-family:monospace; color:#0284c7; font-weight:600;">
                                        <?php echo esc_html( $s->phone ? $s->phone : '-' ); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo $currency . ' ' . number_format( $s->monthly_fee, 2 ); ?></strong></td>
                                <td><?php echo $s->admission_date ? date( 'd-m-Y', strtotime( $s->admission_date ) ) : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $s->status === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $s->status === 'active' ? 'সক্রিয়' : 'নিষ্ক্রিয়'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="mms-btn-sm mms-btn-secondary mms-view-student-ledger" data-id="<?php echo esc_attr( $s->id ); ?>">ফি লেজার 📊</button>
                                    <?php if ( $is_main_admin ) : ?>
                                        <button class="mms-btn-sm mms-btn-outline mms-edit-student" data-student='<?php echo esc_attr( json_encode( $s ) ); ?>'>এডিট</button>
                                        <button class="mms-btn-sm mms-btn-danger mms-delete-student" data-id="<?php echo esc_attr( $s->id ); ?>">মুছুন</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted" style="padding: 30px;">
                                নির্বাচিত ফিল্টারে কোনো শিক্ষার্থী পাওয়া যায়নি।
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ( $is_main_admin ) : ?>
<!-- Add/Edit Student Modal (Only Loaded for Main Admin) -->
<div id="mms-student-modal" class="mms-modal">
    <div class="mms-modal-content">
        <div class="mms-modal-header">
            <h3 id="mms-student-modal-title">নতুন শিক্ষার্থী ভর্তি ফরম</h3>
            <span class="mms-modal-close">&times;</span>
        </div>
        <form id="mms-student-form">
            <input type="hidden" name="id" id="student_id_field" value="0">
            <div class="mms-modal-body">
                <div class="mms-form-grid">
                    <div class="form-group">
                        <label>শিক্ষার্থীর নাম (বাংলা) <span class="required">*</span></label>
                        <input type="text" name="name_bn" id="student_name_bn" required placeholder="উদা: আব্দুল্লাহ আল মামুন">
                    </div>
                    <div class="form-group">
                        <label>শিক্ষার্থীর নাম (ইংরেজি)</label>
                        <input type="text" name="name_en" id="student_name_en" placeholder="Ex: Abdullah Al Mamun">
                    </div>
                    <div class="form-group">
                        <label>শ্রেণি <span class="required">*</span></label>
                        <select name="class_name" id="student_class_name" required>
                            <?php foreach ( $classes as $c ) : ?>
                                <option value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>রোল নম্বর <span class="required">*</span></label>
                        <input type="number" name="roll_no" id="student_roll_no" value="1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>অভিভাবকের নাম</label>
                        <input type="text" name="guardian_name" id="student_guardian_name" placeholder="পিতা/মাতার নাম">
                    </div>
                    <div class="form-group">
                        <label>অভিভাবকের মোবাইল নম্বর</label>
                        <input type="text" name="phone" id="student_phone" placeholder="017xxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>মাসিক বেতন (<?php echo $currency; ?>) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="monthly_fee" id="student_monthly_fee" value="500" required>
                    </div>
                    <div class="form-group">
                        <label>ভর্তির তারিখ</label>
                        <input type="date" name="admission_date" id="student_admission_date" value="<?php echo current_time( 'Y-m-d' ); ?>">
                    </div>
                    <div class="form-group full-width">
                        <label>ঠিকানা</label>
                        <textarea name="address" id="student_address" rows="2" placeholder="গ্রাম/বাসা, ডাকঘর, থানা, জেলা"></textarea>
                    </div>
                    <div class="form-group">
                        <label>স্ট্যাটাস</label>
                        <select name="status" id="student_status">
                            <option value="active">সক্রিয় (Active)</option>
                            <option value="inactive">নিষ্ক্রিয় (Inactive)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mms-modal-footer">
                <button type="submit" class="mms-btn mms-btn-primary" id="mms-student-submit-btn">সংরক্ষণ করুন</button>
                <button type="button" class="mms-btn mms-btn-outline mms-modal-close-btn">বাতিল</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Student Fee Ledger Modal -->
<div id="mms-student-ledger-modal" class="mms-modal">
    <div class="mms-modal-content" style="max-width: 850px;">
        <div class="mms-modal-header">
            <h3>শিক্ষার্থীর মাসিক বেতন ও বকেয়া লেজার বিবরণী</h3>
            <span class="mms-modal-close">&times;</span>
        </div>
        <div class="mms-modal-body" id="mms-student-ledger-content">
            <p style="text-align:center; padding:30px;">লেজার লোড হচ্ছে...</p>
        </div>
        <div class="mms-modal-footer">
            <button type="button" class="mms-btn mms-btn-outline mms-modal-close-btn">বন্ধ করুন</button>
        </div>
    </div>
</div>
