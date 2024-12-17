<?php
/**
 * Template part for displaying jobs
 */

if (!defined('ABSPATH')) exit;

// Get job meta data
$company_name = get_post_meta(get_the_ID(), 'company_name', true);
$job_type = get_post_meta(get_the_ID(), 'job_type', true);
$job_location = get_post_meta(get_the_ID(), 'job_location', true);
$remote_option = get_post_meta(get_the_ID(), 'remote_option', true);
$salary_type = get_post_meta(get_the_ID(), 'salary_type', true);
$salary_min = get_post_meta(get_the_ID(), 'salary_min', true);
$salary_max = get_post_meta(get_the_ID(), 'salary_max', true);
$salary_period = get_post_meta(get_the_ID(), 'salary_period', true);

// Set excerpt length
$excerpt_length = apply_filters('giggajob_job_excerpt_length', 15);

// Job types array
$job_types = array(
    'full-time' => 'Full Time',
    'part-time' => 'Part Time',
    'contract' => 'Contract',
    'temporary' => 'Temporary',
    'internship' => 'Internship'
);

// Check if we're in a dashboard or archive view
$is_dashboard = strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false;
$should_show_excerpt = !is_singular('jobs') || $is_dashboard;

if ($should_show_excerpt): // If we're not on a single job page or we're in dashboard, show the card view ?>
    <div class="card job-card h-100">
        <div class="card-body">
            <h3 class="h5 card-title mb-3">
                <a href="<?php the_permalink(); ?>" class="text-decoration-none">
                    <?php the_title(); ?>
                </a>
            </h3>
            
            <div class="company-info mb-3">
                <span class="company-name text-muted">
                    <i class="bi bi-building me-2"></i><?php echo esc_html($company_name); ?>
                </span>
            </div>

            <div class="job-meta mb-3">
                <?php if ($job_type && isset($job_types[$job_type])): ?>
                    <span class="badge bg-primary me-2"><?php echo esc_html($job_types[$job_type]); ?></span>
                <?php endif; ?>
                
                <?php if ($remote_option === 'yes'): ?>
                    <span class="badge bg-success me-2">Remote</span>
                <?php elseif ($remote_option === 'hybrid'): ?>
                    <span class="badge bg-info me-2">Hybrid</span>
                <?php endif; ?>
                
                <?php
                $job_industries = get_the_terms(get_the_ID(), 'industry');
                if ($job_industries && !is_wp_error($job_industries)) {
                    foreach ($job_industries as $industry) {
                        echo '<span class="badge bg-secondary me-2">' . esc_html($industry->name) . '</span>';
                    }
                }
                ?>
            </div>

            <div class="job-details">
                <?php if ($job_location): ?>
                    <p class="mb-2">
                        <i class="bi bi-geo-alt me-2"></i><?php echo esc_html($job_location); ?>
                    </p>
                <?php endif; ?>

                <?php if ($salary_type !== 'exempt'): ?>
                    <p class="mb-2">
                        <i class="bi bi-currency-dollar me-2"></i>
                        <?php
                        if ($salary_type === 'fixed' && !empty($salary_min)) {
                            echo esc_html(number_format((float)$salary_min) . ' per ' . $salary_period);
                        } elseif (!empty($salary_min) && !empty($salary_max)) {
                            echo esc_html(number_format((float)$salary_min) . ' - ' . number_format((float)$salary_max) . ' per ' . $salary_period);
                        }
                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="job-excerpt mt-3">
                <?php 
                $excerpt = wp_trim_words(get_the_content(), $excerpt_length, '...');
                echo '<p class="mb-0">' . $excerpt . '</p>';
                ?>
            </div>
        </div>
        <div class="card-footer border-top-0">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Posted <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')); ?> ago
                </small>
                <a href="<?php the_permalink(); ?>" class="btn btn-outline-primary btn-sm">View Details</a>
            </div>
        </div>
    </div>
<?php else: // Show full job content for single job pages ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('job-single mb-4'); ?>>
        <?php if (has_post_thumbnail()): ?>
            <div class="featured-image mb-4">
                <?php the_post_thumbnail('large', array('class' => 'img-fluid rounded')); ?>
            </div>
        <?php endif; ?>

        <header class="job-header mb-4">
            <h1 class="job-title"><?php the_title(); ?></h1>
            
            <div class="job-meta mb-3">
                <?php if ($company_name): ?>
                    <p class="company-name mb-2">
                        <i class="bi bi-building me-2"></i><?php echo esc_html($company_name); ?>
                    </p>
                <?php endif; ?>

                <?php if ($job_type && isset($job_types[$job_type])): ?>
                    <span class="badge bg-primary me-2"><?php echo esc_html($job_types[$job_type]); ?></span>
                <?php endif; ?>

                <?php if ($remote_option === 'yes'): ?>
                    <span class="badge bg-success me-2">Remote</span>
                <?php elseif ($remote_option === 'hybrid'): ?>
                    <span class="badge bg-info me-2">Hybrid</span>
                <?php endif; ?>

                <?php
                $job_industries = get_the_terms(get_the_ID(), 'industry');
                if ($job_industries && !is_wp_error($job_industries)) {
                    foreach ($job_industries as $industry) {
                        echo '<span class="badge bg-secondary me-2">' . esc_html($industry->name) . '</span>';
                    }
                }
                ?>
            </div>

            <div class="job-details mb-4">
                <?php if ($job_location): ?>
                    <p class="mb-2">
                        <i class="bi bi-geo-alt me-2"></i><?php echo esc_html($job_location); ?>
                    </p>
                <?php endif; ?>

                <?php if ($salary_type !== 'exempt'): ?>
                    <p class="mb-2">
                        <i class="bi bi-currency-dollar me-2"></i>
                        <?php
                        if ($salary_type === 'fixed' && !empty($salary_min)) {
                            echo esc_html(number_format((float)$salary_min) . ' per ' . $salary_period);
                        } elseif (!empty($salary_min) && !empty($salary_max)) {
                            echo esc_html(number_format((float)$salary_min) . ' - ' . number_format((float)$salary_max) . ' per ' . $salary_period);
                        }
                        ?>
                    </p>
                <?php endif; ?>

                <p class="posted-date text-muted">
                    <i class="bi bi-clock me-2"></i>Posted <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')); ?> ago
                </p>
            </div>
        </header>

        <div class="job-content">
            <?php the_content(); ?>
        </div>

        <?php if (is_user_logged_in()): ?>
            <?php 
            $current_user = wp_get_current_user();
            if (in_array('employee', $current_user->roles)): 
                // Check if user has already applied
                $existing_application = get_posts(array(
                    'post_type' => 'job_application',
                    'author' => get_current_user_id(),
                    'meta_query' => array(
                        array(
                            'key' => 'job_id',
                            'value' => get_the_ID()
                        )
                    ),
                    'posts_per_page' => 1
                ));

                if (!empty($existing_application)): ?>
                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle me-2"></i>You have already applied for this position.
                    </div>
                <?php else: ?>
                    <!-- Application Form -->
                    <div class="job-apply mt-4">
                        <button type="button" class="btn btn-primary" id="showApplicationForm">
                            <i class="bi bi-send me-2"></i>Apply Now
                        </button>
                        
                        <div id="applicationForm" class="application-form mt-4" style="display: none;">
                            <form id="jobApplicationForm" class="needs-validation" novalidate>
                                <?php wp_nonce_field('submit_job_application', 'job_application_nonce'); ?>
                                <input type="hidden" name="job_id" value="<?php echo get_the_ID(); ?>">
                                
                                <?php
                                // Get user's resume
                                $resume = get_posts(array(
                                    'post_type' => 'resume',
                                    'author' => get_current_user_id(),
                                    'posts_per_page' => 1
                                ));
                                ?>

                                <?php if (empty($resume)): ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        You need to create a resume before applying.
                                        <a href="<?php echo home_url('/employee-dashboard/?tab=resume'); ?>" class="btn btn-warning btn-sm ms-3">
                                            Create Resume
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="resume_id" value="<?php echo $resume[0]->ID; ?>">
                                    <div class="alert alert-info mb-3">
                                        <i class="bi bi-file-person me-2"></i>
                                        Your resume "<strong><?php echo esc_html($resume[0]->post_title); ?></strong>" will be attached to this application.
                                        <a href="<?php echo home_url('/employee-dashboard/?tab=resume'); ?>" class="btn btn-outline-info btn-sm ms-3">
                                            View/Edit Resume
                                        </a>
                                    </div>

                                    <div class="mb-3">
                                        <label for="cover_letter" class="form-label">Cover Letter *</label>
                                        <textarea class="form-control" id="cover_letter" name="cover_letter" 
                                                rows="6" required 
                                                placeholder="Introduce yourself and explain why you're a great fit for this position..."></textarea>
                                        <div class="invalid-feedback">Please provide a cover letter.</div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-send me-2"></i>Submit Application
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="cancelApplication">
                                            <i class="bi bi-x-circle me-2"></i>Cancel
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <script>
                    jQuery(document).ready(function($) {
                        // Show/Hide application form
                        $('#showApplicationForm').click(function() {
                            $(this).hide();
                            $('#applicationForm').slideDown();
                        });

                        $('#cancelApplication').click(function() {
                            $('#applicationForm').slideUp(function() {
                                $('#showApplicationForm').show();
                            });
                        });

                        // Handle form submission
                        $('#jobApplicationForm').submit(function(e) {
                            e.preventDefault();
                            
                            if (!this.checkValidity()) {
                                e.stopPropagation();
                                $(this).addClass('was-validated');
                                return;
                            }

                            var $submitBtn = $(this).find('button[type="submit"]');
                            var $form = $(this);

                            $.ajax({
                                url: giggajob_ajax.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'submit_job_application',
                                    job_application_nonce: $('#job_application_nonce').val(),
                                    job_id: $('input[name="job_id"]').val(),
                                    resume_id: $('input[name="resume_id"]').val(),
                                    cover_letter: $('#cover_letter').val()
                                },
                                beforeSend: function() {
                                    $submitBtn.prop('disabled', true)
                                            .html('<span class="spinner-border spinner-border-sm me-2"></span>Submitting...');
                                },
                                success: function(response) {
                                    if (response.success) {
                                        // Replace form with success message
                                        $('#applicationForm').html(
                                            '<div class="alert alert-success">' +
                                            '<i class="bi bi-check-circle me-2"></i>' +
                                            'Your application has been submitted successfully!' +
                                            '</div>'
                                        );
                                        // Reload page after 2 seconds
                                        setTimeout(function() {
                                            location.reload();
                                        }, 2000);
                                    } else {
                                        alert(response.data.message || 'An error occurred while submitting your application.');
                                    }
                                },
                                error: function() {
                                    alert('An error occurred while submitting your application.');
                                },
                                complete: function() {
                                    $submitBtn.prop('disabled', false)
                                            .html('<i class="bi bi-send me-2"></i>Submit Application');
                                }
                            });
                        });
                    });
                    </script>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="job-apply mt-4">
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login to Apply
                </a>
            </div>
        <?php endif; ?>
    </article>
<?php endif; ?> 