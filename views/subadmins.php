<?php
defined( 'ABSPATH' ) || exit;

// Strictly Main Admin Only
if ( ! MMS_Admin::is_main_admin() ) {
    wp_die( 'অনুমতি নেই! শুধুমাত্র প্রধান অ্যাডমিন এই পেজটি দেখতে পারেন।' );
}

$classes = get_option( 'mms_classes', array( 'শিশু ১', 'শিশু ২', '১ম শ্রেণি', '২য় শ্রেণি', '৩য় শ্রেণি' ) );

// Fetch all Sub-Admins
$subadmin_users = get_users( array(
    'role' => 'mms_subadmin',
    'orderby' => 'ID',
    'order' => 'ASC',
) );
?>

<div class="mms-wrap">
    <div class="mms-header">
        <div class="mms-header-title">
            <h1>সাব-অ্যাডমিন ও শ্রেণি শিক্ষক ব্যবস্থাপনা</h1>
            <p>শ্রেণিভিত্তিক সহকারী অ্যাডমিন তৈরি, আইডি-পাসওয়ার্ড প্রদান এবং দায়িত্ব নির্ধারণ</p>
        </div>
        <div class="mms-header-actions">
            <button class="mms-btn mms-btn-primary" id="mms-btn-add-subadmin">+ নতুন সাব-অ্যাডমিন তৈরি করুন</button>
        </div>
    </div>

    <!-- Permission Rules Explanation Card -->
    <div class="mms-card" style="background: #f8fafc; border-left: 4px solid var(--mms-primary);">
        <div style="font-size: 13px; line-height: 1.6;">
            <strong>🔒 পারমিশন ও নিয়মাবলী:</strong>
            <ul style="margin: 6px 0 0 18px; list-style-type: disc; color: var(--mms-muted);">
                <li><strong>প্রধান অ্যাডমিন (আপনি):</strong> সকল ক্ষমতা সংরক্ষণ করেন। নতুন ইউজার/সাব-অ্যাডমিন তৈরি, শিক্ষার্থী ভর্তি, এডিট ও ডিলিট করতে পারবেন।</li>
                <li><strong>সাব-অ্যাডমিন:</strong> আপনি যে শ্রেণির দায়িত্ব দিবেন (যেমন: শিশু ১ বা শিশু ২), সে শুধুমাত্র সেই শ্রেণির হাজিরা নিতে পারবে এবং ফি আদায় করতে পারবে।</li>
                <li><strong>নিরাপত্তা বিধিনিষেধ:</strong> সাব-অ্যাডমিন <u>কখনোই কোনো ইউজার বা শিক্ষার্থী যুক্ত (Add) বা মুছে (Delete) ফেলতে পারবে না</u>।</li>
            </ul>
        </div>
    </div>

    <!-- Sub-Admins List Table -->
    <div class="mms-card">
        <div class="card-header flex-between">
            <h2>সাব-অ্যাডমিন তালিকা (মোট: <?php echo count( $subadmin_users ); ?> জন)</h2>
        </div>
        <div class="table-responsive">
            <table class="mms-table">
                <thead>
                    <tr>
                        <th>আইডি</th>
                        <th>নাম</th>
                        <th>ইউজার আইডি (লগইন)</th>
                        <th>দায়িত্বপ্রাপ্ত শ্রেণি</th>
                        <th>পদবি</th>
                        <th>পারমিশন ক্ষমতা</th>
                        <th>অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $subadmin_users ) ) : ?>
                        <?php foreach ( $subadmin_users as $u ) : 
                            $assigned = get_user_meta( $u->ID, 'mms_assigned_class', true );
                            $designation = get_user_meta( $u->ID, 'mms_designation', true );
                        ?>
                            <tr>
                                <td><strong>#<?php echo esc_html( $u->ID ); ?></strong></td>
                                <td><strong><?php echo esc_html( $u->display_name ); ?></strong></td>
                                <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?php echo esc_html( $u->user_login ); ?></code></td>
                                <td>
                                    <span class="badge badge-class" style="font-size: 13px;">
                                        <?php echo esc_html( $assigned ? $assigned : 'সকল শ্রেণি' ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $designation ? $designation : 'শ্রেণি শিক্ষক' ); ?></td>
                                <td>
                                    <span class="badge badge-warning" style="font-size: 11px;">
                                        শুধু হাজিরা ও ফি (নো ডিলিট/অ্যাড)
                                    </span>
                                </td>
                                <td>
                                    <button class="mms-btn-sm mms-btn-outline mms-edit-subadmin" 
                                            data-id="<?php echo esc_attr( $u->ID ); ?>"
                                            data-name="<?php echo esc_attr( $u->display_name ); ?>"
                                            data-username="<?php echo esc_attr( $u->user_login ); ?>"
                                            data-email="<?php echo esc_attr( $u->user_email ); ?>"
                                            data-class="<?php echo esc_attr( $assigned ); ?>"
                                            data-designation="<?php echo esc_attr( $designation ); ?>">
                                        পাসওয়ার্ড/শ্রেণি পরিবর্তন
                                    </button>
                                    <button class="mms-btn-sm mms-btn-danger mms-delete-subadmin" data-id="<?php echo esc_attr( $u->ID ); ?>">মুছুন</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="7" class="text-center text-muted" style="padding: 30px;">কোনো সাব-অ্যাডমিন অ্যাকাউন্ট তৈরি করা হয়নি। উপরে <strong>"+ নতুন সাব-অ্যাডমিন তৈরি করুন"</strong> বাটনে ক্লিক করে তৈরি করুন।</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Sub-Admin Modal -->
<div id="mms-subadmin-modal" class="mms-modal">
    <div class="mms-modal-content">
        <div class="mms-modal-header">
            <h3 id="mms-subadmin-modal-title">নতুন সাব-অ্যাডমিন তৈরি ফরম</h3>
            <span class="mms-modal-close">&times;</span>
        </div>
        <form id="mms-subadmin-form">
            <input type="hidden" name="user_id" id="subadmin_user_id" value="0">
            <div class="mms-modal-body">
                <div class="mms-form-grid">
                    <div class="form-group full-width">
                        <label>শিক্ষক / সাব-অ্যাডমিনের পুরো নাম <span class="required">*</span></label>
                        <input type="text" name="name" id="subadmin_name" required placeholder="উদা: মাওলানা আহমাদ উল্লাহ">
                    </div>

                    <div class="form-group">
                        <label>ইউজার আইডি / ইউজারনেম (লগইনের জন্য) <span class="required">*</span></label>
                        <input type="text" name="username" id="subadmin_username" required placeholder="উদা: teacher_shishu1">
                        <small class="text-muted">ইংরেজিতে ছোট হাতের অক্ষরে লিখুন</small>
                    </div>

                    <div class="form-group">
                        <label>লগইন পাসওয়ার্ড <span class="required">*</span></label>
                        <input type="password" name="password" id="subadmin_password" placeholder="গোপন পাসওয়ার্ড লিখুন">
                        <small class="text-muted" id="subadmin_pass_hint">সাব-অ্যাডমিন এই পাসওয়ার্ড দিয়ে লগইন করবেন</small>
                    </div>

                    <div class="form-group">
                        <label>দায়িত্বপ্রাপ্ত শ্রেণি <span class="required">*</span></label>
                        <select name="assigned_class" id="subadmin_assigned_class" required>
                            <option value="">-- শ্রেণি নির্বাচন করুন --</option>
                            <?php foreach ( $classes as $c ) : ?>
                                <option value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?></option>
                            <?php endforeach; ?>
                            <option value="all">সকল শ্রেণি (সাধারণ দায়িত্ব)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>পদবি</label>
                        <input type="text" name="designation" id="subadmin_designation" placeholder="উদা: শ্রেণি শিক্ষক - শিশু ১">
                    </div>

                    <div class="form-group full-width">
                        <label>ইমেইল অ্যাড্রেস (ঐচ্ছিক)</label>
                        <input type="email" name="email" id="subadmin_email" placeholder="উদা: teacher@gmail.com">
                        <small class="text-muted">খালি রাখলে স্বয়ংক্রিয়ভাবে একটি সিস্টেম ইমেইল তৈরি হয়ে যাবে</small>
                    </div>
                </div>
            </div>
            <div class="mms-modal-footer">
                <button type="submit" class="mms-btn mms-btn-primary" id="mms-save-subadmin-btn">💾 সাব-অ্যাডমিন অ্যাকাউন্ট তৈরি করুন</button>
                <button type="button" class="mms-btn mms-btn-outline mms-modal-close-btn">বাতিল</button>
            </div>
        </form>
    </div>
</div>
