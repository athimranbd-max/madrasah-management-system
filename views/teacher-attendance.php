<?php
defined( 'ABSPATH' ) || exit;

// Strictly Main Admin Only
if ( ! MMS_Admin::is_main_admin() ) {
    wp_die( 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন শিক্ষকদের হাজিরা নিতে পারেন।' );
}

global $wpdb;

$today = current_time( 'Y-m-d' );
$table_teachers = $wpdb->prefix . 'mms_teachers';
$table_attendance = $wpdb->prefix . 'mms_teacher_attendance';

$teachers = $wpdb->get_results( $wpdb->prepare(
    "SELECT t.id, t.teacher_id, t.name, t.designation,
            COALESCE(a.status, 'present') as att_status,
            COALESCE(a.remarks, '') as att_remarks
     FROM $table_teachers t
     LEFT JOIN $table_attendance a ON t.id = a.teacher_id AND a.attendance_date = %s
     WHERE t.status = 'active'
     ORDER BY t.id ASC",
    $today
) );
?>

<div class="mms-wrap">
    <div class="mms-header">
        <div class="mms-header-title">
            <h1>শিক্ষক ও স্টাফ দৈনিক হাজিরা</h1>
            <p>শিক্ষকদের উপস্থিতি, ছুটি ও বিলম্বের সঠিক হিসাব রাখা</p>
        </div>
    </div>

    <div class="mms-card">
        <div class="card-header flex-between">
            <div class="form-group-inline">
                <label>হাজিরার তারিখ:</label>
                <input type="date" id="mms-teacher-att-date" value="<?php echo esc_attr( $today ); ?>">
            </div>
            <div class="mms-btn-group">
                <button type="button" class="mms-btn mms-btn-sm mms-btn-success" id="mms-teacher-mark-all-present">✓ সবাই উপস্থিত</button>
            </div>
        </div>

        <form id="mms-teacher-attendance-form">
            <div class="table-responsive">
                <table class="mms-table">
                    <thead>
                        <tr>
                            <th width="15%">আইডি নং</th>
                            <th width="25%">শিক্ষকের নাম ও পদবি</th>
                            <th width="35%">হাজিরা স্ট্যাটাস</th>
                            <th width="25%">মন্তব্য (যদি থাকে)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $teachers ) ) : ?>
                            <?php foreach ( $teachers as $t ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $t->teacher_id ); ?></strong></td>
                                    <td>
                                        <strong><?php echo esc_html( $t->name ); ?></strong>
                                        <br><small class="text-muted"><?php echo esc_html( $t->designation ); ?></small>
                                    </td>
                                    <td>
                                        <div class="mms-radio-group">
                                            <label class="radio-label status-present">
                                                <input type="radio" name="attendance[<?php echo esc_attr( $t->id ); ?>][status]" value="present" <?php checked( $t->att_status, 'present' ); ?>>
                                                <span>উপস্থিত</span>
                                            </label>
                                            <label class="radio-label status-absent">
                                                <input type="radio" name="attendance[<?php echo esc_attr( $t->id ); ?>][status]" value="absent" <?php checked( $t->att_status, 'absent' ); ?>>
                                                <span>অনুপস্থিত</span>
                                            </label>
                                            <label class="radio-label status-leave">
                                                <input type="radio" name="attendance[<?php echo esc_attr( $t->id ); ?>][status]" value="leave" <?php checked( $t->att_status, 'leave' ); ?>>
                                                <span>ছুটি</span>
                                            </label>
                                            <label class="radio-label status-late">
                                                <input type="radio" name="attendance[<?php echo esc_attr( $t->id ); ?>][status]" value="late" <?php checked( $t->att_status, 'late' ); ?>>
                                                <span>বিলম্ব</span>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="attendance[<?php echo esc_attr( $t->id ); ?>][remarks]" value="<?php echo esc_attr( $t->att_remarks ); ?>" placeholder="মন্তব্য..." class="form-control-sm">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="4" class="text-center text-muted" style="padding: 30px;">কোনো শিক্ষক সক্রিয় পাওয়া যায়নি।</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ( ! empty( $teachers ) ) : ?>
                <div class="mms-form-actions" style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="mms-btn mms-btn-primary mms-btn-lg" id="mms-save-teacher-att-btn">💾 শিক্ষকদের হাজিরা সংরক্ষণ করুন</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>
