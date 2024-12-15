<?php
/**
 * Template part for employee registration form
 */
?>
<div class="text-center mb-5">
    <h1 class="h2 mb-3">Create Job Seeker Account</h1>
    <p class="text-muted">Find your next opportunity</p>
</div>

<div class="card bg-dark border-secondary">
    <div class="card-body p-4">
        <form id="employee-registration-form" class="needs-validation" novalidate>
            <?php wp_nonce_field('employee_registration', 'employee_registration_nonce'); ?>
            <input type="hidden" name="role" value="employee">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first-name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first-name" 
                           name="first_name" required>
                    <div class="invalid-feedback">Please enter your first name.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="last-name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last-name" 
                           name="last_name" required>
                    <div class="invalid-feedback">Please enter your last name.</div>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" 
                       name="email" required>
                <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" 
                       name="username" required>
                <div class="invalid-feedback">Please choose a username.</div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" 
                       name="password" required 
                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                <div class="invalid-feedback">
                    Password must be at least 8 characters long and include uppercase, 
                    lowercase, and numbers.
                </div>
            </div>

            <div class="mb-3">
                <label for="confirm-password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm-password" 
                       name="confirm_password" required>
                <div class="invalid-feedback">Passwords do not match.</div>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="terms" required>
                    <label class="form-check-label" for="terms">
                        I agree to the <a href="#" class="text-primary">Terms of Service</a> and 
                        <a href="#" class="text-primary">Privacy Policy</a>
                    </label>
                    <div class="invalid-feedback">
                        You must agree to the terms and conditions.
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-person-plus me-2"></i>Create Job Seeker Account
            </button>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Form validation
    function validateForm(formId) {
        const form = document.getElementById(formId);
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
        return form.checkValidity();
    }

    // Password confirmation validation
    function validatePassword() {
        const password = $('#password').val();
        const confirmPassword = $('#confirm-password').val();
        const confirmInput = $('#confirm-password')[0];

        if (password !== confirmPassword) {
            confirmInput.setCustomValidity('Passwords do not match');
            return false;
        } else {
            confirmInput.setCustomValidity('');
            return true;
        }
    }

    // Handle registration
    $('#employee-registration-form').submit(function(e) {
        e.preventDefault();
        
        if (validateForm('employee-registration-form') && validatePassword()) {
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');

            $.ajax({
                url: giggajob_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'register_user',
                    nonce: $('#employee_registration_nonce').val(),
                    form_data: $form.serialize()
                },
                beforeSend: function() {
                    $submitBtn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-2"></span>Creating Account...'
                    );
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert(response.data.message || 'Registration failed. Please try again.');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(
                        '<i class="bi bi-person-plus me-2"></i>Create Job Seeker Account'
                    );
                }
            });
        }
    });

    // Real-time password confirmation validation
    $('#confirm-password, #password').on('input', function() {
        validatePassword();
    });
});
</script> 