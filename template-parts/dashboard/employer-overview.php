<?php
/**
 * Template part for displaying employer dashboard overview
 */

$current_user = wp_get_current_user();

// Get employer's jobs
$jobs = get_posts(array(
    'post_type' => 'jobs',
    'author' => $current_user->ID,
    'posts_per_page' => -1
));

// Get recent applications
$recent_applications = get_posts(array(
    'post_type' => 'job_application',
    'meta_query' => array(
        array(
            'key' => 'employer_id',
            'value' => $current_user->ID
        )
    ),
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
));

// Count statistics
$total_jobs = count($jobs);
$active_jobs = 0;
$total_applications = 0;
$new_applications = 0;

foreach ($jobs as $job) {
    if (get_post_meta($job->ID, 'job_status', true) === 'active') {
        $active_jobs++;
    }
    $applications = get_posts(array(
        'post_type' => 'job_application',
        'meta_query' => array(
            array(
                'key' => 'job_id',
                'value' => $job->ID
            )
        ),
        'posts_per_page' => -1
    ));
    $total_applications += count($applications);
    
    // Count new applications (last 7 days)
    $new_applications += count(get_posts(array(
        'post_type' => 'job_application',
        'meta_query' => array(
            array(
                'key' => 'job_id',
                'value' => $job->ID
            )
        ),
        'date_query' => array(
            array(
                'after' => '1 week ago'
            )
        ),
        'posts_per_page' => -1
    )));
}
?>

<div class="dashboard-overview">
    <h2 class="h4 mb-4">Dashboard Overview</h2>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Jobs</h6>
                    <h2 class="card-text mb-0"><?php echo $total_jobs; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Active Jobs</h6>
                    <h2 class="card-text mb-0"><?php echo $active_jobs; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Applications</h6>
                    <h2 class="card-text mb-0"><?php echo $total_applications; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">New Applications</h6>
                    <h2 class="card-text mb-0"><?php echo $new_applications; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <!-- Recent Applications -->
        <div class="col-md-6 mb-4">
            <h3 class="h5 mb-3">Recent Applications</h3>
            <?php if ($recent_applications) : ?>
                <div class="list-group">
                    <?php foreach ($recent_applications as $application) : 
                        $job_id = get_post_meta($application->ID, 'job_id', true);
                        $job = get_post($job_id);
                        $applicant_id = get_post_meta($application->ID, 'applicant_id', true);
                        $applicant = get_userdata($applicant_id);
                    ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo get_the_title($job); ?></h6>
                                <small class="text-muted"><?php echo human_time_diff(get_post_time('U', false, $application), current_time('timestamp')) . ' ago'; ?></small>
                            </div>
                            <p class="mb-1">Applicant: <?php echo $applicant->display_name; ?></p>
                            <small class="text-muted">Status: <?php echo ucfirst(get_post_meta($application->ID, 'status', true)); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="text-muted">No recent applications.</p>
            <?php endif; ?>
        </div>

        <!-- Active Jobs -->
        <div class="col-md-6 mb-4">
            <h3 class="h5 mb-3">Active Job Listings</h3>
            <?php if ($jobs) : ?>
                <div class="list-group">
                    <?php foreach ($jobs as $job) : 
                        if (get_post_meta($job->ID, 'job_status', true) === 'active') :
                    ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo get_the_title($job); ?></h6>
                                <small class="text-muted">Posted <?php echo human_time_diff(get_post_time('U', false, $job), current_time('timestamp')); ?> ago</small>
                            </div>
                            <?php 
                            $job_applications = get_posts(array(
                                'post_type' => 'job_application',
                                'meta_query' => array(
                                    array(
                                        'key' => 'job_id',
                                        'value' => $job->ID
                                    )
                                ),
                                'posts_per_page' => -1
                            ));
                            ?>
                            <p class="mb-0">
                                <small class="text-muted">
                                    <?php echo count($job_applications); ?> application(s)
                                </small>
                            </p>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            <?php else : ?>
                <p class="text-muted">No active jobs.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions mt-2">
        <h3 class="h5 mb-3">Quick Actions</h3>
        <div class="row g-3">
            <div class="col-md-4">
                <a href="<?php echo add_query_arg('tab', 'post-job'); ?>" class="btn btn-primary w-100">
                    <i class="bi bi-plus-circle me-2"></i> Post New Job
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?php echo add_query_arg('tab', 'applications'); ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-people me-2"></i> View All Applications
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?php echo add_query_arg('tab', 'profile'); ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-building me-2"></i> Update Company Profile
                </a>
            </div>
        </div>
    </div>
</div> 