<?php
/**
 * Template Name: Employer Dashboard
 */

// Security check
if (!defined('ABSPATH')) exit;

// Redirect non-employers to home
if (!is_user_logged_in() || !in_array('employer', wp_get_current_user()->roles)) {
    wp_redirect(home_url());
    exit;
}

get_header();

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
?>

<div class="dashboard-container py-5">
    <div class="container">
        <!-- Navigation -->
        <div class="card bg-dark border-secondary mb-4">
            <div class="card-body">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab === 'overview' ? 'active' : ''; ?>" 
                           href="<?php echo remove_query_arg('tab'); ?>">
                            <i class="bi bi-speedometer2 me-2"></i>Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab === 'post-job' ? 'active' : ''; ?>" 
                           href="<?php echo add_query_arg('tab', 'post-job'); ?>">
                            <i class="bi bi-plus-circle me-2"></i>Post Job
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab === 'manage-jobs' ? 'active' : ''; ?>" 
                           href="<?php echo add_query_arg('tab', 'manage-jobs'); ?>">
                            <i class="bi bi-briefcase me-2"></i>Manage Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab === 'applications' ? 'active' : ''; ?>" 
                           href="<?php echo add_query_arg('tab', 'applications'); ?>">
                            <i class="bi bi-file-earmark-person me-2"></i>Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" 
                           href="<?php echo add_query_arg('tab', 'profile'); ?>">
                            <i class="bi bi-building me-2"></i>Company Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" 
                           href="<?php echo add_query_arg('tab', 'settings'); ?>">
                            <i class="bi bi-gear me-2"></i>Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab === 'search' ? 'active' : ''; ?>" 
                           href="<?php echo add_query_arg('tab', 'search'); ?>">
                            <i class="bi bi-search me-2"></i>Search Jobs
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Content -->
        <div class="dashboard-content">
            <?php
            switch ($active_tab) {
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
                case 'settings':
                    get_template_part('template-parts/dashboard/employer', 'settings');
                    break;
                case 'search':
                    get_template_part('template-parts/dashboard/employer', 'search');
                    break;
                default:
                    get_template_part('template-parts/dashboard/employer', 'overview');
                    break;
            }
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?> 