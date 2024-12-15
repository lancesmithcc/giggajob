<?php
/**
 * Template Name: Registration Page
 */

get_header();

// Get the registration type from URL parameter
$registration_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$show_employer = $registration_type === 'employer';
$show_employee = $registration_type === 'employee';

// If no specific type is set, show both options
$show_both = !$show_employer && !$show_employee;
?>

<div class="register-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if ($show_both): ?>
                    <div class="text-center mb-5">
                        <h1 class="h2 mb-3">Create Your Account</h1>
                        <p class="text-muted">Choose your account type to get started</p>
                    </div>

                    <div class="row g-4">
                        <!-- Job Seeker Card -->
                        <div class="col-md-6">
                            <div class="card bg-dark border-secondary h-100">
                                <div class="card-body p-4 text-center">
                                    <i class="bi bi-person-badge display-4 mb-3 text-primary"></i>
                                    <h3 class="h4 mb-3 text-light">Job Seeker</h3>
                                    <p class="text-muted mb-4">Find your next opportunity and connect with top employers</p>
                                    <a href="<?php echo esc_url(add_query_arg('type', 'employee', get_permalink())); ?>" 
                                       class="btn btn-primary w-100">
                                        <i class="bi bi-person-plus me-2"></i>Register as Job Seeker
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Employer Card -->
                        <div class="col-md-6">
                            <div class="card bg-dark border-secondary h-100">
                                <div class="card-body p-4 text-center">
                                    <i class="bi bi-building display-4 mb-3 text-primary"></i>
                                    <h3 class="h4 mb-3 text-light">Employer</h3>
                                    <p class="text-muted mb-4">Post jobs and find the perfect candidates for your company</p>
                                    <a href="<?php echo esc_url(add_query_arg('type', 'employer', get_permalink())); ?>" 
                                       class="btn btn-primary w-100">
                                        <i class="bi bi-building-add me-2"></i>Register as Employer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($show_employer): ?>
                    <?php get_template_part('template-parts/registration/employer-form'); ?>
                <?php elseif ($show_employee): ?>
                    <?php get_template_part('template-parts/registration/employee-form'); ?>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <p class="mb-0">Already have an account? <a href="<?php echo esc_url(wp_login_url()); ?>" class="text-primary">Login here</a></p>
                    <?php if ($show_employer): ?>
                        <p class="mt-2">Looking for a job? <a href="<?php echo esc_url(add_query_arg('type', 'employee', get_permalink())); ?>" class="text-primary">Register as a Job Seeker</a></p>
                    <?php elseif ($show_employee): ?>
                        <p class="mt-2">Want to hire? <a href="<?php echo esc_url(add_query_arg('type', 'employer', get_permalink())); ?>" class="text-primary">Register as an Employer</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?> 