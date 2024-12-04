<?php
/**
 * Template Name: Employer Dashboard
 */

// Redirect if not logged in or not an employer
if (!is_user_logged_in() || !in_array('employer', wp_get_current_user()->roles)) {
    wp_redirect(home_url());
    exit;
}

get_header();

$current_user = wp_get_current_user();
$employer_profile = get_posts(array(
    'post_type' => 'employer_profile',
    'author' => $current_user->ID,
    'posts_per_page' => 1
));

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
?>

<div class="employer-dashboard py-4">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Employer Dashboard</h5>
                        <div class="nav flex-column nav-pills">
                            <a class="nav-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'dashboard'); ?>">
                                <i class="bi bi-speedometer2 me-2"></i> Overview
                            </a>
                            <a class="nav-link <?php echo $active_tab === 'post-job' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'post-job'); ?>">
                                <i class="bi bi-plus-circle me-2"></i> Post a Job
                            </a>
                            <a class="nav-link <?php echo $active_tab === 'manage-jobs' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'manage-jobs'); ?>">
                                <i class="bi bi-briefcase me-2"></i> Manage Jobs
                            </a>
                            <a class="nav-link <?php echo $active_tab === 'applications' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'applications'); ?>">
                                <i class="bi bi-people me-2"></i> Applications
                            </a>
                            <a class="nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'profile'); ?>">
                                <i class="bi bi-building me-2"></i> Company Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <?php
                        switch ($active_tab) {
                            case 'dashboard':
                                get_template_part('template-parts/dashboard/employer', 'overview');
                                break;
                            case 'post-job':
                                get_template_part('template-parts/dashboard/employer', 'post-job');
                                break;
                            case 'manage-jobs':
                                get_template_part('template-parts/dashboard/employer', 'manage-jobs');
                                break;
                            case 'applications':
                                get_template_part('template-parts/dashboard/employer', 'applications');
                                break;
                            case 'profile':
                                get_template_part('template-parts/dashboard/employer', 'profile');
                                break;
                            default:
                                get_template_part('template-parts/dashboard/employer', 'overview');
                                break;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?> 