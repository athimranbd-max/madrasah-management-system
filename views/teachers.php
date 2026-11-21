<?php
defined( 'ABSPATH' ) || exit;

// Strictly Main Admin Only
if ( ! MMS_Admin::is_main_admin() ) {
    wp_die( 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন শিক্ষক তালিকা দেখতে পারেন।' );
}

global $wpdb;

$settings = get_option( 'mms_settings', array() );
$currency = isset( $settings['currency'] ) ? $settings['currency'] : '৳';

$table_teachers = $wpdb->prefix . 'mms_teachers';
$teachers = $wpdb->get_results( "SELECT * FROM $table_teachers ORDER BY id ASC" );
?>

<div class="mms-wrap">
    <div class="mms-header">
        <div class="mms-header-title">
            <h1>শিক্ষক ও স্টাফ ব্যবস্থাপনা</h1>
            <p>মাদ্রাসার শিক্ষক ও কর্মচারীদের তালিকা, বেতন স্কেল ও তথ্য সংরক্ষণ</p>
        </div>
        <div class="mms-header-actions">
            <button class="mms-btn mms-btn-primary" id="mms-btn-add-teacher">+ নতুন শিক্ষক / স্টাফ যুক্ত করুন</button>
        </div>
    </div>

    <!-- Teachers Table -->
    <div class="mms-card">
        <div class="table-responsive">
            <table class="mms-table">
                <thead>
                    <tr>
                        <th>শিক্ষক আইডি</th>
                        <th>নাম</th>
                        <th>পদবি</th>
                        <th>মোবাইল</th>
                        <th>মাসিক নির্ধারিত বেতন</th>
                        <th>যোগদানের তারিখ</th>
                        <th>স্ট্যাটাস</th>
                        <th>অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $teachers ) ) : ?>
                        <?php foreach ( $teachers as $t ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $t->teacher_id ); ?></strong></td>
                                <td><strong><?php echo esc_html( $t->name ); ?></strong></td>
                                <td><span class="badge badge-info"><?php echo esc_html( $t->designation ); ?></span></td>
                                <td><?php echo esc_html( $t->phone ? $t->phone : '-' ); ?></td>
                                <td><strong><?php echo $currency . ' ' . number_format( $t->monthly_salary, 2 ); ?></strong></td>
                                <td><?php echo $t->joining_date ? date( 'd-m-Y', strtotime( $t->joining_date ) ) : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $t->status === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $t->status === 'active' ? 'সক্রিয়' : 'নিষ্ক্রিয়'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="mms-btn-sm mms-btn-outline mms-edit-teacher" data-teacher='<?php echo esc_attr( json_encode( $t ) ); ?>'>এডিট</button>
                                    <button class="mms-btn-sm mms-btn-danger mms-delete-teacher" data-id="<?php echo esc_attr( $t->id ); ?>">মুছুন</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 30px;">কোনো শিক্ষক বা স্টাফ পাওয়া যায়নি।</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Teacher Modal -->
<div id="mms-teacher-modal" class="mms-modal">
    <div class="mms-modal-content">
        <div class="mms-modal-header">
            <h3 id="mms-teacher-modal-title">শিক্ষক / স্টাফ তথ্য ফরম</h3>
            <span class="mms-modal-close">&times;</span>
        </div>
        <form id="mms-teacher-form">
            <input type="hidden" name="id" id="teacher_id_field" value="0">
            <div class="mms-modal-body">
                <div class="mms-form-grid">
                    <div class="form-group">
                        <label>শিক্ষকের পুরো নাম <span class="required">*</span></label>
                        <input type="text" name="name" id="teacher_name" required placeholder="উদা: মাওলানা আব্দুর রহমান">
                    </div>
                    <div class="form-group">
                        <label>পদবি <span class="required">*</span></label>
                        <input type="text" name="designation" id="teacher_designation" required placeholder="উদা: প্রধান শিক্ষক / সহকারী শিক্ষক">
                    </div>
                    <div class="form-group">
                        <label>মোবাইল নম্বর</label>
                        <input type="text" name="phone" id="teacher_phone" placeholder="017xxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>মাসিক মূল বেতন (<?php echo $currency; ?>) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="monthly_salary" id="teacher_monthly_salary" value="10000" required>
                    </div>
                    <div class="form-group">
                        <label>যোগদানের তারিখ</label>
                        <input type="date" name="joining_date" id="teacher_joining_date" value="<?php echo current_time( 'Y-m-d' ); ?>">
                    </div>
                    <div class="form-group">
                        <label>স্ট্যাটাস</label>
                        <select name="status" id="teacher_status">
                            <option value="active">সক্রিয় (Active)</option>
                            <option value="inactive">নিষ্ক্রিয় (Inactive)</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>স্থায়ী/বর্তমান ঠিকানা</label>
                        <textarea name="address" id="teacher_address" rows="2" placeholder="গ্রাম, ডাকঘর, থানা, জেলা"></textarea>
                    </div>
                </div>
            </div>
            <div class="mms-modal-footer">
                <button type="submit" class="mms-btn mms-btn-primary" id="mms-teacher-submit-btn">সংরক্ষণ করুন</button>
                <button type="button" class="mms-btn mms-btn-outline mms-modal-close-btn">বাতিল</button>
            </div>
        </form>
    </div>
</div>
