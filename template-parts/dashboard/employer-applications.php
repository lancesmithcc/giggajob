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
                        $current_application = get_post(get_the_ID());
                        
                        // Debug information for administrators
                        if (current_user_can('administrator')) {
                            echo '<!-- Debug: Application ID: ' . get_the_ID() . 
                                 ', Raw Applicant ID: ' . var_export($applicant_id, true) . 
                                 ', Post Author: ' . $current_application->post_author . 
                                 ', Post Type: ' . get_post_type(get_the_ID()) . ' -->';
                        }
                        
                        // Try getting user by ID first
                        $applicant = false;
                        if ($applicant_id) {
                            $applicant = get_user_by('ID', $applicant_id);
                        }
                        
                        // If not found by ID, try getting by email
                        if (!$applicant) {
                            $applicant_email = get_post_meta(get_the_ID(), 'applicant_email', true);
                            if ($applicant_email) {
                                $applicant = get_user_by('email', $applicant_email);
                                // Update applicant_id if found by email
                                if ($applicant) {
                                    update_post_meta(get_the_ID(), 'applicant_id', $applicant->ID);
                                }
                            }
                        }
                        
                        // If still not found, try getting from post author
                        if (!$applicant) {
                            $applicant = get_user_by('ID', $current_application->post_author);
                            // Update applicant_id if found from author
                            if ($applicant) {
                                update_post_meta(get_the_ID(), 'applicant_id', $applicant->ID);
                            }
                        }
                        
                        // Additional debug info
                        if (current_user_can('administrator')) {
                            echo '<!-- Debug: Applicant Found: ' . ($applicant ? 'Yes' : 'No') . 
                                 ', Post Author: ' . $current_application->post_author . 
                                 ', Email Meta: ' . get_post_meta(get_the_ID(), 'applicant_email', true) . ' -->';
                        }
                        
                        $status = get_post_meta(get_the_ID(), 'status', true);
                        $resume_id = get_post_meta(get_the_ID(), 'resume_id', true);
                    ?>
                        <tr>
                            <td>
                                <?php if ($applicant && !is_wp_error($applicant)): ?>
                                    <strong class="text-[#ccc]"><?php echo esc_html($applicant->display_name); ?></strong>
                                    <div class="small text-muted"><?php echo esc_html($applicant->user_email); ?></div>
                                <?php else: ?>
                                    <strong class="text-[#ccc]">Applicant Information Unavailable</strong>
                                    <div class="small text-muted">ID: <?php echo esc_html($applicant_id); ?></div>
                                <?php endif; ?>
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
                            <td class="text-[#ccc]"><?php echo get_the_date(); ?></td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($resume_id): ?>
                                        <a href="<?php echo get_permalink($resume_id); ?>" 
                                           class="btn btn-sm btn-outline-light" 
                                           title="View Resume">
                                            <i class="bi bi-file-person"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-info toggle-cover-letter" 
                                            data-application-id="<?php echo get_the_ID(); ?>"
                                            title="View Cover Letter">
                                        <i class="bi bi-envelope"></i>
                                    </button>
                                    <?php if ($status === 'pending'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success toggle-interview-form" 
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
                                        $interview_message = get_post_meta(get_the_ID(), 'interview_message', true);
                                        ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info toggle-interview-details" 
                                                data-application-id="<?php echo get_the_ID(); ?>"
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
                        <!-- Cover Letter Row -->
                        <tr class="cover-letter-row bg-dark d-none" id="cover-letter-<?php echo get_the_ID(); ?>">
                            <td colspan="6">
                                <div class="p-3 border border-secondary rounded">
                                    <h6 class="mb-3 text-light">Cover Letter</h6>
                                    <div class="cover-letter-content text-[#ccc]">
                                        <?php echo nl2br(get_post_field('post_content', get_the_ID())); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php if ($status === 'interview_scheduled'): ?>
                        <tr class="interview-details-row bg-dark d-none" id="interview-details-<?php echo get_the_ID(); ?>">
                            <td colspan="6">
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
                        <?php if ($status === 'pending'): ?>
                        <tr class="interview-form-row bg-dark d-none" id="interview-form-<?php echo get_the_ID(); ?>">
                            <td colspan="6">
                                <form class="interview-schedule-form p-3 border border-secondary rounded">
                                    <input type="hidden" name="application_id" value="<?php echo get_the_ID(); ?>">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label text-light">Interview Date</label>
                                            <input type="date" class="form-control form-control-sm bg-dark text-light border-secondary" 
                                                   name="interview_date" required min="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-light">Interview Time</label>
                                            <input type="time" class="form-control form-control-sm bg-dark text-light border-secondary" 
                                                   name="interview_time" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-light">Location</label>
                                            <input type="text" class="form-control form-control-sm bg-dark text-light border-secondary" 
                                                   name="interview_location" required 
                                                   placeholder="Office address or video call link">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label text-light">Message to Candidate</label>
                                            <textarea class="form-control form-control-sm bg-dark text-light border-secondary" 
                                                      name="interview_message" rows="2" 
                                                      placeholder="Additional information or instructions"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-sm btn-success me-2">
                                                <i class="bi bi-check-lg me-1"></i>Schedule Interview
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary cancel-interview-form">
                                                <i class="bi bi-x-lg me-1"></i>Cancel
                                            </button>
                                        </div>
                                    </div>
                                </form>
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
        <div class="alert alert-dark border-secondary" role="alert">
            <h4 class="alert-heading"><i class="bi bi-info-circle me-2"></i>No Applications Found</h4>
            <p class="mb-0">You haven't received any applications yet.</p>
        </div>
    <?php endif; 
    wp_reset_postdata();
    ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle interview form
    $('.toggle-interview-form').click(function() {
        var applicationId = $(this).data('application-id');
        $('#interview-form-' + applicationId).toggleClass('d-none');
    });

    // Cancel interview form
    $('.cancel-interview-form').click(function() {
        $(this).closest('.interview-form-row').addClass('d-none');
    });

    // Handle interview scheduling
    $('.interview-schedule-form').submit(function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        var formData = new FormData(this);
        formData.append('action', 'handle_interview_schedule');
        formData.append('nonce', giggajob_ajax.nonce);
        
        // Debug output
        console.log('Sending request with nonce:', giggajob_ajax.nonce);
        
        $.ajax({
            url: giggajob_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Scheduling...'
                );
            },
            success: function(response) {
                // Debug output
                console.log('Server response:', response);
                
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Failed to schedule interview. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                // Debug output
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('An error occurred while scheduling the interview. Please try again.');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(
                    '<i class="bi bi-check-lg me-1"></i>Schedule Interview'
                );
            }
        });
    });

    // Handle other actions (reject, cancel interview)
    $('.application-action-btn').click(function() {
        var action = $(this).data('action');
        var applicationId = $(this).data('application-id');
        
        if (!confirm('Are you sure you want to ' + (action === 'reject' ? 'reject this application?' : 'cancel this interview?'))) {
            return;
        }
        
        var $btn = $(this);
        var $row = $btn.closest('tr');
        
        $.ajax({
            url: giggajob_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'handle_application_action',
                application_action: action,
                application_id: applicationId,
                nonce: giggajob_ajax.nonce
            },
            beforeSend: function() {
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                );
            },
            success: function(response) {
                if (response.success) {
                    // Fade out and remove the row
                    $row.fadeOut(400, function() {
                        $(this).remove();
                        // If no more rows, show the "No Applications" message
                        if ($('tbody tr:visible').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert(response.data.message || 'Failed to process the request. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('An error occurred while processing your request. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html(
                    '<i class="bi bi-' + (action === 'reject' ? 'x-lg' : 'calendar-x') + '"></i>'
                );
            }
        });
    });

    // Set minimum time based on selected date
    $('input[name="interview_date"]').change(function() {
        var selectedDate = $(this).val();
        var today = new Date().toISOString().split('T')[0];
        var timeInput = $(this).closest('form').find('input[name="interview_time"]');
        
        if (selectedDate === today) {
            var now = new Date();
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            timeInput.attr('min', hours + ':' + minutes);
        } else {
            timeInput.removeAttr('min');
        }
    });

    // Toggle interview details
    $('.toggle-interview-details').click(function() {
        var applicationId = $(this).data('application-id');
        $('#interview-details-' + applicationId).toggleClass('d-none');
    });

    // Toggle cover letter
    $('.toggle-cover-letter').click(function() {
        var applicationId = $(this).data('application-id');
        $('#cover-letter-' + applicationId).toggleClass('d-none');
    });
});
</script> 