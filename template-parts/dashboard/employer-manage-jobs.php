<?php
/**
 * Template part for displaying job management interface
 */

// Security check
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Get jobs
$args = array(
    'post_type' => 'jobs',
    'author' => $current_user->ID,
    'posts_per_page' => 10,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC'
);

// Add filters if set
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $args['meta_query'][] = array(
        'key' => 'job_status',
        'value' => sanitize_text_field($_GET['status'])
    );
}

$jobs_query = new WP_Query($args);
?>

<div class="manage-jobs">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">Manage Jobs</h2>
        <a href="<?php echo add_query_arg('tab', 'post-job'); ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Post New Job
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="job-filters" method="get">
                <input type="hidden" name="tab" value="manage-jobs">
                <div class="row g-3">
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'active'); ?>>Active</option>
                            <option value="expired" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'expired'); ?>>Expired</option>
                            <option value="draft" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'draft'); ?>>Draft</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-secondary">
                            <i class="bi bi-filter me-2"></i>Apply Filters
                        </button>
                        <a href="<?php echo add_query_arg('tab', 'manage-jobs', remove_query_arg(array('status', 'paged'))); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($jobs_query->have_posts()) : ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Applications</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($jobs_query->have_posts()) : $jobs_query->the_post(); 
                        $job_status = get_post_meta(get_the_ID(), 'job_status', true);
                        $applications = get_posts(array(
                            'post_type' => 'job_application',
                            'meta_query' => array(
                                array(
                                    'key' => 'job_id',
                                    'value' => get_the_ID()
                                )
                            ),
                            'posts_per_page' => -1
                        ));
                        $application_count = count($applications);
                        $expiry_date = get_post_meta(get_the_ID(), 'job_expiry_date', true);
                    ?>
                        <tr>
                            <td>
                                <strong><?php the_title(); ?></strong>
                                <div class="small text-muted">
                                    <?php 
                                    // Show job type and location
                                    $job_type = get_post_meta(get_the_ID(), 'job_type', true);
                                    $job_location = get_post_meta(get_the_ID(), 'job_location', true);
                                    $remote_option = get_post_meta(get_the_ID(), 'remote_option', true);
                                    
                                    if ($job_type) echo '<i class="bi bi-briefcase me-1"></i>' . esc_html($job_type);
                                    if ($job_location) echo ' • <i class="bi bi-geo-alt me-1"></i>' . esc_html($job_location);
                                    if ($remote_option === 'yes') echo ' • <i class="bi bi-laptop me-1"></i>Remote';
                                    else if ($remote_option === 'hybrid') echo ' • <i class="bi bi-laptop me-1"></i>Hybrid';
                                    ?>
                                </div>
                                <div class="small text-muted mt-1">
                                    <?php 
                                    // Show salary information
                                    $salary = get_post_meta(get_the_ID(), 'salary', true);
                                    $salary_period = get_post_meta(get_the_ID(), 'salary_period', true);
                                    if ($salary && $salary !== 'legal exemption for non-disclosure') {
                                        echo '<i class="bi bi-currency-dollar me-1"></i>' . esc_html($salary);
                                        if ($salary_period) echo ' per ' . esc_html($salary_period);
                                    }
                                    ?>
                                </div>
                            </td>
                            <td>
                                <a href="<?php echo add_query_arg(array('tab' => 'applications', 'job_id' => get_the_ID())); ?>" class="text-decoration-none">
                                    <?php echo $application_count; ?> 
                                    <?php echo _n('application', 'applications', $application_count, 'giggajob'); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $job_status === 'active' ? 'success' : 
                                        ($job_status === 'expired' ? 'danger' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($job_status); ?>
                                </span>
                            </td>
                            <td><?php echo get_the_date(); ?></td>
                            <td>
                                <?php 
                                if ($expiry_date) {
                                    echo date_i18n(get_option('date_format'), strtotime($expiry_date));
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo add_query_arg(array('tab' => 'post-job', 'job_id' => get_the_ID())); ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($job_status === 'active'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-warning job-action-btn" 
                                                data-action="pause" 
                                                data-job-id="<?php echo get_the_ID(); ?>"
                                                title="Pause">
                                            <i class="bi bi-pause-fill"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success job-action-btn" 
                                                data-action="activate" 
                                                data-job-id="<?php echo get_the_ID(); ?>"
                                                title="Activate">
                                            <i class="bi bi-play-fill"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger job-action-btn" 
                                            data-action="delete" 
                                            data-job-id="<?php echo get_the_ID(); ?>"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php
        // Pagination
        $big = 999999999;
        echo '<div class="pagination justify-content-center">';
        echo paginate_links(array(
            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format' => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total' => $jobs_query->max_num_pages,
            'prev_text' => '<i class="bi bi-chevron-left"></i>',
            'next_text' => '<i class="bi bi-chevron-right"></i>',
            'type' => 'list',
            'end_size' => 3,
            'mid_size' => 2
        ));
        echo '</div>';
        ?>

    <?php else: ?>
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading"><i class="bi bi-info-circle me-2"></i>No Jobs Found</h4>
            <p class="mb-0">You haven't posted any jobs yet. Get started by posting your first job listing!</p>
            <hr>
            <a href="<?php echo add_query_arg('tab', 'post-job'); ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Post Your First Job
            </a>
        </div>
    <?php endif; 
    wp_reset_postdata();
    ?>
</div>

<!-- Job Action Confirmation Modal -->
<div class="modal fade" id="jobActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <span id="actionText"></span> this job?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmJobAction">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var actionModal = new bootstrap.Modal(document.getElementById('jobActionModal'));
    var currentJobAction = null;
    var currentJobId = null;

    $('.job-action-btn').click(function() {
        currentJobAction = $(this).data('action');
        currentJobId = $(this).data('job-id');
        
        var actionText = currentJobAction === 'delete' ? 'delete' : 
                        (currentJobAction === 'pause' ? 'pause' : 'activate');
        $('#actionText').text(actionText);
        
        actionModal.show();
    });

    $('#confirmJobAction').click(function() {
        $.ajax({
            url: giggajob_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'job_action',
                job_action: currentJobAction,
                job_id: currentJobId,
                nonce: giggajob_ajax.nonce
            },
            beforeSend: function() {
                $(this).prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...'
                );
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                actionModal.hide();
                $(this).prop('disabled', false).text('Confirm');
            }
        });
    });
});
</script> 