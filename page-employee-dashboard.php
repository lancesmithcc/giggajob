<?php
/**
 * Template Name: Employee Dashboard
 */

error_log('=== Employee Dashboard Access Debug ===');
error_log('User logged in: ' . (is_user_logged_in() ? 'yes' : 'no'));
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    error_log('User roles: ' . print_r($current_user->roles, true));
}

// Redirect if not logged in or not an employee
if (!is_user_logged_in()) {
    error_log('User not logged in - redirecting to login');
    wp_redirect(wp_login_url(home_url('/employee-dashboard/')));
    exit;
}

if (!in_array('employee', wp_get_current_user()->roles)) {
    error_log('User not an employee - redirecting to home');
    wp_redirect(home_url());
    exit;
}

error_log('User authorized - loading dashboard');

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
                            <a class="nav-link <?php echo $active_tab === 'search' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'search'); ?>">
                                <i class="bi bi-search me-2"></i> Search Jobs
                            </a>
                            <a class="nav-link <?php echo $active_tab === 'resume' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'resume'); ?>">
                                <i class="bi bi-file-person me-2"></i> Resume
                            </a>
                            <a class="nav-link <?php echo $active_tab === 'applications' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'applications'); ?>">
                                <i class="bi bi-file-earmark-person me-2"></i>Applications
                            </a>
                            <a class="nav-link <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" 
                               href="<?php echo add_query_arg('tab', 'settings'); ?>">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <?php if ($active_tab === 'search'): ?>
                    <?php get_template_part('template-parts/dashboard/employee', 'search'); ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="tab-content mt-4">
                                <?php
                                switch ($active_tab) {
                                    case 'resume':
                                        get_template_part('template-parts/dashboard/employee', 'resume');
                                        break;
                                    case 'applications':
                                        get_template_part('template-parts/dashboard/employee', 'applications');
                                        break;
                                    case 'settings':
                                        get_template_part('template-parts/dashboard/employee', 'settings');
                                        break;
                                    default:
                                        get_template_part('template-parts/dashboard/employee', 'overview');
                                        break;
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?> 