<?php
/**
 * Template part for displaying employee job applications
 */

// Security check
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Get applications
$args = array(
    'post_type' => 'job_application',
    'posts_per_page' => 10,
    'paged' => $paged,
    'meta_query' => array(
        array(
            'key' => 'applicant_id',
            'value' => $current_user->ID
        )
    ),
    'orderby' => 'date',
    'order' => 'DESC'
);

$applications_query = new WP_Query($args);
?>

<div class="applications-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">My Applications</h2>
    </div>

    <?php if ($applications_query->have_posts()) : ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Applied Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($applications_query->have_posts()) : $applications_query->the_post(); 
                        $job_id = get_post_meta(get_the_ID(), 'job_id', true);
                        $job = get_post($job_id);
                        $company_name = get_post_meta($job_id, 'company_name', true);
                        $status = get_post_meta(get_the_ID(), 'status', true);
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo get_the_title($job_id); ?></strong>
                                <div class="small text-muted">
                                    <?php echo get_post_meta($job_id, 'job_location', true); ?>
                                </div>
                            </td>
                            <td><?php echo esc_html($company_name); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $status === 'pending' ? 'warning' : 
                                        ($status === 'accepted' ? 'success' : 
                                        ($status === 'rejected' ? 'danger' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td><?php echo get_the_date(); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo get_permalink($job_id); ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="View Job">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($status === 'pending' || $status === 'interview_scheduled'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger withdraw-application-btn" 
                                                data-application-id="<?php echo get_the_ID(); ?>"
                                                title="Withdraw Application">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($status === 'interview_scheduled'): 
                                        $interview_date = get_post_meta(get_the_ID(), 'interview_date', true);
                                        $interview_time = get_post_meta(get_the_ID(), 'interview_time', true);
                                        $interview_location = get_post_meta(get_the_ID(), 'interview_location', true);
                                        $interview_message = get_post_meta(get_the_ID(), 'interview_message', true);
                                    ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info toggle-interview-details" 
                                                data-application-id="<?php echo get_the_ID(); ?>"
                                                title="View Interview Details">
                                            <i class="bi bi-calendar-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php if ($status === 'interview_scheduled'): ?>
                        <tr class="interview-details-row bg-dark d-none" id="interview-details-<?php echo get_the_ID(); ?>">
                            <td colspan="5">
                                <div class="p-3 border border-secondary rounded">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <i class="bi bi-calendar text-light me-2"></i>
                                                <span class="text-light">Date:</span>
                                                <span class="text-[#ccc]"><?php echo date('M j, Y', strtotime($interview_date)); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <i class="bi bi-clock text-light me-2"></i>
                                                <span class="text-light">Time:</span>
                                                <span class="text-[#ccc]"><?php echo date('g:i A', strtotime($interview_time)); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <i class="bi bi-geo-alt text-light me-2"></i>
                                                <span class="text-light">Location:</span>
                                                <span class="text-[#ccc]"><?php echo esc_html($interview_location); ?></span>
                                            </div>
                                        </div>
                                        <?php if (!empty($interview_message)): ?>
                                        <div class="col-12 mt-2">
                                            <div class="mb-2">
                                                <i class="bi bi-chat-text text-light me-2"></i>
                                                <span class="text-light">Message:</span>
                                                <div class="text-[#ccc] mt-1"><?php echo nl2br(esc_html($interview_message)); ?></div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
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
            'total' => $applications_query->max_num_pages,
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
            <h4 class="alert-heading"><i class="bi bi-info-circle me-2"></i>No Applications Found</h4>
            <p class="mb-0">You haven't applied to any jobs yet.</p>
        </div>
    <?php endif; 
    wp_reset_postdata();
    ?>
</div> 

<script>
jQuery(document).ready(function($) {
    // Create nonce for application actions
    var applicationNonce = '<?php echo wp_create_nonce('application_action_nonce'); ?>';

    // Toggle interview details
    $('.toggle-interview-details').click(function() {
        var applicationId = $(this).data('application-id');
        $('#interview-details-' + applicationId).toggleClass('d-none');
    });

    // Handle withdraw application
    $('.withdraw-application-btn').click(function() {
        var applicationId = $(this).data('application-id');
        var $btn = $(this);
        var $row = $btn.closest('tr');

        if (!confirm('Are you sure you want to withdraw this application? This action cannot be undone.')) {
            return;
        }

        $.ajax({
            url: giggajob_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'handle_application_withdrawal',
                application_id: applicationId,
                nonce: applicationNonce
            },
            beforeSend: function() {
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                );
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Failed to withdraw application. Please try again.');
                }
            },
            error: function() {
                alert('An error occurred while processing your request. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bi bi-x-circle"></i>');
            }
        });
    });
});
</script> 