<?php
defined( 'ABSPATH' ) || exit;

global $wpdb;

$categories = get_option( 'mms_expense_categories', array(
    'বিদ্যুৎ ও গ্যাস বিল',
    'খাবার/বোর্ডিং খরচ',
    'শিক্ষক ও স্টাফ নাস্তা/আপ্যায়ন',
    'বই ও স্টেশনারি',
    'মেরামত ও সংস্কার',
    'মাদ্রাসার ভাড়া',
    'অন্যান্য খরচ'
) );

$settings = get_option( 'mms_settings', array() );
$currency = isset( $settings['currency'] ) ? $settings['currency'] : '৳';

// Filters
$selected_category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
$selected_month    = isset( $_GET['month'] ) ? sanitize_text_field( $_GET['month'] ) : '';
$from_date         = isset( $_GET['from_date'] ) ? sanitize_text_field( $_GET['from_date'] ) : '';
$to_date           = isset( $_GET['to_date'] ) ? sanitize_text_field( $_GET['to_date'] ) : '';
$search_query      = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

$table_expenses = $wpdb->prefix . 'mms_expenses';
$where = array( "1=1" );
$params = array();

if ( ! empty( $selected_category ) ) {
    $where[] = "category = %s";
    $params[] = $selected_category;
}

if ( ! empty( $from_date ) && ! empty( $to_date ) ) {
    $where[] = "expense_date BETWEEN %s AND %s";
    $params[] = $from_date;
    $params[] = $to_date;
} elseif ( ! empty( $selected_month ) ) {
    $where[] = "expense_date LIKE %s";
    $params[] = $selected_month . '%';
}

if ( ! empty( $search_query ) ) {
    $where[] = "(title LIKE %s OR paid_to LIKE %s OR voucher_no LIKE %s)";
    $like = '%' . $wpdb->esc_like( $search_query ) . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = implode( " AND ", $where );

if ( ! empty( $params ) ) {
    $expenses = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_expenses WHERE $where_sql ORDER BY expense_date DESC, id DESC", $params ) );
    $total_expense = $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount), 0) FROM $table_expenses WHERE $where_sql", $params ) );
} else {
    $expenses = $wpdb->get_results( "SELECT * FROM $table_expenses WHERE $where_sql ORDER BY expense_date DESC, id DESC" );
    $total_expense = $wpdb->get_var( "SELECT COALESCE(SUM(amount), 0) FROM $table_expenses WHERE $where_sql" );
}
?>

<div class="mms-wrap">
    <div class="mms-header">
        <div class="mms-header-title">
            <h1>মাদ্রাসার দৈনন্দিন খরচ ও ব্যয় ব্যবস্থাপনা</h1>
            <p>বিদ্যুৎ বিল, বোর্ডিং খরচ, মেরামত ও খরচের ক্যাটাগরি এবং তারিখভিত্তিক ফিল্টারিং</p>
        </div>
        <div class="mms-header-actions">
            <button class="mms-btn mms-btn-danger" id="mms-btn-add-expense">+ নতুন খরচ এন্ট্রি করুন</button>
        </div>
    </div>

    <!-- Filter & Summary Bar -->
    <div class="mms-card mms-filter-card">
        <form method="GET" action="" class="mms-filter-form" style="gap: 12px; align-items: flex-end;">
            <input type="hidden" name="page" value="mms-expenses">

            <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                <label>ক্যাটাগরি:</label>
                <select name="category" onchange="this.form.submit()">
                    <option value="">সকল ক্যাটাগরি</option>
                    <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $selected_category, $cat ); ?>><?php echo esc_html( $cat ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                <label>হতে (From):</label>
                <input type="date" name="from_date" value="<?php echo esc_attr( $from_date ); ?>">
            </div>

            <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                <label>পর্যন্ত (To):</label>
                <input type="date" name="to_date" value="<?php echo esc_attr( $to_date ); ?>">
            </div>

            <div class="form-group-inline" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                <label>সার্চ:</label>
                <div style="display:flex; gap: 4px;">
                    <input type="text" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="বিবরণ বা প্রাপকের নাম...">
                    <button type="submit" class="mms-btn mms-btn-secondary">ফিল্টার</button>
                </div>
            </div>

            <?php if ( $selected_category || $from_date || $to_date || $search_query ) : ?>
                <div>
                    <a href="<?php echo admin_url( 'admin.php?page=mms-expenses' ); ?>" class="mms-btn mms-btn-outline">রিসেট ✕</a>
                </div>
            <?php endif; ?>

            <div style="margin-left: auto; font-size: 14px;">
                ফিল্টারের মোট ব্যয়: <strong class="text-danger" style="font-size: 16px;"><?php echo $currency . ' ' . number_format( $total_expense, 2 ); ?></strong>
            </div>
        </form>
    </div>

    <!-- Expense List Table -->
    <div class="mms-card">
        <div class="table-responsive">
            <table class="mms-table">
                <thead>
                    <tr>
                        <th>ভাউচার নং</th>
                        <th>খরচের বিবরণ / খাত</th>
                        <th>ক্যাটাগরি</th>
                        <th>পরিমাণ</th>
                        <th>তারিখ</th>
                        <th>প্রাপক / মাধ্যম</th>
                        <th>মন্তব্য</th>
                        <th>অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $expenses ) ) : ?>
                        <?php foreach ( $expenses as $exp ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $exp->voucher_no ); ?></strong></td>
                                <td><strong><?php echo esc_html( $exp->title ); ?></strong></td>
                                <td><span class="badge badge-warning"><?php echo esc_html( $exp->category ); ?></span></td>
                                <td><strong class="text-danger"><?php echo $currency . ' ' . number_format( $exp->amount, 2 ); ?></strong></td>
                                <td><?php echo date( 'd-m-Y', strtotime( $exp->expense_date ) ); ?></td>
                                <td><?php echo esc_html( $exp->paid_to ? $exp->paid_to : '-' ); ?></td>
                                <td><?php echo esc_html( $exp->remarks ? $exp->remarks : '-' ); ?></td>
                                <td>
                                    <button class="mms-btn-sm mms-btn-danger mms-delete-expense" data-id="<?php echo esc_attr( $exp->id ); ?>">মুছুন</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 30px;">নির্বাচিত ফিল্টারে কোনো খরচের রেকর্ড পাওয়া যায়নি।</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div id="mms-expense-modal" class="mms-modal">
    <div class="mms-modal-content">
        <div class="mms-modal-header">
            <h3>নতুন খরচ এন্ট্রি ফরম</h3>
            <span class="mms-modal-close">&times;</span>
        </div>
        <form id="mms-expense-form">
            <div class="mms-modal-body">
                <div class="mms-form-grid">
                    <div class="form-group full-width">
                        <label>খরচের বিবরণ (খাত) <span class="required">*</span></label>
                        <input type="text" name="title" id="expense_title" required placeholder="উদা: নভেম্বর মাসের বিদ্যুৎ বিল পরিশোধ">
                    </div>

                    <div class="form-group">
                        <label>ক্যাটাগরি <span class="required">*</span></label>
                        <select name="category" id="expense_category" required>
                            <?php foreach ( $categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( $cat ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>টাকার পরিমাণ (<?php echo $currency; ?>) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="amount" id="expense_amount" required placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label>খরচের তারিখ <span class="required">*</span></label>
                        <input type="date" name="expense_date" id="expense_date" value="<?php echo current_time( 'Y-m-d' ); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>টাকা গ্রহণকারী (প্রাপক)</label>
                        <input type="text" name="paid_to" id="expense_paid_to" placeholder="যার কাছে টাকা দেওয়া হয়েছে">
                    </div>

                    <div class="form-group full-width">
                        <label>অতিরিক্ত নোট / বিবরণ</label>
                        <textarea name="remarks" id="expense_remarks" rows="2" placeholder="প্রয়োজনীয় কোনো তথ্য থাকলে লিখুন..."></textarea>
                    </div>
                </div>
            </div>
            <div class="mms-modal-footer">
                <button type="submit" class="mms-btn mms-btn-danger" id="mms-save-expense-btn">💾 খরচ সংরক্ষণ করুন</button>
                <button type="button" class="mms-btn mms-btn-outline mms-modal-close-btn">বাতিল</button>
            </div>
        </form>
    </div>
</div>
