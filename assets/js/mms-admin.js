jQuery(document).ready(function ($) {
    'use strict';

    var ajaxurl = mmsData.ajax_url;
    var nonce = mmsData.nonce;
    var isMainAdmin = mmsData.is_main_admin;

    // Helper: Close Modals
    $('.mms-modal-close, .mms-modal-close-btn').on('click', function () {
        $('.mms-modal').fadeOut(200);
    });

    $(window).on('click', function (e) {
        if ($(e.target).hasClass('mms-modal')) {
            $('.mms-modal').fadeOut(200);
        }
    });

    /* =========================================================================
     * 0. SUB-ADMINS MANAGEMENT (Main Admin Only)
     * ========================================================================= */
    $('#mms-btn-add-subadmin').on('click', function () {
        $('#mms-subadmin-form')[0].reset();
        $('#subadmin_user_id').val('0');
        $('#subadmin_username').prop('readonly', false);
        $('#subadmin_email').val('');
        $('#mms-subadmin-modal-title').text('নতুন সাব-অ্যাডমিন তৈরি ফরম');
        $('#subadmin_pass_hint').text('সাব-অ্যাডমিন এই পাসওয়ার্ড দিয়ে লগইন করবেন');
        $('#subadmin_password').prop('required', true);
        $('#mms-subadmin-modal').fadeIn(200);
    });

    $('.mms-edit-subadmin').on('click', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var username = $(this).data('username');
        var email = $(this).data('email') || '';
        var cls = $(this).data('class');
        var des = $(this).data('designation');

        $('#subadmin_user_id').val(id);
        $('#subadmin_name').val(name);
        $('#subadmin_username').val(username).prop('readonly', true);
        $('#subadmin_email').val(email);
        $('#subadmin_assigned_class').val(cls);
        $('#subadmin_designation').val(des);
        $('#subadmin_password').val('').prop('required', false);
        $('#subadmin_pass_hint').text('পাসওয়ার্ড পরিবর্তন না করতে চাইলে খালি রাখুন');
        $('#mms-subadmin-modal-title').text('সাব-অ্যাডমিন তথ্য পরিবর্তন');
        $('#mms-subadmin-modal').fadeIn(200);
    });

    $('#mms-subadmin-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#mms-save-subadmin-btn');
        $btn.prop('disabled', true).text('সংরক্ষণ হচ্ছে...');

        var formData = $(this).serialize() + '&action=mms_save_subadmin&nonce=' + nonce;

        $.post(ajaxurl, formData, function (res) {
            $btn.prop('disabled', false).text('💾 সাব-অ্যাডমিন অ্যাকাউন্ট তৈরি করুন');
            if (res.success) {
                alert(res.data.message);
                location.reload();
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('💾 সাব-অ্যাডমিন অ্যাকাউন্ট তৈরি করুন');
            alert(mmsData.strings.error);
        });
    });

    $('.mms-delete-subadmin').on('click', function () {
        if (!confirm('আপনি কি নিশ্চিত যে এই সাব-অ্যাডমিন অ্যাকাউন্টটি মুছে ফেলতে চান?')) return;
        var id = $(this).data('id');

        $.post(ajaxurl, {
            action: 'mms_delete_subadmin',
            nonce: nonce,
            user_id: id
        }, function (res) {
            if (res.success) {
                alert(res.data.message);
                location.reload();
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        });
    });

    /* =========================================================================
     * 1. STUDENTS
     * ========================================================================= */
    $('#mms-btn-add-student, #mms-btn-add-student-empty').on('click', function () {
        if (!isMainAdmin) {
            alert(mmsData.strings.permission_err);
            return;
        }
        $('#mms-student-form')[0].reset();
        $('#student_id_field').val('0');
        $('#mms-student-modal-title').text('নতুন শিক্ষার্থী ভর্তি ফরম');
        $('#student_admission_date').val(new Date().toISOString().split('T')[0]);
        $('#mms-student-modal').fadeIn(200);
    });

    // View Student Fee Ledger
    $(document).on('click', '.mms-view-student-ledger', function () {
        var studentId = $(this).data('id');
        if (!studentId) return;

        $('#mms-student-ledger-content').html('<p style="text-align:center; padding:30px;">লেজার ও ফি বিবরণী লোড হচ্ছে...</p>');
        $('#mms-student-ledger-modal').fadeIn(200);

        $.post(ajaxurl, {
            action: 'mms_get_student_ledger',
            nonce: nonce,
            student_id: studentId
        }, function (res) {
            if (res.success) {
                $('#mms-student-ledger-content').html(res.data.html);
            } else {
                $('#mms-student-ledger-content').html('<p style="color:red; text-align:center; padding:20px;">' + (res.data.message || mmsData.strings.error) + '</p>');
            }
        }).fail(function () {
            $('#mms-student-ledger-content').html('<p style="color:red; text-align:center; padding:20px;">' + mmsData.strings.error + '</p>');
        });
    });

    $('.mms-edit-student').on('click', function () {
        var s = $(this).data('student');
        if (!s) return;

        $('#student_id_field').val(s.id);
        $('#student_name_bn').val(s.name_bn);
        $('#student_name_en').val(s.name_en);
        $('#student_class_name').val(s.class_name);
        $('#student_roll_no').val(s.roll_no);
        $('#student_guardian_name').val(s.guardian_name);
        $('#student_phone').val(s.phone);
        $('#student_monthly_fee').val(s.monthly_fee);
        $('#student_admission_date').val(s.admission_date);
        $('#student_address').val(s.address);
        $('#student_status').val(s.status);

        $('#mms-student-modal-title').text('শিক্ষার্থীর তথ্য পরিবর্তন');
        $('#mms-student-modal').fadeIn(200);
    });

    // View Student Fee Ledger
    $(document).on('click', '.mms-view-student-ledger', function () {
        var studentId = $(this).data('id');
        if (!studentId) return;

        $('#mms-student-ledger-content').html('<p style="text-align:center; padding:30px;">লেজার ও ফি বিবরণী লোড হচ্ছে...</p>');
        $('#mms-student-ledger-modal').fadeIn(200);

        $.post(ajaxurl, {
            action: 'mms_get_student_ledger',
            nonce: nonce,
            student_id: studentId
        }, function (res) {
            if (res.success) {
                $('#mms-student-ledger-content').html(res.data.html);
            } else {
                $('#mms-student-ledger-content').html('<p style="color:red; text-align:center; padding:20px;">' + (res.data.message || mmsData.strings.error) + '</p>');
            }
        }).fail(function () {
            $('#mms-student-ledger-content').html('<p style="color:red; text-align:center; padding:20px;">' + mmsData.strings.error + '</p>');
        });
    });

    $('#mms-student-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#mms-student-submit-btn');
        $btn.prop('disabled', true).text('সংরক্ষণ হচ্ছে...');

        var formData = $(this).serialize() + '&action=mms_save_student&nonce=' + nonce;

        $.post(ajaxurl, formData, function (res) {
            $btn.prop('disabled', false).text('সংরক্ষণ করুন');
            if (res.success) {
                alert(res.data.message);
                location.reload();
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('সংরক্ষণ করুন');
            alert(mmsData.strings.error);
        });
    });

    $('.mms-delete-student').on('click', function () {
        if (!isMainAdmin) {
            alert(mmsData.strings.permission_err);
            return;
        }
        if (!confirm(mmsData.strings.confirm_delete)) return;
        var id = $(this).data('id');

        $.post(ajaxurl, {
            action: 'mms_delete_student',
            nonce: nonce,
            id: id
        }, function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        });
    });

    /* =========================================================================
     * 2. STUDENT ATTENDANCE
     * ========================================================================= */
    $('#mms-btn-load-attendance').on('click', function () {
        var date = $('#mms-att-date').val();
        var className = $('#mms-att-class').val();

        if (!date || !className) {
            alert('শ্রেণি ও তারিখ নির্বাচন করুন!');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('লোড হচ্ছে...');

        $.post(ajaxurl, {
            action: 'mms_get_students_for_attendance',
            nonce: nonce,
            class_name: className,
            date: date
        }, function (res) {
            $btn.prop('disabled', false).text('হাজিরা শিট আনুন →');

            if (res.success) {
                var students = res.data.students;
                $('#display-class').text(className);
                $('#display-date').text(date);
                $('#display-count').text(students.length);

                var tbodyHtml = '';
                if (students.length === 0) {
                    tbodyHtml = '<tr><td colspan="5" class="text-center text-muted" style="padding:20px;">এই শ্রেণিতে কোনো সক্রিয় শিক্ষার্থী পাওয়া যায়নি।</td></tr>';
                } else {
                    $.each(students, function (idx, s) {
                        var pChecked = s.attendance_status === 'present' ? 'checked' : '';
                        var aChecked = s.attendance_status === 'absent' ? 'checked' : '';
                        var lChecked = s.attendance_status === 'leave' ? 'checked' : '';
                        var ltChecked = s.attendance_status === 'late' ? 'checked' : '';

                        tbodyHtml += '<tr>' +
                            '<td><strong>' + s.roll_no + '</strong></td>' +
                            '<td>' + s.student_id + '</td>' +
                            '<td><strong>' + s.name_bn + '</strong></td>' +
                            '<td>' +
                                '<div class="mms-radio-group">' +
                                    '<label class="radio-label status-present"><input type="radio" name="attendance[' + s.id + '][status]" value="present" ' + pChecked + '><span>উপস্থিত</span></label>' +
                                    '<label class="radio-label status-absent"><input type="radio" name="attendance[' + s.id + '][status]" value="absent" ' + aChecked + '><span>অনুপস্থিত</span></label>' +
                                    '<label class="radio-label status-leave"><input type="radio" name="attendance[' + s.id + '][status]" value="leave" ' + lChecked + '><span>ছুটি</span></label>' +
                                    '<label class="radio-label status-late"><input type="radio" name="attendance[' + s.id + '][status]" value="late" ' + ltChecked + '><span>বিলম্ব</span></label>' +
                                '</div>' +
                            '</td>' +
                            '<td><input type="text" name="attendance[' + s.id + '][remarks]" value="' + (s.remarks || '') + '" placeholder="মন্তব্য..." class="form-control-sm" style="width:100%;padding:4px 8px;border:1px solid #e2e8f0;border-radius:4px;"></td>' +
                            '</tr>';
                    });
                }

                $('#mms-attendance-tbody').html(tbodyHtml);
                $('#mms-att-empty-notice').hide();
                $('#mms-attendance-box').fadeIn(200);
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        });
    });

    $('#mms-mark-all-present').on('click', function () {
        $('#mms-attendance-tbody').find('input[type="radio"][value="present"]').prop('checked', true);
    });

    $('#mms-mark-all-absent').on('click', function () {
        $('#mms-attendance-tbody').find('input[type="radio"][value="absent"]').prop('checked', true);
    });

    $('#mms-attendance-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#mms-save-att-btn');
        $btn.prop('disabled', true).text('সংরক্ষণ হচ্ছে...');

        var className = $('#mms-att-class').val();
        var date = $('#mms-att-date').val();

        var formData = $(this).serialize() + '&action=mms_save_student_attendance&class_name=' + encodeURIComponent(className) + '&date=' + encodeURIComponent(date) + '&nonce=' + nonce;

        $.post(ajaxurl, formData, function (res) {
            $btn.prop('disabled', false).text('💾 হাজিরা সংরক্ষণ করুন');
            if (res.success) {
                alert(res.data.message);
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('💾 হাজিরা সংরক্ষণ করুন');
            alert(mmsData.strings.error);
        });
    });

    /* =========================================================================
     * 3. STUDENT FEES & MONEY RECEIPT
     * ========================================================================= */
    $('#mms-btn-collect-fee').on('click', function () {
        $('#mms-fee-form')[0].reset();
        $('#fee_paid_date').val(new Date().toISOString().split('T')[0]);
        $('#mms-fee-modal').fadeIn(200);
    });

    $('#fee_student_id').on('change', function () {
        var fee = $(this).find('option:selected').data('fee');
        if (fee && parseFloat(fee) > 0) {
            $('#fee_amount').val(fee);
        }
    });

    $('.mms-quick-collect').on('click', function () {
        var sId = $(this).data('student-id');
        var fee = $(this).data('fee');

        $('#mms-fee-form')[0].reset();
        $('#fee_paid_date').val(new Date().toISOString().split('T')[0]);
        $('#fee_student_id').val(sId).trigger('change');
        if (fee) {
            $('#fee_amount').val(fee);
        }
        $('#mms-fee-modal').fadeIn(200);
    });

    $('#mms-fee-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#mms-save-fee-btn');
        $btn.prop('disabled', true).text('ফি জমা হচ্ছে...');

        var formData = $(this).serialize() + '&action=mms_save_student_fee&nonce=' + nonce;

        $.post(ajaxurl, formData, function (res) {
            $btn.prop('disabled', false).text('💾 ফি জমা করুন ও রশিদ দেখুন');
            if (res.success) {
                $('#mms-fee-modal').hide();
                loadMoneyReceipt(res.data.fee_id);
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('💾 ফি জমা করুন ও রশিদ দেখুন');
            alert(mmsData.strings.error);
        });
    });

    $('.mms-view-receipt').on('click', function () {
        var feeId = $(this).data('id');
        loadMoneyReceipt(feeId);
    });

    function loadMoneyReceipt(feeId) {
        $('#mms-receipt-content').html('<p style="text-align:center;padding:20px;">রশিদ লোড হচ্ছে...</p>');
        $('#mms-receipt-modal').fadeIn(200);

        $.post(ajaxurl, {
            action: 'mms_get_money_receipt',
            nonce: nonce,
            fee_id: feeId
        }, function (res) {
            if (res.success) {
                $('#mms-receipt-content').html(res.data.html);
            } else {
                $('#mms-receipt-content').html('<p style="color:red;text-align:center;">রশিদ লোড ব্যর্থ হয়েছে।</p>');
            }
        });
    }

    $('.mms-delete-fee').on('click', function () {
        if (!isMainAdmin) {
            alert(mmsData.strings.permission_err);
            return;
        }
        if (!confirm(mmsData.strings.confirm_delete)) return;
        var id = $(this).data('id');

        $.post(ajaxurl, {
            action: 'mms_delete_student_fee',
            nonce: nonce,
            id: id
        }, function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        });
    });

    /* =========================================================================
     * 4. TEACHERS & ATTENDANCE (Main Admin Only)
     * ========================================================================= */
    $('#mms-btn-add-teacher').on('click', function () {
        $('#mms-teacher-form')[0].reset();
        $('#teacher_id_field').val('0');
        $('#mms-teacher-modal-title').text('নতুন শিক্ষক / স্টাফ ফরম');
        $('#teacher_joining_date').val(new Date().toISOString().split('T')[0]);
        $('#mms-teacher-modal').fadeIn(200);
    });

    $('.mms-edit-teacher').on('click', function () {
        var t = $(this).data('teacher');
        if (!t) return;

        $('#teacher_id_field').val(t.id);
        $('#teacher_name').val(t.name);
        $('#teacher_designation').val(t.designation);
        $('#teacher_phone').val(t.phone);
        $('#teacher_monthly_salary').val(t.monthly_salary);
        $('#teacher_joining_date').val(t.joining_date);
        $('#teacher_status').val(t.status);
        $('#teacher_address').val(t.address);

        $('#mms-teacher-modal-title').text('শিক্ষক তথ্য পরিবর্তন');
        $('#mms-teacher-modal').fadeIn(200);
    });

    $('#mms-teacher-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#mms-teacher-submit-btn');
        $btn.prop('disabled', true).text('সংরক্ষণ হচ্ছে...');

        var formData = $(this).serialize() + '&action=mms_save_teacher&nonce=' + nonce;

        $.post(ajaxurl, formData, function (res) {
            $btn.prop('disabled', false).text('সংরক্ষণ করুন');
            if (res.success) {
                alert(res.data.message);
                location.reload();
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('সংরক্ষণ করুন');
            alert(mmsData.strings.error);
        });
    });

    $('.mms-delete-teacher').on('click', function () {
        if (!confirm(mmsData.strings.confirm_delete)) return;
        var id = $(this).data('id');

        $.post(ajaxurl, {
            action: 'mms_delete_teacher',
            nonce: nonce,
            id: id
        }, function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        });
    });

    // Teacher Attendance
    $('#mms-teacher-mark-all-present').on('click', function () {
        $('#mms-teacher-attendance-form').find('input[type="radio"][value="present"]').prop('checked', true);
    });

    $('#mms-teacher-attendance-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#mms-save-teacher-att-btn');
        $btn.prop('disabled', true).text('সংরক্ষণ হচ্ছে...');

        var date = $('#mms-teacher-att-date').val();
        var formData = $(this).serialize() + '&action=mms_save_teacher_attendance&date=' + encodeURIComponent(date) + '&nonce=' + nonce;

        $.post(ajaxurl, formData, function (res) {
            $btn.prop('disabled', false).text('💾 শিক্ষকদের হাজিরা সংরক্ষণ করুন');
            if (res.success) {
                alert(res.data.message);
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('💾 শিক্ষকদের হাজিরা সংরক্ষণ করুন');
            alert(mmsData.strings.error);
        });
    });

    /* =========================================================================
     * 5. TEACHER SALARIES
     * ========================================================================= */
    $('#mms-btn-pay-salary').on('click', function () {
        $('#mms-salary-form')[0].reset();
        $('#salary_payment_date').val(new Date().toISOString().split('T')[0]);
        $('#mms-salary-modal').fadeIn(200);
    });

    $('#salary_teacher_id').on('change', function () {
        var sal = $(this).find('option:selected').data('salary');
        if (sal && parseFloat(sal) > 0) {
            $('#salary_basic').val(sal);
            calculateNetSalary();
        }
    });

    $('#salary_basic, #salary_bonus, #salary_deduction').on('input', function () {
        calculateNetSalary();
    });

    function calculateNetSalary() {
        var basic = parseFloat($('#salary_basic').val()) || 0;
        var bonus = parseFloat($('#salary_bonus').val()) || 0;
        var deduction = parseFloat($('#salary_deduction').val()) || 0;
        var net = basic + bonus - deduction;
        $('#salary_net_paid').val(net.toFixed(2));
    }

    $('#mms-salary-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#mms-save-salary-btn');
        $btn.prop('disabled', true).text('বেতন এন্ট্রি হচ্ছে...');

        var formData = $(this).serialize() + '&action=mms_save_teacher_salary&nonce=' + nonce;

        $.post(ajaxurl, formData, function (res) {
            $btn.prop('disabled', false).text('💾 বেতন পরিশোধ করুন ও স্লিপ দেখুন');
            if (res.success) {
                $('#mms-salary-modal').hide();
                loadSalarySlip(res.data.salary_id);
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('💾 বেতন পরিশোধ করুন ও স্লিপ দেখুন');
            alert(mmsData.strings.error);
        });
    });

    $('.mms-view-salary-slip').on('click', function () {
        var id = $(this).data('id');
        loadSalarySlip(id);
    });

    function loadSalarySlip(salaryId) {
        $('#mms-slip-content').html('<p style="text-align:center;padding:20px;">স্লিপ লোড হচ্ছে...</p>');
        $('#mms-slip-modal').fadeIn(200);

        $.post(ajaxurl, {
            action: 'mms_get_salary_slip',
            nonce: nonce,
            salary_id: salaryId
        }, function (res) {
            if (res.success) {
                $('#mms-slip-content').html(res.data.html);
            } else {
                $('#mms-slip-content').html('<p style="color:red;text-align:center;">স্লিপ লোড ব্যর্থ হয়েছে।</p>');
            }
        });
    }

    $('.mms-delete-salary').on('click', function () {
        if (!confirm(mmsData.strings.confirm_delete)) return;
        var id = $(this).data('id');

        $.post(ajaxurl, {
            action: 'mms_delete_teacher_salary',
            nonce: nonce,
            id: id
        }, function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        });
    });

    /* =========================================================================
     * 6. EXPENSES
     * ========================================================================= */
    $('#mms-btn-add-expense').on('click', function () {
        $('#mms-expense-form')[0].reset();
        $('#expense_date').val(new Date().toISOString().split('T')[0]);
        $('#mms-expense-modal').fadeIn(200);
    });

    $('#mms-expense-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#mms-save-expense-btn');
        $btn.prop('disabled', true).text('সংরক্ষণ হচ্ছে...');

        var formData = $(this).serialize() + '&action=mms_save_expense&nonce=' + nonce;

        $.post(ajaxurl, formData, function (res) {
            $btn.prop('disabled', false).text('💾 খরচ সংরক্ষণ করুন');
            if (res.success) {
                alert(res.data.message);
                location.reload();
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('💾 খরচ সংরক্ষণ করুন');
            alert(mmsData.strings.error);
        });
    });

    $('.mms-delete-expense').on('click', function () {
        if (!confirm(mmsData.strings.confirm_delete)) return;
        var id = $(this).data('id');

        $.post(ajaxurl, {
            action: 'mms_delete_expense',
            nonce: nonce,
            id: id
        }, function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        });
    });

    /* =========================================================================
     * 7. SETTINGS
     * ========================================================================= */
    $('#mms-settings-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#mms-save-settings-btn');
        $btn.prop('disabled', true).text('সংরক্ষণ হচ্ছে...');

        var formData = $(this).serialize() + '&action=mms_save_settings&nonce=' + nonce;

        $.post(ajaxurl, formData, function (res) {
            $btn.prop('disabled', false).text('💾 সেটিংস সংরক্ষণ করুন');
            if (res.success) {
                alert(res.data.message);
                location.reload();
            } else {
                alert(res.data.message || mmsData.strings.error);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('💾 সেটিংস সংরক্ষণ করুন');
            alert(mmsData.strings.error);
        });
    });

});
