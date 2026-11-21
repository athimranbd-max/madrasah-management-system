<?php
defined( 'ABSPATH' ) || exit;

// Strictly Main Admin Only
if ( ! MMS_Admin::is_main_admin() ) {
    wp_die( 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন সেটিংস পরিবর্তন করতে পারেন।' );
}

$settings = get_option( 'mms_settings', array() );
$classes = get_option( 'mms_classes', array( 'শিশু ১', 'শিশু ২', '১ম শ্রেণি', '২য় শ্রেণি', '৩য় শ্রেণি' ) );
$fee_types = get_option( 'mms_fee_types', array( 'মাসিক বেতন', 'পরীক্ষার ফি', 'ভর্তি ফি', 'সেশন ফি', 'বই ও খাতা বাবদ', 'অন্যান্য ফি' ) );
$expense_categories = get_option( 'mms_expense_categories', array(
    'বিদ্যুৎ ও গ্যাস বিল',
    'খাবার/বোর্ডিং খরচ',
    'শিক্ষক ও স্টাফ নাস্তা/আপ্যায়ন',
    'বই ও স্টেশনারি',
    'মেরামত ও সংস্কার',
    'মাদ্রাসার ভাড়া',
    'অন্যান্য খরচ'
) );
?>

<div class="mms-wrap">
    <div class="mms-header">
        <div class="mms-header-title">
            <h1>মাদ্রাসা সেটিংস ও কনফিগারেশন</h1>
            <p>প্রতিষ্ঠানের পরিচিতি, শ্রেণি বিন্যাস, ফি ধরণ ও খরচের ক্যাটাগরি কনফিগার করুন</p>
        </div>
    </div>

    <form id="mms-settings-form">
        <div class="mms-grid-2">
            <!-- Left: Madrasah Details -->
            <div class="mms-card">
                <div class="card-header">
                    <h2>মাদ্রাসার পরিচিতি ও রশিদ তথ্য</h2>
                </div>
                <div class="mms-form-grid" style="grid-template-columns: 1fr;">
                    <div class="form-group">
                        <label>মাদ্রাসার পুরো নাম <span class="required">*</span></label>
                        <input type="text" name="madrasah_name" value="<?php echo esc_attr( isset( $settings['madrasah_name'] ) ? $settings['madrasah_name'] : 'ফরমান আলী মণ্ডল রওজাতুল আতফাল মাদরাসা' ); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>স্লোগান / ট্যাগলাইন</label>
                        <input type="text" name="madrasah_tagline" value="<?php echo esc_attr( isset( $settings['madrasah_tagline'] ) ? $settings['madrasah_tagline'] : 'দ্বীনি ও আধুনিক শিক্ষার আদর্শ প্রতিষ্ঠান' ); ?>">
                    </div>

                    <div class="form-group">
                        <label>ঠিকানা (রশিদে প্রদর্শিত হবে)</label>
                        <textarea name="address" rows="2"><?php echo esc_textarea( isset( $settings['address'] ) ? $settings['address'] : '' ); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>যোগাযোগের মোবাইল নম্বর</label>
                        <input type="text" name="phone" value="<?php echo esc_attr( isset( $settings['phone'] ) ? $settings['phone'] : '' ); ?>">
                    </div>

                    <div class="form-group">
                        <label>ইমেইল অ্যাড্রেস</label>
                        <input type="email" name="email" value="<?php echo esc_attr( isset( $settings['email'] ) ? $settings['email'] : '' ); ?>">
                    </div>

                    <div class="form-group">
                        <label>মুদ্রা প্রতীক (Currency Symbol)</label>
                        <input type="text" name="currency" value="<?php echo esc_attr( isset( $settings['currency'] ) ? $settings['currency'] : '৳' ); ?>" style="width: 100px;">
                    </div>

                    <div class="form-group">
                        <label>প্রধানের পদবি (রশিদে স্বাক্ষর)</label>
                        <input type="text" name="principal_title" value="<?php echo esc_attr( isset( $settings['principal_title'] ) ? $settings['principal_title'] : 'মুহতামিম / অধ্যক্ষ' ); ?>">
                    </div>

                    <div class="form-group">
                        <label>হিসাবরক্ষকের পদবি (রশিদে স্বাক্ষর)</label>
                        <input type="text" name="accountant_title" value="<?php echo esc_attr( isset( $settings['accountant_title'] ) ? $settings['accountant_title'] : 'আদায়কারী / হিসাবরক্ষক' ); ?>">
                    </div>
                </div>
            </div>

            <!-- Right: Classes, Fees & Expense Categories -->
            <div class="mms-card">
                <div class="card-header">
                    <h2>শ্রেণি, ফি ও খরচের তালিকা</h2>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label><strong>মাদ্রাসার শ্রেণি তালিকা</strong> (প্রতি লাইনে একটি করে শ্রেণি লিখুন):</label>
                    <textarea name="classes" rows="5" style="width:100%; font-family:inherit; padding:8px; border-radius:6px;"><?php echo esc_textarea( implode( "\n", $classes ) ); ?></textarea>
                    <small class="text-muted">উদা: শিশু ১, শিশু ২, ১ম শ্রেণি, ২য় শ্রেণি, ৩য় শ্রেণি ইত্যাদি।</small>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label><strong>ফি-এর ধরণের তালিকা</strong> (প্রতি লাইনে একটি করে):</label>
                    <textarea name="fee_types" rows="5" style="width:100%; font-family:inherit; padding:8px; border-radius:6px;"><?php echo esc_textarea( implode( "\n", $fee_types ) ); ?></textarea>
                    <small class="text-muted">উদা: মাসিক বেতন, পরীক্ষার ফি, ভর্তি ফি, সেশন ফি ইত্যাদি।</small>
                </div>

                <div class="form-group">
                    <label><strong>মাদ্রাসার খরচের খাতসমূহ (ক্যাটাগরি)</strong> (প্রতি লাইনে একটি করে):</label>
                    <textarea name="expense_categories" rows="5" style="width:100%; font-family:inherit; padding:8px; border-radius:6px;"><?php echo esc_textarea( implode( "\n", $expense_categories ) ); ?></textarea>
                    <small class="text-muted">উদা: বিদ্যুৎ বিল, খাবার/বোর্ডিং খরচ, আপ্যায়ন, মেরামত ইত্যাদি।</small>
                </div>
            </div>
        </div>

        <div class="mms-card" style="margin-top: 20px; text-align: right;">
            <button type="submit" class="mms-btn mms-btn-primary mms-btn-lg" id="mms-save-settings-btn">💾 সেটিংস সংরক্ষণ করুন</button>
        </div>
    </form>
</div>
