<?php
defined( 'ABSPATH' ) || exit;

// Strictly Main Admin Only
if ( ! MMS_Admin::is_main_admin() ) {
    wp_die( 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন শিক্ষকদের বেতন পরিচালনা করতে পারেন।' );
}

global $wpdb;

$settings = get_option( 'mms_settings', array() );
$currency = isset( $settings['currency'] ) ? $settings['currency'] : '৳';

$table_salaries = $wpdb->prefix . 'mms_teacher_salaries';
$table_teachers = $wpdb->prefix . 'mms_teachers';

$active_teachers = $wpdb->get_results( "SELECT id, teacher_id, name, designation, monthly_salary FROM $table_teachers WHERE status = 'active' ORDER BY id ASC" );

$salaries = $wpdb->get_results(
    "SELECT s.*, t.name, t.designation, t.teacher_id as teacher_code 
     FROM $table_salaries s 
     JOIN $table_teachers t ON s.teacher_id = t.id 
     ORDER BY s.id DESC"
);
?>

<div class="mms-wrap">
    <div class="mms-header">
        <div class="mms-header-title">
            <h1>শিক্ষকদের বেতন ও পে-রোল</h1>
            <p>মাসিক বেতন পরিশোধ, বোনাস, কর্তন হিসাব এবং প্রদেয় বেতন স্লিপ প্রস্তুতকরণ</p>
        </div>
        <div class="mms-header-actions">
            <button class="mms-btn mms-btn-primary" id="mms-btn-pay-salary">+ শিক্ষক বেতন পরিশোধ করুন</button>
        </div>
    </div>

    <!-- Salary History Table -->
    <div class="mms-card">
        <div class="table-responsive">
            <table class="mms-table">
                <thead>
                    <tr>
                        <th>ভাউচার নং</th>
                        <th>শিক্ষকের নাম</th>
                        <th>পদবি</th>
                        <th>বেতনের মাস</th>
                        <th>মূল বেতন</th>
                        <th>বোনাস</th>
                        <th>কর্তন</th>
                        <th>প্রদেয় মোট</th>
                        <th>পরিশোধের তারিখ</th>
                        <th>অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $salaries ) ) : ?>
                        <?php foreach ( $salaries as $sal ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $sal->voucher_no ); ?></strong></td>
                                <td>
                                    <strong><?php echo esc_html( $sal->name ); ?></strong>
                                    <br><small class="text-muted"><?php echo esc_html( $sal->teacher_code ); ?></small>
                                </td>
                                <td><?php echo esc_html( $sal->designation ); ?></td>
                                <td><span class="badge badge-info"><?php echo esc_html( $sal->month_year ); ?></span></td>
                                <td><?php echo $currency . ' ' . number_format( $sal->basic_salary, 2 ); ?></td>
                                <td>+<?php echo number_format( $sal->bonus, 2 ); ?></td>
                                <td>-<?php echo number_format( $sal->deduction, 2 ); ?></td>
                                <td><strong class="text-success"><?php echo $currency . ' ' . number_format( $sal->net_paid, 2 ); ?></strong></td>
                                <td><?php echo date( 'd-m-Y', strtotime( $sal->payment_date ) ); ?></td>
                                <td>
                                    <button class="mms-btn-sm mms-btn-primary mms-view-salary-slip" data-id="<?php echo esc_attr( $sal->id ); ?>">🖨️ স্লিপ</button>
                                    <button class="mms-btn-sm mms-btn-danger mms-delete-salary" data-id="<?php echo esc_attr( $sal->id ); ?>">মুছুন</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="10" class="text-center text-muted" style="padding: 30px;">কোনো বেতন পরিশোধের রেকর্ড পাওয়া যায়নি।</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pay Salary Modal -->
<div id="mms-salary-modal" class="mms-modal">
    <div class="mms-modal-content">
        <div class="mms-modal-header">
            <h3>শিক্ষক বেতন পরিশোধ ও স্লিপ</h3>
            <span class="mms-modal-close">&times;</span>
        </div>
        <form id="mms-salary-form">
            <div class="mms-modal-body">
                <div class="mms-form-grid">
                    <div class="form-group full-width">
                        <label>শিক্ষক নির্বাচন করুন <span class="required">*</span></label>
                        <select name="teacher_id" id="salary_teacher_id" required>
                            <option value="">-- শিক্ষক নির্বাচন করুন --</option>
                            <?php foreach ( $active_teachers as $at ) : ?>
                                <option value="<?php echo esc_attr( $at->id ); ?>" data-salary="<?php echo esc_attr( $at->monthly_salary ); ?>">
                                    <?php echo esc_html( $at->name ); ?> (<?php echo esc_html( $at->designation ); ?>) - নির্ধারিত বেতন: <?php echo $currency . $at->monthly_salary; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>বেতনের মাস ও বছর <span class="required">*</span></label>
                        <input type="text" name="month_year" id="salary_month_year" value="<?php echo date( 'F Y' ); ?>" required placeholder="উদা: জানুয়ারি ২০২৬">
                    </div>

                    <div class="form-group">
                        <label>মূল বেতন (<?php echo $currency; ?>) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="basic_salary" id="salary_basic" required placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label>বোনাস / ভাতা (<?php echo $currency; ?>)</label>
                        <input type="number" step="0.01" name="bonus" id="salary_bonus" value="0.00">
                    </div>

                    <div class="form-group">
                        <label>কর্তন / অগ্রিম কর্তন (<?php echo $currency; ?>)</label>
                        <input type="number" step="0.01" name="deduction" id="salary_deduction" value="0.00">
                    </div>

                    <div class="form-group">
                        <label>প্রদেয় মোট নিট বেতন (Net Paid)</label>
                        <input type="number" step="0.01" name="net_paid" id="salary_net_paid" readonly style="font-weight: bold; background: #f8fafc; color: #16a34a;">
                    </div>

                    <div class="form-group">
                        <label>পরিশোধের তারিখ</label>
                        <input type="date" name="payment_date" id="salary_payment_date" value="<?php echo current_time( 'Y-m-d' ); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>পেমেন্ট মাধ্যম</label>
                        <select name="payment_method" id="salary_payment_method">
                            <option value="Cash">নগদ (Cash)</option>
                            <option value="Bank">ব্যাংক অ্যাকাউন্ট (Bank)</option>
                            <option value="bKash">বিকাশ (bKash)</option>
                            <option value="Nagad">নগদ (Nagad)</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label>মন্তব্য / নোট (ঐচ্ছিক)</label>
                        <textarea name="remarks" id="salary_remarks" rows="2" placeholder="প্রয়োজনীয় কোনো নোট থাকলে লিখুন..."></textarea>
                    </div>
                </div>
            </div>
            <div class="mms-modal-footer">
                <button type="submit" class="mms-btn mms-btn-primary" id="mms-save-salary-btn">💾 বেতন পরিশোধ করুন ও স্লিপ দেখুন</button>
                <button type="button" class="mms-btn mms-btn-outline mms-modal-close-btn">বাতিল</button>
            </div>
        </form>
    </div>
</div>

<!-- Salary Slip Modal -->
<div id="mms-slip-modal" class="mms-modal">
    <div class="mms-modal-content">
        <div class="mms-modal-header">
            <h3>শিক্ষক বেতন রশিদ স্লিপ</h3>
            <span class="mms-modal-close">&times;</span>
        </div>
        <div class="mms-modal-body" id="mms-slip-content"></div>
        <div class="mms-modal-footer">
            <button class="mms-btn mms-btn-primary" onclick="window.print();">🖨️ প্রিন্ট করুন</button>
            <button class="mms-btn mms-btn-outline mms-modal-close-btn">বন্ধ করুন</button>
        </div>
    </div>
</div>
