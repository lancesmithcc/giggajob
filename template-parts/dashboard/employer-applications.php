<?php
/**
 * Template part for displaying employer job applications
 */

// Security check
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Get specific job if filtered
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

// First, get all jobs for this employer
$jobs = get_posts(array(
    'post_type' => 'jobs',
    'author' => $current_user->ID,
    'posts_per_page' => -1
));

// Get job IDs
$job_ids = array_map(function($job) { 
    return $job->ID; 
}, $jobs);

// Get applications
$args = array(
    'post_type' => 'job_application',
    'posts_per_page' => 10,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC'
);

// Only add meta query if we have jobs or a specific job ID
if (!empty($job_ids) || $job_id) {
    $args['meta_query'] = array(
        'relation' => 'AND'
    );
    
    if (!empty($job_ids)) {
        $args['meta_query'][] = array(
            'key' => 'job_id',
            'value' => $job_ids,
            'compare' => 'IN'
        );
    }
    
    if ($job_id) {
        $args['meta_query'][] = array(
            'key' => 'job_id',
            'value' => $job_id,
            'compare' => '='
        );
    }
}

$applications_query = new WP_Query($args);

// Debug information
if (current_user_can('administrator')) {
    echo '<!-- Debug Info: ';
    echo 'Job IDs: ' . implode(', ', $job_ids) . ' | ';
    echo 'Query: ' . print_r($args, true) . ' | ';
    echo 'Found Posts: ' . $applications_query->found_posts;
    echo ' -->';
}
?>

<div class="applications-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">
            <?php 
            if ($job_id) {
                echo 'Applications for: ' . get_the_title($job_id);
            } else {
                echo 'All Applications';
            }
            ?>
        </h2>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="application-filters" method="get">
                <input type="hidden" name="tab" value="applications">
                <div class="row g-3">
                    <div class="col-md-4">
                        <select name="job_id" class="form-select">
                            <option value="">All Jobs</option>
                            <?php foreach ($jobs as $job): ?>
                                <option value="<?php echo $job->ID; ?>" <?php selected($job_id, $job->ID); ?>>
                                    <?php echo get_the_title($job); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-secondary">
                            <i class="bi bi-filter me-2"></i>Apply Filters
                        </button>
                        <a href="<?php echo add_query_arg('tab', 'applications', remove_query_arg('job_id')); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($applications_query->have_posts()) : ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Job Title</th>
                        <th>Status</th>
                        <th>Applied Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($applications_query->have_posts()) : $applications_query->the_post(); 
                        $job_id = get_post_meta(get_the_ID(), 'job_id', true);
                        $job = get_post($job_id);
                        $applicant_id = get_post_meta(get_the_ID(), 'applicant_id', true);
                        $applicant = get_userdata($applicant_id);
                        $status = get_post_meta(get_the_ID(), 'status', true);
                        $resume_id = get_post_meta(get_the_ID(), 'resume_id', true);
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($applicant->display_name); ?></strong>
                                <div class="small text-muted"><?php echo esc_html($applicant->user_email); ?></div>
                            </td>
                            <td>
                                <strong><?php echo get_the_title($job_id); ?></strong>
                                <div class="small text-muted">
                                    <?php echo get_post_meta($job_id, 'job_location', true); ?>
                                </div>
                            </td>
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
                                    <?php if ($resume_id): ?>
                                        <a href="<?php echo get_permalink($resume_id); ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="View Resume">
                                            <i class="bi bi-file-person"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($status === 'pending'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success application-action-btn" 
                                                data-action="schedule_interview" 
                                                data-application-id="<?php echo get_the_ID(); ?>"
                                                title="Schedule Interview">
                                            <i class="bi bi-calendar-check"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger application-action-btn" 
                                                data-action="reject" 
                                                data-application-id="<?php echo get_the_ID(); ?>"
                                                title="Reject">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    <?php elseif ($status === 'interview_scheduled'): ?>
                                        <?php 
                                        $interview_date = get_post_meta(get_the_ID(), 'interview_date', true);
                                        $interview_time = get_post_meta(get_the_ID(), 'interview_time', true);
                                        $interview_location = get_post_meta(get_the_ID(), 'interview_location', true);
                                        ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info application-action-btn" 
                                                data-action="view_interview" 
                                                data-application-id="<?php echo get_the_ID(); ?>"
                                                data-interview-date="<?php echo esc_attr($interview_date); ?>"
                                                data-interview-time="<?php echo esc_attr($interview_time); ?>"
                                                data-interview-location="<?php echo esc_attr($interview_location); ?>"
                                                title="View Interview Details">
                                            <i class="bi bi-calendar-check"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-warning application-action-btn" 
                                                data-action="cancel_interview" 
                                                data-application-id="<?php echo get_the_ID(); ?>"
                                                title="Cancel Interview">
                                            <i class="bi bi-calendar-x"></i>
                                        </button>
                                    <?php endif; ?>
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
            <p class="mb-0">You haven't received any applications yet.</p>
        </div>
    <?php endif; 
    wp_reset_postdata();
    ?>
</div>

<!-- Interview Scheduling Modal -->
<div class="modal fade" id="interviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Interview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="interviewForm">
                    <input type="hidden" id="application_id" name="application_id">
                    <div class="mb-3">
                        <label for="interview_date" class="form-label">Interview Date *</label>
                        <input type="date" class="form-control" id="interview_date" name="interview_date" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="interview_time" class="form-label">Interview Time *</label>
                        <input type="time" class="form-control" id="interview_time" name="interview_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="interview_location" class="form-label">Interview Location *</label>
                        <input type="text" class="form-control" id="interview_location" name="interview_location" required>
                        <div class="form-text">Enter physical location or video call link</div>
                    </div>
                    <div class="mb-3">
                        <label for="interview_message" class="form-label">Message to Candidate</label>
                        <textarea class="form-control" id="interview_message" name="interview_message" rows="3"></textarea>
                        <div class="form-text">Additional information or instructions for the candidate</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="scheduleInterview">Schedule Interview</button>
            </div>
        </div>
    </div>
</div>

<!-- View Interview Modal -->
<div class="modal fade" id="viewInterviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Interview Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">Date:</dt>
                    <dd class="col-sm-8" id="view_interview_date"></dd>
                    
                    <dt class="col-sm-4">Time:</dt>
                    <dd class="col-sm-8" id="view_interview_time"></dd>
                    
                    <dt class="col-sm-4">Location:</dt>
                    <dd class="col-sm-8" id="view_interview_location"></dd>
                    
                    <dt class="col-sm-4">Message:</dt>
                    <dd class="col-sm-8" id="view_interview_message"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <span id="actionText"></span>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var interviewModal = new bootstrap.Modal(document.getElementById('interviewModal'));
    var viewInterviewModal = new bootstrap.Modal(document.getElementById('viewInterviewModal'));
    var confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    var currentAction = null;
    var currentApplicationId = null;

    $('.application-action-btn').click(function() {
        currentAction = $(this).data('action');
        currentApplicationId = $(this).data('application-id');
        
        switch(currentAction) {
            case 'schedule_interview':
                $('#application_id').val(currentApplicationId);
                interviewModal.show();
                break;
                
            case 'view_interview':
                var date = $(this).data('interview-date');
                var time = $(this).data('interview-time');
                var location = $(this).data('interview-location');
                var message = $(this).data('interview-message');
                
                $('#view_interview_date').text(date);
                $('#view_interview_time').text(time);
                $('#view_interview_location').text(location);
                $('#view_interview_message').text(message || 'No additional message');
                
                viewInterviewModal.show();
                break;
                
            case 'reject':
                $('#actionText').text('reject this application');
                confirmationModal.show();
                break;
                
            case 'cancel_interview':
                $('#actionText').text('cancel this interview');
                confirmationModal.show();
                break;
        }
    });

    $('#scheduleInterview').click(function() {
        var formData = new FormData($('#interviewForm')[0]);
        formData.append('action', 'schedule_interview');
        formData.append('nonce', giggajob_ajax.nonce);
        
        $.ajax({
            url: giggajob_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $(this).prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Scheduling...'
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
                interviewModal.hide();
                $(this).prop('disabled', false).text('Schedule Interview');
            }
        });
    });

    $('#confirmAction').click(function() {
        $.ajax({
            url: giggajob_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'application_action',
                application_action: currentAction,
                application_id: currentApplicationId,
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
                confirmationModal.hide();
                $(this).prop('disabled', false).text('Confirm');
            }
        });
    });

    // Set minimum time based on selected date
    $('#interview_date').change(function() {
        var selectedDate = $(this).val();
        var today = new Date().toISOString().split('T')[0];
        
        if (selectedDate === today) {
            var now = new Date();
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            $('#interview_time').attr('min', hours + ':' + minutes);
        } else {
            $('#interview_time').removeAttr('min');
        }
    });
});
</script> 