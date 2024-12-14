<?php
/**
 * Template Name: Employee Dashboard
 */

// Redirect if not logged in or not an employee
if (!is_user_logged_in() || !in_array('employee', wp_get_current_user()->roles)) {
    wp_redirect(home_url());
    exit;
}

get_header();

$current_user = wp_get_current_user();
$resume = get_posts(array(
    'post_type' => 'resume',
    'author' => $current_user->ID,
    'posts_per_page' => 1
));

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
?>

<div class="employee-dashboard py-4">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Employee Dashboard</h5>
                        <div class="nav flex-column nav-pills">
                            <a class="nav-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'dashboard'); ?>">
                                <i class="bi bi-speedometer2 me-2"></i> Overview
                            </a>
                            <a class="nav-link <?php echo $active_tab === 'resume' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'resume'); ?>">
                                <i class="bi bi-file-person me-2"></i> Resume
                            </a>
                            <a class="nav-link <?php echo $active_tab === 'applications' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'applications'); ?>">
                                <i class="bi bi-briefcase me-2"></i> Applications
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
                                get_template_part('template-parts/dashboard/employee', 'overview');
                                break;
                            case 'resume':
                                get_template_part('template-parts/dashboard/employee', 'resume');
                                break;
                            case 'applications':
                                get_template_part('template-parts/dashboard/employee', 'applications');
                                break;
                            default:
                                get_template_part('template-parts/dashboard/employee', 'overview');
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