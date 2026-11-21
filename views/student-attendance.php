<?php
defined( 'ABSPATH' ) || exit;

global $wpdb;

$classes = get_option( 'mms_classes', array( 'শিশু ১', 'শিশু ২', '১ম শ্রেণি', '২য় শ্রেণি', '৩য় শ্রেণি' ) );
$today = current_time( 'Y-m-d' );
$is_main_admin = MMS_Admin::is_main_admin();
$assigned_class = MMS_Admin::get_assigned_class();
?>

<div class="mms-wrap">
    <div class="mms-header">
        <div class="mms-header-title">
            <h1>শিক্ষার্থী দৈনিক হাজিরা</h1>
            <p>
                শ্রেণিভিত্তিক সহজ ও দ্রুত হাজিরা গ্রহণ ও রেজিস্টার ব্যবস্থাপনা
                <?php if ( ! $is_main_admin && $assigned_class !== 'all' ) : ?>
                    <span class="badge badge-info" style="margin-left: 6px;">আপনার দায়িত্বপ্রাপ্ত শ্রেণি: <?php echo esc_html( $assigned_class ); ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Attendance Selector Card -->
    <div class="mms-card mms-filter-card">
        <div class="mms-filter-form">
            <div class="form-group-inline">
                <label>তারিখ:</label>
                <input type="date" id="mms-att-date" value="<?php echo esc_attr( $today ); ?>">
            </div>

            <div class="form-group-inline">
                <label>শ্রেণি নির্বাচন:</label>
                <select id="mms-att-class" <?php echo ( ! $is_main_admin && $assigned_class !== 'all' ) ? 'disabled' : ''; ?>>
                    <?php foreach ( $classes as $c ) : ?>
                        <?php if ( $is_main_admin || $assigned_class === 'all' || $assigned_class === $c ) : ?>
                            <option value="<?php echo esc_attr( $c ); ?>" <?php selected( $assigned_class, $c ); ?>><?php echo esc_html( $c ); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-inline">
                <button type="button" class="mms-btn mms-btn-primary" id="mms-btn-load-attendance">হাজিরা শিট আনুন &rarr;</button>
            </div>
        </div>
    </div>

    <!-- Attendance Action Bar (Shown when loaded) -->
    <div class="mms-card" id="mms-attendance-box" style="display:none;">
        <div class="card-header flex-between">
            <div id="mms-att-summary-info">
                <strong>শ্রেণি: <span id="display-class"></span></strong> | 
                <strong>তারিখ: <span id="display-date"></span></strong> | 
                <span>মোট শিক্ষার্থী: <strong id="display-count">0</strong> জন</span>
            </div>
            <div class="mms-btn-group">
                <button type="button" class="mms-btn mms-btn-sm mms-btn-success" id="mms-mark-all-present">✓ সবাই উপস্থিত</button>
                <button type="button" class="mms-btn mms-btn-sm mms-btn-danger" id="mms-mark-all-absent">✗ সবাই অনুপস্থিত</button>
            </div>
        </div>

        <form id="mms-attendance-form">
            <div class="table-responsive">
                <table class="mms-table" id="mms-attendance-table">
                    <thead>
                        <tr>
                            <th width="8%">রোল</th>
                            <th width="15%">আইডি নং</th>
                            <th width="25%">শিক্ষার্থীর নাম</th>
                            <th width="35%">হাজিরা স্ট্যাটাস</th>
                            <th width="17%">মন্তব্য</th>
                        </tr>
                    </thead>
                    <tbody id="mms-attendance-tbody">
                    </tbody>
                </table>
            </div>

            <div class="mms-form-actions" style="margin-top: 20px; text-align: right;">
                <button type="submit" class="mms-btn mms-btn-primary mms-btn-lg" id="mms-save-att-btn">💾 হাজিরা সংরক্ষণ করুন</button>
            </div>
        </form>
    </div>

    <div id="mms-att-empty-notice" class="mms-card text-center" style="padding: 40px; color: #64748b;">
        <p style="font-size: 16px;">অনুগ্রহ করে উপরে তারিখ নির্বাচন করে <strong>"হাজিরা শিট আনুন"</strong> বাটনে ক্লিক করুন।</p>
    </div>
</div>
