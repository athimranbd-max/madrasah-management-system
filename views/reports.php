<?php
defined( 'ABSPATH' ) || exit;

// Strictly Main Admin Only
if ( ! MMS_Admin::is_main_admin() ) {
    wp_die( 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন আর্থিক হিসাব রিপোর্ট দেখতে পারেন।' );
}

global $wpdb;

$settings = get_option( 'mms_settings', array() );
$currency = isset( $settings['currency'] ) ? $settings['currency'] : '৳';
$madrasah_name = ! empty( $settings['madrasah_name'] ) ? $settings['madrasah_name'] : 'ফরমান আলী মণ্ডল রওজাতুল আতফাল মাদরাসা';

$from_date = isset( $_GET['from_date'] ) ? sanitize_text_field( $_GET['from_date'] ) : date( 'Y-m-01' );
$to_date   = isset( $_GET['to_date'] ) ? sanitize_text_field( $_GET['to_date'] ) : date( 'Y-m-t' );

$table_fees     = $wpdb->prefix . 'mms_student_fees';
$table_salaries = $wpdb->prefix . 'mms_teacher_salaries';
$table_expenses = $wpdb->prefix . 'mms_expenses';

// Income breakdown by fee type
$income_by_type = $wpdb->get_results( $wpdb->prepare(
    "SELECT fee_type, COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount 
     FROM $table_fees 
     WHERE paid_date BETWEEN %s AND %s 
     GROUP BY fee_type",
    $from_date,
    $to_date
) );

$total_income = $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM $table_fees WHERE paid_date BETWEEN %s AND %s",
    $from_date,
    $to_date
) );

// Teacher Salary Expense
$total_salary_expense = $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(net_paid), 0) FROM $table_salaries WHERE payment_date BETWEEN %s AND %s",
    $from_date,
    $to_date
) );

// Other Madrasah Expenses breakdown by category
$expenses_by_cat = $wpdb->get_results( $wpdb->prepare(
    "SELECT category, COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount 
     FROM $table_expenses 
     WHERE expense_date BETWEEN %s AND %s 
     GROUP BY category",
    $from_date,
    $to_date
) );

$total_other_expenses = $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM $table_expenses WHERE expense_date BETWEEN %s AND %s",
    $from_date,
    $to_date
) );

$grand_total_expense = $total_salary_expense + $total_other_expenses;
$net_surplus = $total_income - $grand_total_expense;
?>

<div class="mms-wrap">
    <div class="mms-header">
        <div class="mms-header-title">
            <h1>মাদ্রাসার আয়-ব্যয় ও আর্থিক হিসাব রিপোর্ট</h1>
            <p>নির্দিষ্ট সময়ের আয়, ব্যয়, ক্যাশ ব্যালেন্স ও প্রিন্টযোগ্য আর্থিক বিবরণী</p>
        </div>
        <div class="mms-header-actions">
            <button class="mms-btn mms-btn-primary" onclick="window.print();">🖨️ পূর্ণাঙ্গ রিপোর্ট প্রিন্ট করুন</button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="mms-card mms-filter-card mms-no-print">
        <form method="GET" action="" class="mms-filter-form">
            <input type="hidden" name="page" value="mms-reports">

            <div class="form-group-inline">
                <label>হতে (From):</label>
                <input type="date" name="from_date" value="<?php echo esc_attr( $from_date ); ?>">
            </div>

            <div class="form-group-inline">
                <label>পর্যন্ত (To):</label>
                <input type="date" name="to_date" value="<?php echo esc_attr( $to_date ); ?>">
            </div>

            <button type="submit" class="mms-btn mms-btn-secondary">রিপোর্ট দেখুন</button>
            <a href="<?php echo admin_url( 'admin.php?page=mms-reports' ); ?>" class="mms-btn mms-btn-outline">চলতি মাস</a>
        </form>
    </div>

    <!-- Printable Report Container -->
    <div class="mms-card mms-printable-report">
        <!-- Report Header for Print -->
        <div class="report-print-header">
            <div class="bismillah">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</div>
            <h2><?php echo esc_html( $madrasah_name ); ?></h2>
            <p><?php echo esc_html( ! empty( $settings['address'] ) ? $settings['address'] : '' ); ?></p>
            <h3>আয়-ব্যয় ও আর্থিক বিবরণী</h3>
            <p class="report-period">
                সময়কাল: <strong><?php echo date( 'd-m-Y', strtotime( $from_date ) ); ?></strong> হতে <strong><?php echo date( 'd-m-Y', strtotime( $to_date ) ); ?></strong>
            </p>
        </div>

        <!-- Summary Metric Boxes -->
        <div class="mms-grid-3 mms-no-print" style="margin-bottom: 25px;">
            <div class="mms-card stat-card stat-emerald">
                <div class="stat-info">
                    <h3><?php echo $currency . ' ' . number_format( $total_income, 2 ); ?></h3>
                    <p>সর্বমোট আয় (ফি আদায়)</p>
                </div>
            </div>

            <div class="mms-card stat-card stat-red">
                <div class="stat-info">
                    <h3><?php echo $currency . ' ' . number_format( $grand_total_expense, 2 ); ?></h3>
                    <p>সর্বমোট ব্যয় (বেতন + খরচ)</p>
                </div>
            </div>

            <div class="mms-card stat-card <?php echo $net_surplus >= 0 ? 'stat-green' : 'stat-red'; ?>">
                <div class="stat-info">
                    <h3><?php echo $currency . ' ' . number_format( $net_surplus, 2 ); ?></h3>
                    <p>নিট উদ্বৃত্ত / ক্যাশ ব্যালেন্স</p>
                </div>
            </div>
        </div>

        <!-- 2 Columns: Income vs Expense Breakdown -->
        <div class="mms-grid-2">
            <!-- Left: Income Breakdown -->
            <div class="report-box">
                <h3 class="box-title income-title">আয়ের বিবরণ (ইনকাম)</h3>
                <table class="mms-table">
                    <thead>
                        <tr>
                            <th>খাত / ফি ধরণ</th>
                            <th class="text-center">রসিদ সংখ্যা</th>
                            <th class="text-right">পরিমাণ (<?php echo $currency; ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $income_by_type ) ) : ?>
                            <?php foreach ( $income_by_type as $inc ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $inc->fee_type ); ?></strong></td>
                                    <td class="text-center"><?php echo esc_html( $inc->total_count ); ?> টি</td>
                                    <td class="text-right"><?php echo number_format( $inc->total_amount, 2 ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="3" class="text-center text-muted">কোনো আয় এন্ট্রি নেই।</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-right">মোট আয়:</th>
                            <th class="text-right text-success"><?php echo $currency . ' ' . number_format( $total_income, 2 ); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Right: Expense Breakdown -->
            <div class="report-box">
                <h3 class="box-title expense-title">ব্যয়ের বিবরণ (খরচ)</h3>
                <table class="mms-table">
                    <thead>
                        <tr>
                            <th>খাত / ক্যাটাগরি</th>
                            <th class="text-center">ভাউচার</th>
                            <th class="text-right">পরিমাণ (<?php echo $currency; ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>শিক্ষক ও স্টাফ বেতন</strong></td>
                            <td class="text-center">-</td>
                            <td class="text-right"><?php echo number_format( $total_salary_expense, 2 ); ?></td>
                        </tr>
                        <?php if ( ! empty( $expenses_by_cat ) ) : ?>
                            <?php foreach ( $expenses_by_cat as $ec ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $ec->category ); ?></td>
                                    <td class="text-center"><?php echo esc_html( $ec->total_count ); ?> টি</td>
                                    <td class="text-right"><?php echo number_format( $ec->total_amount, 2 ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-right">মোট ব্যয়:</th>
                            <th class="text-right text-danger"><?php echo $currency . ' ' . number_format( $grand_total_expense, 2 ); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Net Summary Row -->
        <div class="report-final-summary">
            <table class="mms-table final-table">
                <tr>
                    <td width="70%"><strong>সর্বমোট আয় (Total Revenue):</strong></td>
                    <td width="30%" class="text-right"><strong>+ <?php echo $currency . ' ' . number_format( $total_income, 2 ); ?></strong></td>
                </tr>
                <tr>
                    <td><strong>সর্বমোট ব্যয় (Total Expenditure):</strong></td>
                    <td class="text-right"><strong>- <?php echo $currency . ' ' . number_format( $grand_total_expense, 2 ); ?></strong></td>
                </tr>
                <tr class="grand-balance-row">
                    <td><strong>নিট উদ্বৃত্ত / স্থিতি (Net Balance):</strong></td>
                    <td class="text-right"><strong style="font-size: 18px; color: <?php echo $net_surplus >= 0 ? '#16a34a' : '#dc2626'; ?>;"><?php echo $currency . ' ' . number_format( $net_surplus, 2 ); ?></strong></td>
                </tr>
            </table>
        </div>

        <!-- Print Signatures -->
        <div class="mms-receipt-signatures report-signatures">
            <div class="sig-block">
                <span class="sig-line"></span>
                <span><?php echo esc_html( ! empty( $settings['accountant_title'] ) ? $settings['accountant_title'] : 'হিসাবরক্ষক' ); ?></span>
            </div>
            <div class="sig-block">
                <span class="sig-line"></span>
                <span><?php echo esc_html( ! empty( $settings['principal_title'] ) ? $settings['principal_title'] : 'মুহতামিম / অধ্যক্ষ' ); ?></span>
            </div>
        </div>
    </div>
</div>
