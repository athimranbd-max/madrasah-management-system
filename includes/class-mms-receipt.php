<?php
defined( 'ABSPATH' ) || exit;

class MMS_Receipt {

    public static function get_money_receipt_html( $fee_id ) {
        global $wpdb;
        $table_fees = $wpdb->prefix . 'mms_student_fees';
        $table_students = $wpdb->prefix . 'mms_students';

        $fee = $wpdb->get_row( $wpdb->prepare(
            "SELECT f.*, s.name_bn, s.roll_no, s.class_name, s.student_id as student_code, s.guardian_name, s.phone 
             FROM $table_fees f 
             JOIN $table_students s ON f.student_id = s.id 
             WHERE f.id = %d",
            $fee_id
        ) );

        if ( ! $fee ) {
            return '<p style="color:red;text-align:center;">রশিদ পাওয়া যায়নি!</p>';
        }

        $settings = get_option( 'mms_settings', array() );
        $madrasah_name    = ! empty( $settings['madrasah_name'] ) ? esc_html( $settings['madrasah_name'] ) : 'ফরমান আলী মণ্ডল রওজাতুল আতফাল মাদরাসা';
        $madrasah_tagline = ! empty( $settings['madrasah_tagline'] ) ? esc_html( $settings['madrasah_tagline'] ) : 'দ্বীনি ও আধুনিক শিক্ষার আদর্শ প্রতিষ্ঠান';
        $address          = ! empty( $settings['address'] ) ? esc_html( $settings['address'] ) : '';
        $phone            = ! empty( $settings['phone'] ) ? esc_html( $settings['phone'] ) : '';
        $currency         = ! empty( $settings['currency'] ) ? esc_html( $settings['currency'] ) : '৳';
        $accountant_title = ! empty( $settings['accountant_title'] ) ? esc_html( $settings['accountant_title'] ) : 'আদায়কারী / হিসাবরক্ষক';
        $principal_title  = ! empty( $settings['principal_title'] ) ? esc_html( $settings['principal_title'] ) : 'মুহতামিম / অধ্যক্ষ';

        ob_start();
        ?>
        <div class="mms-receipt-container">
            <div class="mms-receipt-header">
                <div class="bismillah">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</div>
                <h2><?php echo $madrasah_name; ?></h2>
                <?php if ( $madrasah_tagline ) : ?><p class="tagline"><?php echo $madrasah_tagline; ?></p><?php endif; ?>
                <p class="contact-info"><?php echo $address; ?> <?php echo $phone ? ' | মোবা: ' . $phone : ''; ?></p>
                <div class="receipt-badge">টাকা জমার মানি রিসিট</div>
            </div>

            <div class="mms-receipt-meta">
                <div class="meta-col">
                    <p><strong>রশিদ নং:</strong> <?php echo esc_html( $fee->voucher_no ); ?></p>
                    <p><strong>আইডি নং:</strong> <?php echo esc_html( $fee->student_code ); ?></p>
                </div>
                <div class="meta-col text-right">
                    <p><strong>তারিখ:</strong> <?php echo date( 'd-m-Y', strtotime( $fee->paid_date ) ); ?></p>
                    <p><strong>পেমেন্ট মাধ্যম:</strong> <?php echo esc_html( $fee->payment_method ); ?></p>
                </div>
            </div>

            <div class="mms-receipt-student">
                <table class="info-table">
                    <tr>
                        <td width="50%"><strong>শিক্ষার্থীর নাম:</strong> <?php echo esc_html( $fee->name_bn ); ?></td>
                        <td width="25%"><strong>শ্রেণি:</strong> <?php echo esc_html( $fee->class_name ); ?></td>
                        <td width="25%"><strong>রোল নং:</strong> <?php echo esc_html( $fee->roll_no ); ?></td>
                    </tr>
                    <tr>
                        <td><strong>অভিভাবকের নাম:</strong> <?php echo esc_html( $fee->guardian_name ? $fee->guardian_name : '-' ); ?></td>
                        <td colspan="2"><strong>অভিভাবকের মোবাইল:</strong> <?php echo esc_html( $fee->phone ? $fee->phone : '-' ); ?></td>
                    </tr>
                </table>
            </div>

            <table class="receipt-items-table">
                <thead>
                    <tr>
                        <th width="10%">ক্র.নং</th>
                        <th width="65%">ফি-এর বিবরণ</th>
                        <th width="25%" class="text-right">পরিমাণ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">১</td>
                        <td>
                            <strong><?php echo esc_html( $fee->fee_type ); ?></strong>
                            <?php if ( ! empty( $fee->month_year ) ) : ?>
                                <span class="month-tag">(মাস: <?php echo esc_html( $fee->month_year ); ?>)</span>
                            <?php endif; ?>
                            <?php if ( ! empty( $fee->remarks ) ) : ?>
                                <div class="fee-note"><?php echo esc_html( $fee->remarks ); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><strong><?php echo $currency . ' ' . number_format( $fee->amount, 2 ); ?></strong></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-right">মোট আদায়:</th>
                        <th class="text-right"><?php echo $currency . ' ' . number_format( $fee->amount, 2 ); ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="mms-receipt-signatures">
                <div class="sig-block">
                    <span class="sig-line"></span>
                    <span><?php echo $accountant_title; ?></span>
                </div>
                <div class="sig-block">
                    <span class="sig-line"></span>
                    <span><?php echo $principal_title; ?></span>
                </div>
            </div>
            <div class="receipt-footer-note">ধন্যবাদ। রশিদটি সংরক্ষণ করুন।</div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function get_salary_slip_html( $salary_id ) {
        global $wpdb;
        $table_salaries = $wpdb->prefix . 'mms_teacher_salaries';
        $table_teachers = $wpdb->prefix . 'mms_teachers';

        $salary = $wpdb->get_row( $wpdb->prepare(
            "SELECT s.*, t.name, t.designation, t.teacher_id as teacher_code, t.phone 
             FROM $table_salaries s 
             JOIN $table_teachers t ON s.teacher_id = t.id 
             WHERE s.id = %d",
            $salary_id
        ) );

        if ( ! $salary ) {
            return '<p style="color:red;text-align:center;">বেতন রশিদ পাওয়া যায়নি!</p>';
        }

        $settings = get_option( 'mms_settings', array() );
        $madrasah_name    = ! empty( $settings['madrasah_name'] ) ? esc_html( $settings['madrasah_name'] ) : 'ফরমান আলী মণ্ডল রওজাতুল আতফাল মাদরাসা';
        $currency         = ! empty( $settings['currency'] ) ? esc_html( $settings['currency'] ) : '৳';
        $principal_title  = ! empty( $settings['principal_title'] ) ? esc_html( $settings['principal_title'] ) : 'মুহতামিম / অধ্যক্ষ';

        ob_start();
        ?>
        <div class="mms-receipt-container">
            <div class="mms-receipt-header">
                <div class="bismillah">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</div>
                <h2><?php echo $madrasah_name; ?></h2>
                <div class="receipt-badge">শিক্ষক ও স্টাফ বেতন স্লিপ</div>
            </div>

            <div class="mms-receipt-meta">
                <div class="meta-col">
                    <p><strong>ভাউচার নং:</strong> <?php echo esc_html( $salary->voucher_no ); ?></p>
                    <p><strong>শিক্ষক আইডি:</strong> <?php echo esc_html( $salary->teacher_code ); ?></p>
                </div>
                <div class="meta-col text-right">
                    <p><strong>পরিশোধের তারিখ:</strong> <?php echo date( 'd-m-Y', strtotime( $salary->payment_date ) ); ?></p>
                    <p><strong>বেতনের মাস:</strong> <?php echo esc_html( $salary->month_year ); ?></p>
                </div>
            </div>

            <div class="mms-receipt-student">
                <table class="info-table">
                    <tr>
                        <td width="60%"><strong>নাম:</strong> <?php echo esc_html( $salary->name ); ?></td>
                        <td width="40%"><strong>পদবি:</strong> <?php echo esc_html( $salary->designation ); ?></td>
                    </tr>
                </table>
            </div>

            <table class="receipt-items-table">
                <thead>
                    <tr>
                        <th>বিবরণ</th>
                        <th class="text-right">পরিমাণ (<?php echo $currency; ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>মূল বেতন (Basic Salary)</td>
                        <td class="text-right"><?php echo number_format( $salary->basic_salary, 2 ); ?></td>
                    </tr>
                    <?php if ( $salary->bonus > 0 ) : ?>
                    <tr>
                        <td>বোনাস / ভাতা (Bonus / Allowance)</td>
                        <td class="text-right">+ <?php echo number_format( $salary->bonus, 2 ); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ( $salary->deduction > 0 ) : ?>
                    <tr>
                        <td>কর্তন / অগ্রিম কর্তন (Deduction)</td>
                        <td class="text-right">- <?php echo number_format( $salary->deduction, 2 ); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th class="text-right">মোট প্রদেয় বেতন (Net Paid):</th>
                        <th class="text-right"><?php echo $currency . ' ' . number_format( $salary->net_paid, 2 ); ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="mms-receipt-signatures">
                <div class="sig-block">
                    <span class="sig-line"></span>
                    <span>শিক্ষক / স্টাফ এর স্বাক্ষর</span>
                </div>
                <div class="sig-block">
                    <span class="sig-line"></span>
                    <span><?php echo $principal_title; ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
