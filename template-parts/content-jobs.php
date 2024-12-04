<?php
/**
 * Template part for displaying jobs
 */

if (!defined('ABSPATH')) exit;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('job-listing mb-4 p-4 border rounded'); ?>>
    <header class="job-header mb-3">
        <h2 class="job-title h4">
            <?php if (!is_singular()): ?>
                <a href="<?php the_permalink(); ?>" class="text-decoration-none">
                    <?php the_title(); ?>
                </a>
            <?php else: ?>
                <?php the_title(); ?>
            <?php endif; ?>
        </h2>
        
        <div class="job-meta text-muted">
            <?php
            // Get custom fields
            $job_type = get_post_meta(get_the_ID(), 'job_type', true);
            $job_location = get_post_meta(get_the_ID(), 'job_location', true);
            $job_salary = get_post_meta(get_the_ID(), 'job_salary', true);
            $remote_options = get_post_meta(get_the_ID(), 'remote_options', true);
            $company_name = get_post_meta(get_the_ID(), 'company_name', true);
            ?>
            
            <?php if ($company_name): ?>
                <span class="company-name me-3">
                    <i class="bi bi-building"></i> <?php echo esc_html($company_name); ?>
                </span>
            <?php endif; ?>

            <?php if ($job_type): ?>
                <span class="job-type me-3">
                    <i class="bi bi-briefcase"></i> <?php echo esc_html($job_type); ?>
                </span>
            <?php endif; ?>

            <?php if ($job_location): ?>
                <span class="job-location me-3">
                    <i class="bi bi-geo-alt"></i> <?php echo esc_html($job_location); ?>
                </span>
            <?php endif; ?>

            <?php if ($remote_options): ?>
                <span class="remote-options me-3">
                    <i class="bi bi-laptop"></i> <?php echo esc_html($remote_options); ?>
                </span>
            <?php endif; ?>

            <?php if ($job_salary && $job_salary !== 'legal exemption for non-disclosure'): ?>
                <span class="salary">
                    <i class="bi bi-cash"></i> <?php echo esc_html($job_salary); ?>
                </span>
            <?php endif; ?>
        </div>
    </header>

    <?php if (is_singular()): ?>
        <div class="job-content">
            <?php the_content(); ?>
            
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
        </div>
    <?php else: ?>
        <div class="job-excerpt">
            <?php the_excerpt(); ?>
            <a href="<?php the_permalink(); ?>" class="btn btn-outline-primary btn-sm">View Details</a>
        </div>
    <?php endif; ?>
</article>

<?php if (is_singular() && is_user_logged_in() && in_array('employee', wp_get_current_user()->roles)): ?>
<!-- Job Application Modal -->
<div class="modal fade" id="applyJobModal" tabindex="-1" aria-labelledby="applyJobModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applyJobModalLabel">Apply for <?php the_title(); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="job-application-form" method="post">
                    <input type="hidden" name="job_id" value="<?php echo get_the_ID(); ?>">
                    <input type="hidden" name="action" value="submit_job_application">
                    <?php wp_nonce_field('submit_job_application', 'job_application_nonce'); ?>
                    
                    <div class="mb-3">
                        <label for="cover_letter" class="form-label">Cover Letter</label>
                        <textarea class="form-control" id="cover_letter" name="cover_letter" rows="5" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?> 