<?php
/**
 * Template part for displaying employee dashboard overview
 */

$current_user = wp_get_current_user();

// Get employee's resume
$resume = get_posts(array(
    'post_type' => 'resume',
    'author' => $current_user->ID,
    'posts_per_page' => 1
));

// Get job applications
$applications = get_posts(array(
    'post_type' => 'job_application',
    'meta_query' => array(
        array(
            'key' => 'applicant_id',
            'value' => $current_user->ID
        )
    ),
    'posts_per_page' => -1
));

// Get saved jobs
$saved_jobs = get_user_meta($current_user->ID, 'saved_jobs', true);
if (!is_array($saved_jobs)) {
    $saved_jobs = array();
}

// Count statistics
$total_applications = count($applications);
$pending_applications = 0;
$shortlisted = 0;
$rejected = 0;

foreach ($applications as $application) {
    $status = get_post_meta($application->ID, 'status', true);
    switch ($status) {
        case 'pending':
            $pending_applications++;
            break;
        case 'shortlisted':
            $shortlisted++;
            break;
        case 'rejected':
            $rejected++;
            break;
    }
}

// Get recent job applications
$recent_applications = array_slice($applications, 0, 5);

// Get recommended jobs based on skills
$resume_skills = array();
if (!empty($resume)) {
    $skills = get_post_meta($resume[0]->ID, 'skills', true);
    if (is_array($skills)) {
        $resume_skills = $skills;
    }
}

$recommended_jobs = array();
if (!empty($resume_skills)) {
    $recommended_jobs = get_posts(array(
        'post_type' => 'jobs',
        'posts_per_page' => 5,
        'meta_query' => array(
            array(
                'key' => 'required_skills',
                'value' => $resume_skills,
                'compare' => 'IN'
            )
        )
    ));
}
?>

<div class="dashboard-overview">
    <h2 class="h4 mb-4">Dashboard Overview</h2>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Applications</h6>
                    <h2 class="card-text mb-0"><?php echo $total_applications; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Pending</h6>
                    <h2 class="card-text mb-0"><?php echo $pending_applications; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Shortlisted</h6>
                    <h2 class="card-text mb-0"><?php echo $shortlisted; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6 class="card-title">Saved Jobs</h6>
                    <h2 class="card-text mb-0"><?php echo count($saved_jobs); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Resume Status -->
    <?php if (empty($resume)) : ?>
        <div class="alert alert-warning mb-4" role="alert">
            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Complete Your Resume</h4>
            <p class="mb-0">Your resume is not yet created. Create your resume to start applying for jobs.</p>
            <hr>
            <a href="<?php echo add_query_arg('tab', 'resume'); ?>" class="btn btn-warning">Create Resume</a>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Recent Applications -->
        <div class="col-md-6 mb-4">
            <h3 class="h5 mb-3">Recent Applications</h3>
            <?php if ($recent_applications) : ?>
                <div class="list-group">
                    <?php foreach ($recent_applications as $application) : 
                        $job_id = get_post_meta($application->ID, 'job_id', true);
                        $job = get_post($job_id);
                        $status = get_post_meta($application->ID, 'status', true);
                    ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo get_the_title($job); ?></h6>
                                <small class="text-muted"><?php echo human_time_diff(get_post_time('U', false, $application), current_time('timestamp')) . ' ago'; ?></small>
                            </div>
                            <p class="mb-1"><?php echo get_post_meta($job->ID, 'company_name', true); ?></p>
                            <small class="text-muted">Status: 
                                <span class="badge bg-<?php 
                                    echo $status === 'pending' ? 'warning' : 
                                        ($status === 'shortlisted' ? 'success' : 
                                        ($status === 'rejected' ? 'danger' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="text-muted">No applications yet.</p>
            <?php endif; ?>
        </div>

        <!-- Recommended Jobs -->
        <div class="col-md-6 mb-4">
            <h3 class="h5 mb-3">Recommended Jobs</h3>
            <?php if ($recommended_jobs) : ?>
                <div class="list-group">
                    <?php foreach ($recommended_jobs as $job) : ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo get_the_title($job); ?></h6>
                                <small class="text-muted">Posted <?php echo human_time_diff(get_post_time('U', false, $job), current_time('timestamp')); ?> ago</small>
                            </div>
                            <p class="mb-1"><?php echo get_post_meta($job->ID, 'company_name', true); ?></p>
                            <small class="text-muted">
                                <?php echo get_post_meta($job->ID, 'job_location', true); ?> • 
                                <?php 
                                $job_types = get_the_terms($job->ID, 'job_type');
                                if ($job_types && !is_wp_error($job_types)) {
                                    $type_names = array();
                                    foreach ($job_types as $type) {
                                        $type_names[] = esc_html($type->name);
                                    }
                                    echo implode(', ', $type_names);
                                }
                                ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="text-muted">No recommended jobs available.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions mt-2">
        <h3 class="h5 mb-3">Quick Actions</h3>
        <div class="row g-3">
            <div class="col-md-4">
                <a href="<?php echo add_query_arg('tab', 'search-jobs'); ?>" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i> Search Jobs
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?php echo add_query_arg('tab', 'applications'); ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-briefcase me-2"></i> View Applications
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?php echo add_query_arg('tab', 'resume'); ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-file-person me-2"></i> Update Resume
                </a>
            </div>
        </div>
    </div>
</div> 