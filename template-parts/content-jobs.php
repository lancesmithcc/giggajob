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
            ?>
                <div class="job-apply mt-4">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyJobModal">
                        Apply Now
                    </button>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="job-apply mt-4">
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-primary">
                    Login to Apply
                </a>
            </div>
        <?php endif; ?>
    </article>
<?php endif; ?> 