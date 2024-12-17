<?php
if (!defined('ABSPATH')) exit;

// Include the Bootstrap 5 Nav Walker
require_once get_template_directory() . '/inc/class-bootstrap-5-nav-walker.php';

// Register Custom Taxonomies
function giggajob_register_taxonomies() {
    // Industries Taxonomy
    register_taxonomy('industry', array('jobs', 'employer_profile'), array(
        'labels' => array(
            'name' => __('Industries', 'giggajob'),
            'singular_name' => __('Industry', 'giggajob'),
            'search_items' => __('Search Industries', 'giggajob'),
            'all_items' => __('All Industries', 'giggajob'),
            'parent_item' => __('Parent Industry', 'giggajob'),
            'parent_item_colon' => __('Parent Industry:', 'giggajob'),
            'edit_item' => __('Edit Industry', 'giggajob'),
            'update_item' => __('Update Industry', 'giggajob'),
            'add_new_item' => __('Add New Industry', 'giggajob'),
            'new_item_name' => __('New Industry Name', 'giggajob'),
            'menu_name' => __('Industries', 'giggajob'),
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'industry'),
        'show_in_rest' => true, // Enable Gutenberg editor support
    ));
}
add_action('init', 'giggajob_register_taxonomies');

// Function to organize taxonomy terms hierarchically
function organize_terms_hierarchically($terms, $parent_id = 0) {
    $children = array();
    foreach ($terms as $term) {
        if ($term->parent == $parent_id) {
            $term->children = organize_terms_hierarchically($terms, $term->term_id);
            $children[] = $term;
        }
    }
    return $children;
}

// Handle Job Submission
function giggajob_handle_job_submission() {
    // Start debugging
    error_log('Job submission started');
    
    // Check nonce
    if (!isset($_POST['job_nonce'])) {
        error_log('Job submission failed: No nonce provided');
        wp_send_json_error(array('message' => 'Security check failed - no nonce'));
    }
    
    if (!wp_verify_nonce($_POST['job_nonce'], 'post_job_nonce')) {
        error_log('Job submission failed: Nonce verification failed');
        wp_send_json_error(array('message' => 'Security check failed - invalid nonce'));
    }

    // Check if user is logged in and is an employer
    if (!is_user_logged_in()) {
        error_log('Job submission failed: User not logged in');
        wp_send_json_error(array('message' => 'User not logged in'));
    }
    
    if (!in_array('employer', wp_get_current_user()->roles)) {
        error_log('Job submission failed: User not an employer');
        wp_send_json_error(array('message' => 'User not an employer'));
    }

    // Log received data
    error_log('POST data received: ' . print_r($_POST, true));

    // Validate required fields
    $required_fields = array(
        'job_title' => 'Job Title',
        'company_name' => 'Company Name',
        'job_type' => 'Job Type',
        'job_location' => 'Location',
        'job_description' => 'Job Description',
        'remote_option' => 'Remote Work Option',
        'salary_type' => 'Salary Information'
    );

    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            error_log("Job submission failed: Missing required field - {$label}");
            wp_send_json_error(array('message' => $label . ' is required.'));
        }
    }

    try {
        // Check if editing or creating new job
        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        $editing = false;

        if ($job_id) {
            $job = get_post($job_id);
            if (!$job || $job->post_author != get_current_user_id()) {
                error_log('Job submission failed: Invalid job or permission denied');
                wp_send_json_error(array('message' => 'Invalid job or permission denied.'));
            }
            $editing = true;
        }

        // Prepare job data
        $job_data = array(
            'post_title' => sanitize_text_field($_POST['job_title']),
            'post_content' => wp_kses_post($_POST['job_description']),
            'post_status' => 'publish',
            'post_type' => 'jobs',
            'post_author' => get_current_user_id()
        );

        if ($editing) {
            $job_data['ID'] = $job_id;
            error_log('Updating existing job: ' . $job_id);
            $job_id = wp_update_post($job_data);
        } else {
            error_log('Creating new job');
            $job_id = wp_insert_post($job_data);
        }

        if (is_wp_error($job_id)) {
            error_log('Job submission failed: wp_insert_post/wp_update_post error - ' . $job_id->get_error_message());
            wp_send_json_error(array('message' => 'Failed to ' . ($editing ? 'update' : 'create') . ' job post.'));
        }

        error_log('Job post created/updated successfully. Job ID: ' . $job_id);

        // Handle featured image
        if (!empty($_POST['job_featured_image_id'])) {
            set_post_thumbnail($job_id, intval($_POST['job_featured_image_id']));
        } else {
            delete_post_thumbnail($job_id);
        }

        // Save job meta data
        $meta_fields = array(
            'company_name' => sanitize_text_field($_POST['company_name']),
            'job_type' => sanitize_text_field($_POST['job_type']),
            'job_location' => sanitize_text_field($_POST['job_location']),
            'remote_option' => sanitize_text_field($_POST['remote_option']),
            'job_status' => 'active'
        );

        // Handle salary information
        if ($_POST['salary_type'] === 'exempt') {
            $meta_fields['salary'] = 'legal exemption for non-disclosure';
        } else {
            $salary_min = isset($_POST['salary_min']) ? intval($_POST['salary_min']) : 0;
            $salary_max = isset($_POST['salary_max']) ? intval($_POST['salary_max']) : 0;
            $salary_period = isset($_POST['salary_period']) ? sanitize_text_field($_POST['salary_period']) : 'year';

            if ($_POST['salary_type'] === 'fixed') {
                $meta_fields['salary'] = $salary_min . ' per ' . $salary_period;
            } else {
                $meta_fields['salary'] = $salary_min . ' - ' . $salary_max . ' per ' . $salary_period;
            }

            // Store individual salary components for filtering
            $meta_fields['salary_min'] = $salary_min;
            $meta_fields['salary_max'] = $salary_max;
            $meta_fields['salary_period'] = $salary_period;
        }

        // Save all meta fields
        foreach ($meta_fields as $key => $value) {
            update_post_meta($job_id, $key, $value);
        }

        // Set taxonomies
        if (!empty($_POST['industry']) && is_array($_POST['industry'])) {
            $industries = array_map('intval', $_POST['industry']);
            wp_set_object_terms($job_id, $industries, 'industry');
        }

        // Set expiry date (30 days from now)
        $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
        update_post_meta($job_id, 'job_expiry_date', $expiry_date);

        error_log('All meta data saved successfully');

        // Make sure we haven't output anything before this point
        if (!headers_sent()) {
            wp_send_json_success(array(
                'message' => 'Job ' . ($editing ? 'updated' : 'posted') . ' successfully!',
                'redirect_url' => home_url('/employer-dashboard/?tab=manage-jobs')
            ));
        } else {
            error_log('Headers already sent before JSON response');
            die(json_encode(array(
                'success' => true,
                'data' => array(
                    'message' => 'Job ' . ($editing ? 'updated' : 'posted') . ' successfully!',
                    'redirect_url' => home_url('/employer-dashboard/?tab=manage-jobs')
                )
            )));
        }

    } catch (Exception $e) {
        error_log('Job submission failed with exception: ' . $e->getMessage());
        wp_send_json_error(array('message' => 'An unexpected error occurred.'));
    }
}
add_action('wp_ajax_post_job', 'giggajob_handle_job_submission');

// Add filter to control when featured images are displayed
add_filter('post_thumbnail_html', 'giggajob_filter_job_thumbnail', 10, 5);
function giggajob_filter_job_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {
    // Only show featured images on single job pages
    if (get_post_type($post_id) === 'jobs' && !is_singular('jobs')) {
        return '';
    }
    return $html;
}

// Theme Setup
function giggajob_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'giggajob'),
        'footer' => __('Footer Menu', 'giggajob'),
    ));
}
add_action('after_setup_theme', 'giggajob_theme_setup');

// Register Custom Post Types
function giggajob_register_post_types() {
    // Jobs Post Type
    register_post_type('jobs', array(
        'labels' => array(
            'name' => __('Jobs', 'giggajob'),
            'singular_name' => __('Job', 'giggajob'),
            'add_new' => __('Add New Job', 'giggajob'),
            'add_new_item' => __('Add New Job', 'giggajob'),
            'edit_item' => __('Edit Job', 'giggajob'),
        ),
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-businessman',
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'rewrite' => array('slug' => 'jobs'),
        'capability_type' => 'job',
        'map_meta_cap' => true,
        'capabilities' => array(
            'edit_post' => 'edit_job',
            'read_post' => 'read_job',
            'delete_post' => 'delete_job',
            'edit_posts' => 'edit_jobs',
            'edit_others_posts' => 'edit_others_jobs',
            'publish_posts' => 'publish_jobs',
            'read_private_posts' => 'read_private_jobs',
            'delete_posts' => 'delete_jobs'
        ),
        'taxonomies' => array('industry')
    ));

    // Job Applications Post Type
    register_post_type('job_application', array(
        'labels' => array(
            'name' => __('Job Applications', 'giggajob'),
            'singular_name' => __('Job Application', 'giggajob'),
            'add_new' => __('Add New Application', 'giggajob'),
            'add_new_item' => __('Add New Application', 'giggajob'),
            'edit_item' => __('Edit Application', 'giggajob'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-portfolio',
        'supports' => array('title', 'custom-fields'),
        'capability_type' => 'application',
        'map_meta_cap' => true,
        'capabilities' => array(
            'edit_post' => 'edit_application',
            'read_post' => 'read_application',
            'delete_post' => 'delete_application',
            'edit_posts' => 'edit_applications',
            'edit_others_posts' => 'edit_others_applications',
            'publish_posts' => 'publish_applications',
            'read_private_posts' => 'read_private_applications',
            'delete_posts' => 'delete_applications'
        )
    ));

    // Resume Post Type
    register_post_type('resume', array(
        'labels' => array(
            'name' => __('Resumes', 'giggajob'),
            'singular_name' => __('Resume', 'giggajob'),
            'add_new' => __('Add New Resume', 'giggajob'),
            'add_new_item' => __('Add New Resume', 'giggajob'),
            'edit_item' => __('Edit Resume', 'giggajob'),
        ),
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-id',
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'rewrite' => array('slug' => 'resumes'),
        'capability_type' => 'resume',
        'map_meta_cap' => true,
        'capabilities' => array(
            'edit_post' => 'edit_resume',
            'read_post' => 'read_resume',
            'delete_post' => 'delete_resume',
            'edit_posts' => 'edit_resumes',
            'edit_others_posts' => 'edit_others_resumes',
            'publish_posts' => 'publish_resumes',
            'read_private_posts' => 'read_private_resumes',
            'delete_posts' => 'delete_resumes'
        )
    ));

    // Employer Profile Post Type
    register_post_type('employer_profile', array(
        'labels' => array(
            'name' => __('Employer Profiles', 'giggajob'),
            'singular_name' => __('Employer Profile', 'giggajob'),
            'add_new' => __('Add New Profile', 'giggajob'),
            'add_new_item' => __('Add New Profile', 'giggajob'),
            'edit_item' => __('Edit Profile', 'giggajob'),
        ),
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-building',
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'rewrite' => array('slug' => 'employer-profiles'),
        'capability_type' => 'employer_profile',
        'map_meta_cap' => true,
        'capabilities' => array(
            'edit_post' => 'edit_employer_profile',
            'read_post' => 'read_employer_profile',
            'delete_post' => 'delete_employer_profile',
            'edit_posts' => 'edit_employer_profiles',
            'edit_others_posts' => 'edit_others_employer_profiles',
            'publish_posts' => 'publish_employer_profiles',
            'read_private_posts' => 'read_private_employer_profiles',
            'delete_posts' => 'delete_employer_profiles'
        ),
        'taxonomies' => array('industry')
    ));
}
add_action('init', 'giggajob_register_post_types');

// Create custom user roles on theme activation
function giggajob_create_user_roles() {
    error_log('=== Creating Custom User Roles ===');
    
    // Remove roles first to ensure clean setup
    remove_role('employee');
    remove_role('employer');
    
    // Add the employee role
    $employee = add_role('employee', 'Employee', array(
        'read' => true,
        'upload_files' => true,
        // Resume capabilities
        'edit_resume' => true,
        'read_resume' => true,
        'delete_resume' => true,
        'edit_resumes' => true,
        'publish_resumes' => true,
        'edit_published_resumes' => true,
        'delete_published_resumes' => true,
        // Application capabilities
        'edit_application' => true,
        'read_application' => true,
        'delete_application' => true,
        'edit_applications' => true,
        'publish_applications' => true
    ));
    
    if (is_null($employee)) {
        error_log('Failed to create employee role');
    } else {
        error_log('Employee role created successfully');
    }

    // Add the employer role
    $employer = add_role('employer', 'Employer', array(
        'read' => true,
        'upload_files' => true,
        // Job capabilities
        'edit_job' => true,
        'read_job' => true,
        'delete_job' => true,
        'edit_jobs' => true,
        'publish_jobs' => true,
        'edit_published_jobs' => true,
        'delete_published_jobs' => true,
        // Employer profile capabilities
        'edit_employer_profile' => true,
        'read_employer_profile' => true,
        'delete_employer_profile' => true,
        'edit_employer_profiles' => true,
        'publish_employer_profiles' => true,
        // Application capabilities
        'edit_application' => true,
        'read_application' => true,
        'delete_application' => true,
        'edit_applications' => true,
        'publish_applications' => true
    ));
    
    if (is_null($employer)) {
        error_log('Failed to create employer role');
    } else {
        error_log('Employer role created successfully');
    }
}

// Hook into theme activation and switch
add_action('after_switch_theme', 'giggajob_create_user_roles');
add_action('after_setup_theme', 'giggajob_create_user_roles');

// Add custom capabilities to roles
function giggajob_add_role_caps() {
    error_log('=== Adding Role Capabilities ===');
    
    // Get the employer role
    $employer = get_role('employer');
    if ($employer) {
        error_log('Adding employer capabilities');
        $employer_caps = array(
            'read' => true,
            'upload_files' => true,
            // Job capabilities
            'edit_job' => true,
            'read_job' => true,
            'delete_job' => true,
            'edit_jobs' => true,
            'publish_jobs' => true,
            'edit_published_jobs' => true,
            'delete_published_jobs' => true,
            // Employer profile capabilities
            'edit_employer_profile' => true,
            'read_employer_profile' => true,
            'delete_employer_profile' => true,
            'edit_employer_profiles' => true,
            'publish_employer_profiles' => true,
            // Application capabilities
            'edit_application' => true,
            'read_application' => true,
            'delete_application' => true,
            'edit_applications' => true,
            'publish_applications' => true
        );
        
        foreach ($employer_caps as $cap => $grant) {
            $employer->add_cap($cap, $grant);
        }
    } else {
        error_log('Employer role not found');
    }

    // Get the employee role
    $employee = get_role('employee');
    if ($employee) {
        error_log('Adding employee capabilities');
        $employee_caps = array(
            'read' => true,
            'upload_files' => true,
            // Resume capabilities
            'edit_resume' => true,
            'read_resume' => true,
            'delete_resume' => true,
            'edit_resumes' => true,
            'publish_resumes' => true,
            'edit_published_resumes' => true,
            'delete_published_resumes' => true,
            // Application capabilities
            'edit_application' => true,
            'read_application' => true,
            'delete_application' => true,
            'edit_applications' => true,
            'publish_applications' => true
        );
        
        foreach ($employee_caps as $cap => $grant) {
            $employee->add_cap($cap, $grant);
        }
    } else {
        error_log('Employee role not found');
    }
}
add_action('init', 'giggajob_add_role_caps');

// Function to check and repair user roles
function giggajob_check_user_roles() {
    error_log('=== Checking User Roles ===');
    
    // Get all users with employee or employer role
    $users = get_users(array(
        'role__in' => array('employee', 'employer')
    ));
    
    foreach ($users as $user) {
        error_log('Checking user: ' . $user->ID);
        error_log('Current roles: ' . print_r($user->roles, true));
        
        // Ensure roles have proper capabilities
        if (in_array('employer', $user->roles)) {
            $employer_caps = array(
                'read', 'upload_files',
                'edit_job', 'read_job', 'delete_job', 'edit_jobs', 'publish_jobs',
                'edit_published_jobs', 'delete_published_jobs',
                'edit_employer_profile', 'read_employer_profile', 'delete_employer_profile',
                'edit_employer_profiles', 'publish_employer_profiles',
                'edit_application', 'read_application', 'delete_application',
                'edit_applications', 'publish_applications'
            );
            foreach ($employer_caps as $cap) {
                $user->add_cap($cap);
            }
        }
        
        if (in_array('employee', $user->roles)) {
            $employee_caps = array(
                'read', 'upload_files',
                'edit_resume', 'read_resume', 'delete_resume', 'edit_resumes',
                'publish_resumes', 'edit_published_resumes', 'delete_published_resumes',
                'edit_application', 'read_application', 'delete_application',
                'edit_applications', 'publish_applications'
            );
            foreach ($employee_caps as $cap) {
                $user->add_cap($cap);
            }
        }
    }
}
add_action('init', 'giggajob_check_user_roles');

// Restrict Admin Access
function giggajob_restrict_admin_access() {
    if (is_admin() && !current_user_can('administrator') && 
        !(defined('DOING_AJAX') && DOING_AJAX)) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('init', 'giggajob_restrict_admin_access');

// Modify the login redirect function to be more robust
function giggajob_login_redirect($redirect_to, $request, $user) {
    error_log('=== Login Redirect Debug ===');
    error_log('Original redirect_to: ' . $redirect_to);
    error_log('Request: ' . $request);
    
    if (!is_wp_error($user) && $user instanceof WP_User) {
        error_log('User ID: ' . $user->ID);
        error_log('User roles: ' . print_r($user->roles, true));
        
        // Force refresh user capabilities
        $user->get_role_caps();
        
        if (in_array('employee', $user->roles)) {
            $url = home_url('/employee-dashboard/');
            error_log('Redirecting employee to dashboard: ' . $url);
            return $url;
        } elseif (in_array('employer', $user->roles)) {
            $url = home_url('/employer-dashboard/');
            error_log('Redirecting employer to dashboard: ' . $url);
            return $url;
        }
    } else {
        error_log('Invalid user object or WP_Error');
    }
    
    error_log('Using default redirect');
    return $redirect_to;
}
add_filter('login_redirect', 'giggajob_login_redirect', 10, 3);

// Add authentication success action
add_action('wp_login', function($user_login, $user) {
    error_log('=== Login Success ===');
    error_log('User logged in: ' . $user_login);
    error_log('User ID: ' . $user->ID);
    error_log('User roles: ' . print_r($user->roles, true));
    
    // Force refresh capabilities
    $user->get_role_caps();
    
    // Set authentication cookie with longer expiration
    wp_set_auth_cookie($user->ID, true);
}, 10, 2);

// Add authentication debugging with more detail
add_filter('authenticate', function($user, $username, $password) {
    error_log('=== Authentication Debug ===');
    error_log('Username attempting login: ' . $username);
    
    if (is_wp_error($user)) {
        error_log('Authentication error: ' . $user->get_error_message());
    } else if ($user instanceof WP_User) {
        error_log('User authenticated successfully');
        error_log('User ID: ' . $user->ID);
        error_log('User roles: ' . print_r($user->roles, true));
        error_log('User capabilities: ' . print_r($user->allcaps, true));
    }
    
    return $user;
}, 30, 3);

// Add init hook to check user status with more detail
add_action('init', function() {
    error_log('=== Init Hook Debug ===');
    error_log('Is user logged in: ' . (is_user_logged_in() ? 'yes' : 'no'));
    
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        error_log('Current user ID: ' . $user->ID);
        error_log('Current user roles: ' . print_r($user->roles, true));
        error_log('Current user capabilities: ' . print_r($user->allcaps, true));
    }
});

// Enqueue scripts and styles
function giggajob_enqueue_scripts() {
    wp_enqueue_style('giggajob-style', get_stylesheet_uri(), array('dashicons'), null);
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css');
    wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css');
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_style('select2-bootstrap5', 'https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css');

    // Enqueue dashicons for the editor and make sure it loads before our theme styles
    wp_enqueue_style('dashicons');
    
    // Add admin bar styles if showing
    if (is_admin_bar_showing()) {
        wp_enqueue_style('admin-bar');
    }

    // Enqueue scripts
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array('jquery'), null, true);
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), null, true);
    wp_enqueue_script('giggajob-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery', 'select2'), '1.0.0', true);

    // Localize the script with new data
    wp_localize_script('giggajob-main', 'giggajob_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('giggajob_ajax_nonce')
    ));

    // Add editor scripts and styles if we're on the post job page
    if (is_page('employer-dashboard') && isset($_GET['tab']) && $_GET['tab'] === 'post-job') {
        wp_enqueue_editor();
        wp_enqueue_media();
    }
}
add_action('wp_enqueue_scripts', 'giggajob_enqueue_scripts');

// Enqueue admin styles
function giggajob_admin_enqueue_scripts() {
    wp_enqueue_style('dashicons');
    wp_enqueue_style('wp-admin');
    wp_enqueue_style('giggajob-admin', get_template_directory_uri() . '/assets/css/admin.css', array('dashicons', 'wp-admin'), null);
}
add_action('admin_enqueue_scripts', 'giggajob_admin_enqueue_scripts');

// Fix admin bar styling
function giggajob_admin_bar_style() {
    if (is_admin_bar_showing()) {
        ?>
        <style>
            #wpadminbar {
                position: fixed !important;
            }
            html {
                margin-top: 32px !important;
            }
            @media screen and (max-width: 782px) {
                html {
                    margin-top: 46px !important;
                }
            }
            /* Ensure admin bar icons use correct font */
            #wpadminbar .ab-icon,
            #wpadminbar .ab-item:before,
            #wpadminbar>#wp-toolbar>#wp-admin-bar-root-default .ab-icon {
                font: normal 20px/1 dashicons !important;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'giggajob_admin_bar_style');

// Handle Job Actions (activate, pause, delete)
function giggajob_handle_job_action() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'giggajob_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // Check if user is logged in and is an employer
    if (!is_user_logged_in() || !in_array('employer', wp_get_current_user()->roles)) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    // Get job ID and action
    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $action = isset($_POST['job_action']) ? sanitize_text_field($_POST['job_action']) : '';

    // Verify job ownership
    $job = get_post($job_id);
    if (!$job || $job->post_author != get_current_user_id()) {
        wp_send_json_error(array('message' => 'Invalid job or permission denied.'));
    }

    switch ($action) {
        case 'activate':
            update_post_meta($job_id, 'job_status', 'active');
            wp_send_json_success(array('message' => 'Job activated successfully.'));
            break;

        case 'pause':
            update_post_meta($job_id, 'job_status', 'draft');
            wp_send_json_success(array('message' => 'Job paused successfully.'));
            break;

        case 'delete':
            wp_delete_post($job_id, true);
            wp_send_json_success(array('message' => 'Job deleted successfully.'));
            break;

        default:
            wp_send_json_error(array('message' => 'Invalid action.'));
            break;
    }
}
add_action('wp_ajax_job_action', 'giggajob_handle_job_action');

// Add taxonomy-based filtering to job search
function giggajob_job_search_filters($query) {
    if (!is_admin() && $query->is_main_query() && is_post_type_archive('jobs')) {
        // Industry filter
        if (isset($_GET['industry']) && !empty($_GET['industry'])) {
            $tax_query[] = array(
                'taxonomy' => 'industry',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['industry'])
            );
        }

        // Job type filter
        if (isset($_GET['job_type']) && !empty($_GET['job_type'])) {
            $meta_query[] = array(
                'key' => 'job_type',
                'value' => sanitize_text_field($_GET['job_type'])
            );
        }

        // Location filter
        if (isset($_GET['job_location']) && !empty($_GET['job_location'])) {
            $meta_query[] = array(
                'key' => 'job_location',
                'value' => sanitize_text_field($_GET['job_location']),
                'compare' => 'LIKE'
            );
        }

        // Remote work filter
        if (isset($_GET['remote_option']) && !empty($_GET['remote_option'])) {
            $meta_query[] = array(
                'key' => 'remote_option',
                'value' => sanitize_text_field($_GET['remote_option'])
            );
        }

        // Salary range filter
        if (isset($_GET['salary_min']) && !empty($_GET['salary_min'])) {
            $meta_query[] = array(
                'key' => 'salary_min',
                'value' => intval($_GET['salary_min']),
                'type' => 'NUMERIC',
                'compare' => '>='
            );
        }

        if (isset($_GET['salary_max']) && !empty($_GET['salary_max'])) {
            $meta_query[] = array(
                'key' => 'salary_max',
                'value' => intval($_GET['salary_max']),
                'type' => 'NUMERIC',
                'compare' => '<='
            );
        }

        // Apply tax query if exists
        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $query->set('tax_query', $tax_query);
        }

        // Apply meta query if exists
        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $query->set('meta_query', $meta_query);
        }

        // Search query
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $query->set('s', sanitize_text_field($_GET['s']));
        }

        // Order by
        if (isset($_GET['orderby'])) {
            switch ($_GET['orderby']) {
                case 'date':
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
                    break;
                case 'salary':
                    $query->set('meta_key', 'salary_min');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'DESC');
                    break;
                case 'title':
                    $query->set('orderby', 'title');
                    $query->set('order', 'ASC');
                    break;
            }
        }

        // Only show active jobs
        $meta_query[] = array(
            'key' => 'job_status',
            'value' => 'active'
        );

        return $query;
    }
}
add_action('pre_get_posts', 'giggajob_job_search_filters');

// Handle Employer Profile Submission
function giggajob_handle_employer_profile_submission() {
    // Check nonce
    if (!isset($_POST['profile_nonce']) || !wp_verify_nonce($_POST['profile_nonce'], 'employer_profile_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // Check if user is logged in and is an employer
    if (!is_user_logged_in() || !in_array('employer', wp_get_current_user()->roles)) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    // Prepare profile data
    $profile_data = array(
        'post_title' => sanitize_text_field($_POST['company_name']),
        'post_content' => wp_kses_post($_POST['company_description']),
        'post_type' => 'employer_profile',
        'post_status' => 'publish',
        'post_author' => get_current_user_id()
    );

    // Update or create profile
    if (isset($_POST['profile_id'])) {
        $profile_data['ID'] = intval($_POST['profile_id']);
        $profile_id = wp_update_post($profile_data);
    } else {
        $profile_id = wp_insert_post($profile_data);
    }

    if (is_wp_error($profile_id)) {
        wp_send_json_error(array('message' => 'Failed to save profile.'));
    }

    // Save meta fields
    $meta_fields = array(
        'company_name', 'company_website', 'company_size', 'founded_year',
        'company_email', 'phone_number', 'address', 'city', 'state',
        'country', 'postal_code'
    );

    foreach ($meta_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($profile_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Save social media links
    if (isset($_POST['social_media'])) {
        $social_media = array_map('esc_url_raw', $_POST['social_media']);
        update_post_meta($profile_id, 'social_media', $social_media);
    }

    // Handle logo upload
    if (!empty($_FILES['company_logo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('company_logo', $profile_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($profile_id, $attachment_id);
        }
    }

    // Set industries
    if (!empty($_POST['industry'])) {
        $industries = array_map('intval', $_POST['industry']);
        wp_set_object_terms($profile_id, $industries, 'industry');
    }

    wp_send_json_success(array('message' => 'Profile saved successfully!'));
}
add_action('wp_ajax_save_employer_profile', 'giggajob_handle_employer_profile_submission');

// Handle Resume Submission
function giggajob_handle_resume_submission() {
    // Check nonce
    if (!isset($_POST['resume_nonce']) || !wp_verify_nonce($_POST['resume_nonce'], 'resume_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // Check if user is logged in and is an employee
    if (!is_user_logged_in() || !in_array('employee', wp_get_current_user()->roles)) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    // Prepare resume data
    $resume_data = array(
        'post_title' => sanitize_text_field($_POST['full_name']),
        'post_content' => wp_kses_post($_POST['professional_summary']),
        'post_type' => 'resume',
        'post_status' => 'publish',
        'post_author' => get_current_user_id()
    );

    // Update or create resume
    if (isset($_POST['resume_id'])) {
        $resume_data['ID'] = intval($_POST['resume_id']);
        $resume_id = wp_update_post($resume_data);
    } else {
        $resume_id = wp_insert_post($resume_data);
    }

    if (is_wp_error($resume_id)) {
        wp_send_json_error(array('message' => 'Failed to save resume.'));
    }

    // Save basic information
    $basic_fields = array(
        'full_name', 'professional_title', 'email', 'phone',
        'location', 'website'
    );

    foreach ($basic_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($resume_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Save social media links
    if (isset($_POST['social_media'])) {
        $social_media = array_map('esc_url_raw', $_POST['social_media']);
        update_post_meta($resume_id, 'social_media', $social_media);
    }

    // Save skills
    if (isset($_POST['skills'])) {
        $skills = array_map('sanitize_text_field', $_POST['skills']);
        $skills = array_filter($skills); // Remove empty values
        update_post_meta($resume_id, 'skills', $skills);
    }

    // Save experience
    if (isset($_POST['experience'])) {
        $experience = array();
        foreach ($_POST['experience'] as $exp) {
            if (!empty($exp['title']) && !empty($exp['company'])) {
                $experience[] = array(
                    'title' => sanitize_text_field($exp['title']),
                    'company' => sanitize_text_field($exp['company']),
                    'start_date' => sanitize_text_field($exp['start_date']),
                    'end_date' => sanitize_text_field($exp['end_date']),
                    'current' => isset($exp['current']),
                    'description' => wp_kses_post($exp['description'])
                );
            }
        }
        update_post_meta($resume_id, 'experience', $experience);
    }

    // Save education
    if (isset($_POST['education'])) {
        $education = array();
        foreach ($_POST['education'] as $edu) {
            if (!empty($edu['degree']) && !empty($edu['institution'])) {
                $education[] = array(
                    'degree' => sanitize_text_field($edu['degree']),
                    'institution' => sanitize_text_field($edu['institution']),
                    'start_date' => sanitize_text_field($edu['start_date']),
                    'end_date' => sanitize_text_field($edu['end_date']),
                    'current' => isset($edu['current']),
                    'description' => wp_kses_post($edu['description'])
                );
            }
        }
        update_post_meta($resume_id, 'education', $education);
    }

    // Save certifications
    if (isset($_POST['certifications'])) {
        $certifications = array();
        foreach ($_POST['certifications'] as $cert) {
            if (!empty($cert['name']) && !empty($cert['organization'])) {
                $certifications[] = array(
                    'name' => sanitize_text_field($cert['name']),
                    'organization' => sanitize_text_field($cert['organization']),
                    'issue_date' => sanitize_text_field($cert['issue_date']),
                    'expiry_date' => sanitize_text_field($cert['expiry_date']),
                    'no_expiry' => isset($cert['no_expiry'])
                );
            }
        }
        update_post_meta($resume_id, 'certifications', $certifications);
    }

    // Save languages
    if (isset($_POST['languages'])) {
        $languages = array();
        foreach ($_POST['languages'] as $lang) {
            if (!empty($lang['name']) && !empty($lang['level'])) {
                $languages[] = array(
                    'name' => sanitize_text_field($lang['name']),
                    'level' => sanitize_text_field($lang['level'])
                );
            }
        }
        update_post_meta($resume_id, 'languages', $languages);
    }

    // Handle profile photo upload
    if (!empty($_FILES['profile_photo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('profile_photo', $resume_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($resume_id, $attachment_id);
        }
    }

    // Set industries
    if (!empty($_POST['industry'])) {
        $industries = array_map('intval', $_POST['industry']);
        wp_set_object_terms($resume_id, $industries, 'industry');
    }

    wp_send_json_success(array('message' => 'Resume saved successfully!'));
}
add_action('wp_ajax_save_resume', 'giggajob_handle_resume_submission');

// Add Meta Boxes for Resume
function giggajob_add_resume_meta_boxes() {
    add_meta_box(
        'resume_details',
        'Resume Details',
        'giggajob_resume_meta_box_callback',
        'resume',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'giggajob_add_resume_meta_boxes');

// Resume Meta Box Callback
function giggajob_resume_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('resume_meta_box', 'resume_meta_box_nonce');

    // Get existing values
    $resume_data = array(
        'full_name' => get_post_meta($post->ID, 'full_name', true),
        'professional_title' => get_post_meta($post->ID, 'professional_title', true),
        'email' => get_post_meta($post->ID, 'email', true),
        'phone' => get_post_meta($post->ID, 'phone', true),
        'location' => get_post_meta($post->ID, 'location', true),
        'website' => get_post_meta($post->ID, 'website', true),
        'skills' => get_post_meta($post->ID, 'skills', true),
        'experience' => get_post_meta($post->ID, 'experience', true),
        'education' => get_post_meta($post->ID, 'education', true),
        'certifications' => get_post_meta($post->ID, 'certifications', true),
        'languages' => get_post_meta($post->ID, 'languages', true),
        'social_media' => get_post_meta($post->ID, 'social_media', true)
    );
    ?>
    <div class="resume-meta-box">
        <style>
            .resume-meta-box .form-field { margin-bottom: 15px; }
            .resume-meta-box label { display: block; font-weight: bold; margin-bottom: 5px; }
            .resume-meta-box input[type="text"],
            .resume-meta-box input[type="email"],
            .resume-meta-box input[type="tel"],
            .resume-meta-box input[type="url"] { width: 100%; }
            .resume-meta-box .repeater-field { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; }
        </style>

        <!-- Basic Information -->
        <div class="form-field">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo esc_attr($resume_data['full_name']); ?>">
        </div>

        <div class="form-field">
            <label for="professional_title">Professional Title</label>
            <input type="text" id="professional_title" name="professional_title" value="<?php echo esc_attr($resume_data['professional_title']); ?>">
        </div>

        <div class="form-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo esc_attr($resume_data['email']); ?>">
        </div>

        <div class="form-field">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($resume_data['phone']); ?>">
        </div>

        <div class="form-field">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?php echo esc_attr($resume_data['location']); ?>">
        </div>

        <div class="form-field">
            <label for="website">Website</label>
            <input type="url" id="website" name="website" value="<?php echo esc_url($resume_data['website']); ?>">
        </div>

        <!-- Skills -->
        <div class="form-field">
            <label>Skills (one per line)</label>
            <textarea name="skills" rows="5"><?php 
                if (is_array($resume_data['skills'])) {
                    echo esc_textarea(implode("\n", $resume_data['skills']));
                }
            ?></textarea>
        </div>

        <!-- Social Media -->
        <div class="form-field">
            <label>Social Media Links</label>
            <?php 
            $social_platforms = array('linkedin', 'github');
            foreach ($social_platforms as $platform) {
                $value = isset($resume_data['social_media'][$platform]) ? $resume_data['social_media'][$platform] : '';
                ?>
                <p>
                    <label for="social_media_<?php echo $platform; ?>"><?php echo ucfirst($platform); ?></label>
                    <input type="url" id="social_media_<?php echo $platform; ?>" 
                           name="social_media[<?php echo $platform; ?>]" 
                           value="<?php echo esc_url($value); ?>">
                </p>
                <?php
            }
            ?>
        </div>
    </div>
    <?php
}

// Save Resume Meta Box Data
function giggajob_save_resume_meta_box($post_id) {
    // Security checks
    if (!isset($_POST['resume_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['resume_meta_box_nonce'], 'resume_meta_box') ||
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save basic fields
    $fields = array('full_name', 'professional_title', 'email', 'phone', 'location', 'website');
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Save skills
    if (isset($_POST['skills'])) {
        $skills = array_filter(array_map('trim', explode("\n", $_POST['skills'])));
        update_post_meta($post_id, 'skills', $skills);
    }

    // Save social media
    if (isset($_POST['social_media'])) {
        $social_media = array_map('esc_url_raw', $_POST['social_media']);
        update_post_meta($post_id, 'social_media', $social_media);
    }
}
add_action('save_post_resume', 'giggajob_save_resume_meta_box');

// Add Meta Boxes for Employer Profile
function giggajob_add_employer_profile_meta_boxes() {
    add_meta_box(
        'employer_profile_details',
        'Company Details',
        'giggajob_employer_profile_meta_box_callback',
        'employer_profile',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'giggajob_add_employer_profile_meta_boxes');

// Employer Profile Meta Box Callback
function giggajob_employer_profile_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('employer_profile_meta_box', 'employer_profile_meta_box_nonce');

    // Get existing values
    $profile_data = array(
        'company_name' => get_post_meta($post->ID, 'company_name', true),
        'company_website' => get_post_meta($post->ID, 'company_website', true),
        'company_size' => get_post_meta($post->ID, 'company_size', true),
        'founded_year' => get_post_meta($post->ID, 'founded_year', true),
        'company_email' => get_post_meta($post->ID, 'company_email', true),
        'phone_number' => get_post_meta($post->ID, 'phone_number', true),
        'address' => get_post_meta($post->ID, 'address', true),
        'city' => get_post_meta($post->ID, 'city', true),
        'state' => get_post_meta($post->ID, 'state', true),
        'country' => get_post_meta($post->ID, 'country', true),
        'postal_code' => get_post_meta($post->ID, 'postal_code', true),
        'social_media' => get_post_meta($post->ID, 'social_media', true)
    );
    ?>
    <div class="employer-profile-meta-box">
        <style>
            .employer-profile-meta-box .form-field { margin-bottom: 15px; }
            .employer-profile-meta-box label { display: block; font-weight: bold; margin-bottom: 5px; }
            .employer-profile-meta-box input[type="text"],
            .employer-profile-meta-box input[type="email"],
            .employer-profile-meta-box input[type="tel"],
            .employer-profile-meta-box input[type="url"],
            .employer-profile-meta-box select { width: 100%; }
        </style>

        <!-- Basic Information -->
        <div class="form-field">
            <label for="company_name">Company Name</label>
            <input type="text" id="company_name" name="company_name" value="<?php echo esc_attr($profile_data['company_name']); ?>">
        </div>

        <div class="form-field">
            <label for="company_website">Company Website</label>
            <input type="url" id="company_website" name="company_website" value="<?php echo esc_url($profile_data['company_website']); ?>">
        </div>

        <div class="form-field">
            <label for="company_size">Company Size</label>
            <select id="company_size" name="company_size">
                <option value="">Select Size</option>
                <?php
                $sizes = array(
                    '1-10' => '1-10 employees',
                    '11-50' => '11-50 employees',
                    '51-200' => '51-200 employees',
                    '201-500' => '201-500 employees',
                    '501-1000' => '501-1000 employees',
                    '1001-5000' => '1001-5000 employees',
                    '5000+' => '5000+ employees'
                );
                foreach ($sizes as $value => $label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($value),
                        selected($profile_data['company_size'], $value, false),
                        esc_html($label)
                    );
                }
                ?>
            </select>
        </div>

        <div class="form-field">
            <label for="founded_year">Founded Year</label>
            <input type="number" id="founded_year" name="founded_year" 
                   value="<?php echo esc_attr($profile_data['founded_year']); ?>"
                   min="1800" max="<?php echo date('Y'); ?>">
        </div>

        <!-- Contact Information -->
        <div class="form-field">
            <label for="company_email">Company Email</label>
            <input type="email" id="company_email" name="company_email" value="<?php echo esc_attr($profile_data['company_email']); ?>">
        </div>

        <div class="form-field">
            <label for="phone_number">Phone Number</label>
            <input type="tel" id="phone_number" name="phone_number" value="<?php echo esc_attr($profile_data['phone_number']); ?>">
        </div>

        <!-- Address -->
        <div class="form-field">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?php echo esc_attr($profile_data['address']); ?>">
        </div>

        <div class="form-field">
            <label for="city">City</label>
            <input type="text" id="city" name="city" value="<?php echo esc_attr($profile_data['city']); ?>">
        </div>

        <div class="form-field">
            <label for="state">State/Province</label>
            <input type="text" id="state" name="state" value="<?php echo esc_attr($profile_data['state']); ?>">
        </div>

        <div class="form-field">
            <label for="country">Country</label>
            <input type="text" id="country" name="country" value="<?php echo esc_attr($profile_data['country']); ?>">
        </div>

        <div class="form-field">
            <label for="postal_code">Postal Code</label>
            <input type="text" id="postal_code" name="postal_code" value="<?php echo esc_attr($profile_data['postal_code']); ?>">
        </div>

        <!-- Social Media -->
        <div class="form-field">
            <label>Social Media Links</label>
            <?php 
            $social_platforms = array('linkedin', 'twitter', 'facebook', 'instagram');
            foreach ($social_platforms as $platform) {
                $value = isset($profile_data['social_media'][$platform]) ? $profile_data['social_media'][$platform] : '';
                ?>
                <p>
                    <label for="social_media_<?php echo $platform; ?>"><?php echo ucfirst($platform); ?></label>
                    <input type="url" id="social_media_<?php echo $platform; ?>" 
                           name="social_media[<?php echo $platform; ?>]" 
                           value="<?php echo esc_url($value); ?>">
                </p>
                <?php
            }
            ?>
        </div>
    </div>
    <?php
}

// Save Employer Profile Meta Box Data
function giggajob_save_employer_profile_meta_box($post_id) {
    // Security checks
    if (!isset($_POST['employer_profile_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['employer_profile_meta_box_nonce'], 'employer_profile_meta_box') ||
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save basic fields
    $fields = array(
        'company_name', 'company_website', 'company_size', 'founded_year',
        'company_email', 'phone_number', 'address', 'city', 'state',
        'country', 'postal_code'
    );
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Save social media
    if (isset($_POST['social_media'])) {
        $social_media = array_map('esc_url_raw', $_POST['social_media']);
        update_post_meta($post_id, 'social_media', $social_media);
    }
}
add_action('save_post_employer_profile', 'giggajob_save_employer_profile_meta_box');

// Add Industry Taxonomy to Resume and Employer Profile in Admin
function giggajob_add_taxonomy_filters() {
    $taxonomies = array('industry');
    $post_types = array('resume', 'employer_profile');
    
    foreach ($post_types as $post_type) {
        foreach ($taxonomies as $taxonomy) {
            if (isset($_GET[$taxonomy]) && $taxonomy_obj = get_taxonomy($taxonomy)) {
                $term = get_term_by('slug', $_GET[$taxonomy], $taxonomy);
                if ($term) {
                    echo '<span class="subtitle">' . 
                         esc_html($taxonomy_obj->labels->singular_name . ': ' . $term->name) .
                         '</span>';
                }
            }
        }
    }
}
add_action('restrict_manage_posts', 'giggajob_add_taxonomy_filters');

// Add Meta Boxes for Jobs
function giggajob_add_job_meta_boxes() {
    add_meta_box(
        'job_details',
        'Job Details',
        'giggajob_job_meta_box_callback',
        'jobs',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'giggajob_add_job_meta_boxes');

// Job Meta Box Callback
function giggajob_job_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('job_meta_box', 'job_meta_box_nonce');

    // Get existing values
    $job_data = array(
        'company_name' => get_post_meta($post->ID, 'company_name', true),
        'job_type' => get_post_meta($post->ID, 'job_type', true),
        'job_location' => get_post_meta($post->ID, 'job_location', true),
        'remote_option' => get_post_meta($post->ID, 'remote_option', true),
        'salary_type' => get_post_meta($post->ID, 'salary_type', true),
        'salary_min' => get_post_meta($post->ID, 'salary_min', true),
        'salary_max' => get_post_meta($post->ID, 'salary_max', true),
        'salary_period' => get_post_meta($post->ID, 'salary_period', true),
        'job_status' => get_post_meta($post->ID, 'job_status', true)
    );
    ?>
    <div class="job-meta-box">
        <style>
            .job-meta-box .form-field { margin-bottom: 15px; }
            .job-meta-box label { display: block; font-weight: bold; margin-bottom: 5px; }
            .job-meta-box input[type="text"],
            .job-meta-box input[type="number"],
            .job-meta-box select { width: 100%; }
            .job-meta-box .salary-group { display: flex; gap: 10px; align-items: flex-end; }
            .job-meta-box .salary-group > div { flex: 1; }
        </style>

        <!-- Company Name -->
        <div class="form-field">
            <label for="company_name">Company Name</label>
            <input type="text" id="company_name" name="company_name" 
                   value="<?php echo esc_attr($job_data['company_name']); ?>">
        </div>

        <!-- Job Type -->
        <div class="form-field">
            <label for="job_type">Job Type</label>
            <select id="job_type" name="job_type">
                <option value="">Select Job Type</option>
                <?php
                $job_types = array(
                    'full-time' => 'Full Time',
                    'part-time' => 'Part Time',
                    'contract' => 'Contract',
                    'temporary' => 'Temporary',
                    'internship' => 'Internship'
                );
                foreach ($job_types as $value => $label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($value),
                        selected($job_data['job_type'], $value, false),
                        esc_html($label)
                    );
                }
                ?>
            </select>
        </div>

        <!-- Location -->
        <div class="form-field">
            <label for="job_location">Location</label>
            <input type="text" id="job_location" name="job_location" 
                   value="<?php echo esc_attr($job_data['job_location']); ?>">
        </div>

        <!-- Remote Work Options -->
        <div class="form-field">
            <label for="remote_option">Remote Work Option</label>
            <select id="remote_option" name="remote_option">
                <option value="">Select Remote Option</option>
                <?php
                $remote_options = array(
                    'no' => 'No Remote Work',
                    'hybrid' => 'Hybrid',
                    'yes' => 'Fully Remote'
                );
                foreach ($remote_options as $value => $label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($value),
                        selected($job_data['remote_option'], $value, false),
                        esc_html($label)
                    );
                }
                ?>
            </select>
        </div>

        <!-- Salary Information -->
        <div class="form-field">
            <label for="salary_type">Salary Information</label>
            <select id="salary_type" name="salary_type">
                <option value="">Select Salary Type</option>
                <?php
                $salary_types = array(
                    'range' => 'Salary Range',
                    'fixed' => 'Fixed Amount',
                    'exempt' => 'Legal Exemption'
                );
                foreach ($salary_types as $value => $label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($value),
                        selected($job_data['salary_type'], $value, false),
                        esc_html($label)
                    );
                }
                ?>
            </select>
            
            <div class="salary-group mt-2">
                <div>
                    <label for="salary_min">Minimum Salary</label>
                    <input type="number" id="salary_min" name="salary_min" 
                           value="<?php echo esc_attr($job_data['salary_min']); ?>">
                </div>
                <div>
                    <label for="salary_max">Maximum Salary</label>
                    <input type="number" id="salary_max" name="salary_max" 
                           value="<?php echo esc_attr($job_data['salary_max']); ?>">
                </div>
                <div>
                    <label for="salary_period">Period</label>
                    <select id="salary_period" name="salary_period">
                        <?php
                        $periods = array(
                            'year' => 'Per Year',
                            'month' => 'Per Month',
                            'hour' => 'Per Hour'
                        );
                        foreach ($periods as $value => $label) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($value),
                                selected($job_data['salary_period'], $value, false),
                                esc_html($label)
                            );
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Job Status -->
        <div class="form-field">
            <label for="job_status">Job Status</label>
            <select id="job_status" name="job_status">
                <option value="">Select Status</option>
                <?php
                $statuses = array(
                    'active' => 'Active',
                    'draft' => 'Draft',
                    'expired' => 'Expired'
                );
                foreach ($statuses as $value => $label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($value),
                        selected($job_data['job_status'], $value, false),
                        esc_html($label)
                    );
                }
                ?>
            </select>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        function toggleSalaryFields() {
            var salaryType = $('#salary_type').val();
            var $salaryGroup = $('.salary-group');
            
            if (salaryType === 'exempt') {
                $salaryGroup.hide();
            } else {
                $salaryGroup.show();
                if (salaryType === 'fixed') {
                    $('#salary_max').closest('div').hide();
                } else {
                    $('#salary_max').closest('div').show();
                }
            }
        }

        $('#salary_type').on('change', toggleSalaryFields);
        toggleSalaryFields();
    });
    </script>
    <?php
}

// Save Job Meta Box Data
function giggajob_save_job_meta_box($post_id) {
    // Security checks
    if (!isset($_POST['job_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['job_meta_box_nonce'], 'job_meta_box') ||
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save basic fields
    $fields = array(
        'company_name', 'job_type', 'job_location', 'remote_option',
        'salary_type', 'salary_min', 'salary_max', 'salary_period', 'job_status'
    );

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post_jobs', 'giggajob_save_job_meta_box');

/**
 * Handle application actions (reject, cancel interview)
 */
function handle_application_action() {
    error_log('=== START Application Action Handler ===');
    
    // Check nonce and user
    if (!check_ajax_referer('application_action_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Permission denied'));
        return;
    }

    $current_user = wp_get_current_user();
    if (!in_array('employer', $current_user->roles)) {
        wp_send_json_error(array('message' => 'Permission denied'));
        return;
    }

    // Get and validate application ID
    $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
    if (!$application_id) {
        wp_send_json_error(array('message' => 'Invalid application ID'));
        return;
    }

    // Get and validate action
    $action = isset($_POST['application_action']) ? sanitize_text_field($_POST['application_action']) : '';
    if (!in_array($action, array('reject', 'cancel_interview'))) {
        wp_send_json_error(array('message' => 'Invalid action'));
        return;
    }

    // Get the job ID associated with this application
    $job_id = get_post_meta($application_id, 'job_id', true);
    $job = get_post($job_id);

    // Verify user owns this job
    if (!$job || $job->post_author != $current_user->ID) {
        wp_send_json_error(array('message' => 'Permission denied'));
        return;
    }

    error_log("Processing application action: $action for application: $application_id");

    // Process the action
    switch ($action) {
        case 'reject':
            update_post_meta($application_id, 'status', 'rejected');
            // This will trigger the status change notification
            break;
        case 'cancel_interview':
            update_post_meta($application_id, 'status', 'pending');
            delete_post_meta($application_id, 'interview_date');
            delete_post_meta($application_id, 'interview_time');
            delete_post_meta($application_id, 'interview_location');
            delete_post_meta($application_id, 'interview_message');
            // This will trigger the status change notification
            break;
    }

    error_log('=== END Application Action Handler ===');
    wp_send_json_success(array('message' => 'Action completed successfully'));
}
add_action('wp_ajax_handle_application_action', 'handle_application_action');

// Handle Interview Scheduling
add_action('wp_ajax_handle_interview_schedule', 'giggajob_handle_interview_schedule');
function giggajob_handle_interview_schedule() {
    error_log('=== START Interview Schedule Handler ===');
    
    // Debug output
    error_log('Interview Schedule Handler - POST data: ' . print_r($_POST, true));
    error_log('Interview Schedule Handler - Nonce: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'not set'));
    
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'giggajob_ajax_nonce')) {
        error_log('Interview Schedule Handler - Nonce verification failed');
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // Check if user is logged in and is an employer
    if (!is_user_logged_in() || !in_array('employer', wp_get_current_user()->roles)) {
        error_log('Interview Schedule Handler - Permission denied: User not logged in or not an employer');
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    // Validate required fields
    $required_fields = array(
        'application_id' => 'Application ID',
        'interview_date' => 'Interview Date',
        'interview_time' => 'Interview Time',
        'interview_location' => 'Interview Location'
    );

    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            error_log('Interview Schedule Handler - Missing required field: ' . $field);
            wp_send_json_error(array('message' => $label . ' is required.'));
        }
    }

    $application_id = intval($_POST['application_id']);
    
    // Get the application
    $application = get_post($application_id);
    if (!$application || $application->post_type !== 'job_application') {
        error_log('Interview Schedule Handler - Invalid application ID: ' . $application_id);
        wp_send_json_error(array('message' => 'Invalid application.'));
    }

    // Get the associated job
    $job_id = get_post_meta($application_id, 'job_id', true);
    $job = get_post($job_id);

    // Verify ownership
    if (!$job || $job->post_author != get_current_user_id()) {
        error_log('Interview Schedule Handler - Permission denied: Job ownership verification failed');
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    // Update application status and interview details
    update_post_meta($application_id, 'status', 'interview_scheduled');
    update_post_meta($application_id, 'interview_date', sanitize_text_field($_POST['interview_date']));
    update_post_meta($application_id, 'interview_time', sanitize_text_field($_POST['interview_time']));
    update_post_meta($application_id, 'interview_location', sanitize_text_field($_POST['interview_location']));
    
    if (!empty($_POST['interview_message'])) {
        update_post_meta($application_id, 'interview_message', sanitize_textarea_field($_POST['interview_message']));
    }

    error_log('Interview details saved, triggering notification...');
    
    // This will trigger the meta update hook which sends the email
    error_log('=== END Interview Schedule Handler ===');
    
    wp_send_json_success(array('message' => 'Interview scheduled successfully.'));
}

// Handle Notification Preferences Update
add_action('wp_ajax_update_notification_preferences', 'giggajob_update_notification_preferences');
function giggajob_update_notification_preferences() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'update_notification_preferences')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    // Parse the form data
    parse_str($_POST['preferences'], $preferences);
    
    // Initialize notifications array
    $notifications = array();
    
    // Get user role to determine which preferences to save
    $user = wp_get_current_user();
    if (in_array('employer', $user->roles)) {
        $default_prefs = array(
            'new_application' => false,
            'application_withdrawn' => false,
            'resume_updated' => false
        );
    } else {
        $default_prefs = array(
            'application_status' => false,
            'interview_scheduled' => false,
            'job_recommendations' => false,
            'saved_job_expiring' => false
        );
    }
    
    // Get submitted notifications or use defaults
    if (isset($preferences['notifications']) && is_array($preferences['notifications'])) {
        foreach ($default_prefs as $key => $default) {
            $notifications[$key] = isset($preferences['notifications'][$key]) ? true : false;
        }
    } else {
        $notifications = $default_prefs;
    }

    // Save preferences
    $result = update_user_meta(get_current_user_id(), 'notification_preferences', $notifications);

    if ($result !== false) {
        wp_send_json_success(array('message' => 'Notification preferences updated successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to update notification preferences.'));
    }
}

// Handle Password Change
add_action('wp_ajax_change_user_password', 'giggajob_change_user_password');
function giggajob_change_user_password() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'change_password')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    $current_user = wp_get_current_user();
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';

    // Verify current password
    if (!wp_check_password($current_password, $current_user->user_pass, $current_user->ID)) {
        wp_send_json_error(array('message' => 'Current password is incorrect.'));
    }

    // Validate new password
    if (strlen($new_password) < 8) {
        wp_send_json_error(array('message' => 'Password must be at least 8 characters long.'));
    }

    if (!preg_match('/[A-Z]/', $new_password)) {
        wp_send_json_error(array('message' => 'Password must contain at least one uppercase letter.'));
    }

    if (!preg_match('/[a-z]/', $new_password)) {
        wp_send_json_error(array('message' => 'Password must contain at least one lowercase letter.'));
    }

    if (!preg_match('/[0-9]/', $new_password)) {
        wp_send_json_error(array('message' => 'Password must contain at least one number.'));
    }

    // Verify passwords match
    if ($new_password !== $_POST['confirm_password']) {
        wp_send_json_error(array('message' => 'Passwords do not match.'));
    }

    // Update password
    wp_set_password($new_password, $current_user->ID);

    // Log the user back in
    wp_clear_auth_cookie();
    wp_set_current_user($current_user->ID);
    wp_set_auth_cookie($current_user->ID, true);

    wp_send_json_success(array('message' => 'Password updated successfully.'));
}

// Handle Job Application Submission
add_action('wp_ajax_submit_job_application', 'giggajob_handle_job_application');
function giggajob_handle_job_application() {
    // Check nonce
    if (!isset($_POST['job_application_nonce']) || !wp_verify_nonce($_POST['job_application_nonce'], 'submit_job_application')) {
        wp_send_json_error(['message' => 'Security check failed.']);
    }

    // Check if user is logged in and is an employee
    if (!is_user_logged_in() || !in_array('employee', wp_get_current_user()->roles)) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }

    // Validate required fields
    if (empty($_POST['job_id']) || empty($_POST['cover_letter']) || empty($_POST['resume_id'])) {
        wp_send_json_error(['message' => 'Please fill in all required fields.']);
    }

    $job_id = intval($_POST['job_id']);
    $resume_id = intval($_POST['resume_id']);
    $cover_letter = wp_kses_post($_POST['cover_letter']);

    // Verify job exists and is published
    $job = get_post($job_id);
    if (!$job || $job->post_type !== 'jobs' || $job->post_status !== 'publish') {
        wp_send_json_error(['message' => 'Invalid job posting.']);
    }

    // Verify resume exists and belongs to user
    $resume = get_post($resume_id);
    if (!$resume) {
        wp_send_json_error(['message' => 'Resume not found.']);
    }
    
    if ($resume->post_type !== 'resume') {
        wp_send_json_error(['message' => 'Invalid resume type.']);
    }
    
    if ($resume->post_author != get_current_user_id()) {
        wp_send_json_error(['message' => 'This resume does not belong to you.']);
    }

    // Check if already applied
    $existing_application = get_posts([
        'post_type' => 'job_application',
        'author' => get_current_user_id(),
        'meta_query' => [
            [
                'key' => 'job_id',
                'value' => $job_id
            ]
        ],
        'posts_per_page' => 1
    ]);

    if (!empty($existing_application)) {
        wp_send_json_error(['message' => 'You have already applied for this job.']);
    }

    // Create application
    $application_data = [
        'post_title' => sprintf('Application for %s by %s', 
                              $job->post_title, 
                              wp_get_current_user()->display_name),
        'post_content' => $cover_letter,
        'post_status' => 'publish',
        'post_type' => 'job_application',
        'post_author' => get_current_user_id()
    ];

    $application_id = wp_insert_post($application_data);

    if (is_wp_error($application_id)) {
        wp_send_json_error(['message' => 'Failed to submit application.']);
    }

    // Save application meta
    update_post_meta($application_id, 'job_id', $job_id);
    update_post_meta($application_id, 'resume_id', $resume_id);
    update_post_meta($application_id, 'status', 'pending');
    update_post_meta($application_id, 'application_date', current_time('mysql'));
    update_post_meta($application_id, 'applicant_id', get_current_user_id());

    error_log("Job application created successfully - ID: $application_id");

    // Send notification to employer directly
    giggajob_send_application_notification($application_id);

    wp_send_json_success(['message' => 'Application submitted successfully!']);
}

// Handle User Registration
add_action('wp_ajax_nopriv_register_user', 'giggajob_register_user');
add_action('wp_ajax_register_user', 'giggajob_register_user');
function giggajob_register_user() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'employee_registration_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // Parse form data
    parse_str($_POST['form_data'], $form_data);
    
    // Determine role
    $role = isset($form_data['role']) ? $form_data['role'] : 'employee';
    if (!in_array($role, array('employee', 'employer'))) {
        wp_send_json_error(array('message' => 'Invalid role specified.'));
    }

    // Validate required fields
    $required_fields = array('username', 'email', 'password', 'confirm_password', 'first_name', 'last_name');
    if ($role === 'employer') {
        $required_fields[] = 'company_name';
    }

    foreach ($required_fields as $field) {
        if (empty($form_data[$field])) {
            wp_send_json_error(array('message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.'));
        }
    }

    // Validate email
    if (!is_email($form_data['email'])) {
        wp_send_json_error(array('message' => 'Please enter a valid email address.'));
    }

    // Check if email exists
    if (email_exists($form_data['email'])) {
        wp_send_json_error(array('message' => 'This email address is already registered.'));
    }

    // Check if username exists
    if (username_exists($form_data['username'])) {
        wp_send_json_error(array('message' => 'This username is already taken.'));
    }

    // Validate password
    if (strlen($form_data['password']) < 8) {
        wp_send_json_error(array('message' => 'Password must be at least 8 characters long.'));
    }

    if (!preg_match('/[A-Z]/', $form_data['password'])) {
        wp_send_json_error(array('message' => 'Password must contain at least one uppercase letter.'));
    }

    if (!preg_match('/[a-z]/', $form_data['password'])) {
        wp_send_json_error(array('message' => 'Password must contain at least one lowercase letter.'));
    }

    if (!preg_match('/[0-9]/', $form_data['password'])) {
        wp_send_json_error(array('message' => 'Password must contain at least one number.'));
    }

    // Verify passwords match
    if ($form_data['password'] !== $form_data['confirm_password']) {
        wp_send_json_error(array('message' => 'Passwords do not match.'));
    }

    // Create user
    $userdata = array(
        'user_login' => $form_data['username'],
        'user_email' => $form_data['email'],
        'user_pass' => $form_data['password'],
        'first_name' => $form_data['first_name'],
        'last_name' => $form_data['last_name'],
        'role' => $role,
        'show_admin_bar_front' => false
    );

    $user_id = wp_insert_user($userdata);

    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
    }

    // Add company name for employers
    if ($role === 'employer' && !empty($form_data['company_name'])) {
        update_user_meta($user_id, 'company_name', sanitize_text_field($form_data['company_name']));
    }

    // Log the user in
    wp_clear_auth_cookie();
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    // Welcome email will be sent by the giggajob_send_welcome_email function hooked to 'user_register'

    // Return success
    wp_send_json_success(array(
        'message' => 'Registration successful!',
        'redirect_url' => home_url($role === 'employer' ? '/employer-dashboard/' : '/employee-dashboard/')
    ));
}

// Add Email Templates Settings Page
add_action('admin_menu', 'giggajob_add_email_templates_page');
function giggajob_add_email_templates_page() {
    add_menu_page(
        'Email Templates',
        'Email Templates',
        'manage_options',
        'giggajob-email-templates',
        'giggajob_email_templates_page',
        'dashicons-email',
        30
    );
}

function giggajob_email_templates_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings if form is submitted
    if (isset($_POST['giggajob_email_templates_nonce']) && 
        wp_verify_nonce($_POST['giggajob_email_templates_nonce'], 'giggajob_save_email_templates')) {
        
        $templates = array(
            'new_application' => array(
                'subject' => sanitize_text_field($_POST['new_application_subject']),
                'body' => wp_kses_post($_POST['new_application_body'])
            ),
            'interview_scheduled' => array(
                'subject' => sanitize_text_field($_POST['interview_scheduled_subject']),
                'body' => wp_kses_post($_POST['interview_scheduled_body'])
            ),
            'application_status' => array(
                'subject' => sanitize_text_field($_POST['application_status_subject']),
                'body' => wp_kses_post($_POST['application_status_body'])
            ),
            'welcome_employee' => array(
                'subject' => sanitize_text_field($_POST['welcome_employee_subject']),
                'body' => wp_kses_post($_POST['welcome_employee_body'])
            ),
            'welcome_employer' => array(
                'subject' => sanitize_text_field($_POST['welcome_employer_subject']),
                'body' => wp_kses_post($_POST['welcome_employer_body'])
            )
        );

        update_option('giggajob_email_templates', $templates);
        echo '<div class="notice notice-success"><p>Email templates saved successfully!</p></div>';
    }

    // Get current templates
    $templates = get_option('giggajob_email_templates', array(
        'new_application' => array(
            'subject' => 'New Job Application Received: {job_title}',
            'body' => "Dear {employer_name},\n\nA new application has been received for your job posting: {job_title}\n\nCandidate: {applicant_name}\nEmail: {applicant_email}\n\nYou can view the full application in your dashboard: {application_url}\n\nBest regards,\n{site_name}"
        ),
        'interview_scheduled' => array(
            'subject' => 'Interview Scheduled: {job_title}',
            'body' => "Dear {applicant_name},\n\nAn interview has been scheduled for your application to {job_title} at {company_name}.\n\nDate: {interview_date}\nTime: {interview_time}\nLocation: {interview_location}\n\n{interview_message}\n\nBest regards,\n{company_name}"
        ),
        'application_status' => array(
            'subject' => 'Application Status Update: {job_title}',
            'body' => "Dear {applicant_name},\n\nThere has been an update to your application for {job_title} at {company_name}.\n\nStatus: {status}\n\nYou can view the details in your dashboard: {application_url}\n\nBest regards,\n{company_name}"
        ),
        'welcome_employee' => array(
            'subject' => 'Welcome to {site_name}!',
            'body' => "Welcome to {site_name}!\n\nYour job seeker account has been created successfully.\n\nUsername: {username}\n\nYou can log in at: {login_url}\n\nBest regards,\n{site_name} Team"
        ),
        'welcome_employer' => array(
            'subject' => 'Welcome to {site_name}!',
            'body' => "Welcome to {site_name}!\n\nYour employer account has been created successfully.\n\nUsername: {username}\nCompany: {company_name}\n\nYou can log in at: {login_url}\n\nBest regards,\n{site_name} Team"
        )
    ));
    ?>
    <div class="wrap">
        <h1>Email Templates</h1>
        <form method="post" action="">
            <?php wp_nonce_field('giggajob_save_email_templates', 'giggajob_email_templates_nonce'); ?>
            
            <div class="email-templates-container">
                <!-- New Application Template -->
                <h2>New Application Email (To Employer)</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Subject</th>
                        <td>
                            <input type="text" name="new_application_subject" class="large-text" 
                                   value="<?php echo esc_attr($templates['new_application']['subject']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Body</th>
                        <td>
                            <textarea name="new_application_body" rows="10" class="large-text"><?php 
                                echo esc_textarea($templates['new_application']['body']); 
                            ?></textarea>
                            <p class="description">
                                Available variables: {employer_name}, {job_title}, {applicant_name}, 
                                {applicant_email}, {application_url}, {site_name}
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Interview Scheduled Template -->
                <h2>Interview Scheduled Email (To Applicant)</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Subject</th>
                        <td>
                            <input type="text" name="interview_scheduled_subject" class="large-text" 
                                   value="<?php echo esc_attr($templates['interview_scheduled']['subject']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Body</th>
                        <td>
                            <textarea name="interview_scheduled_body" rows="10" class="large-text"><?php 
                                echo esc_textarea($templates['interview_scheduled']['body']); 
                            ?></textarea>
                            <p class="description">
                                Available variables: {applicant_name}, {job_title}, {company_name}, 
                                {interview_date}, {interview_time}, {interview_location}, {interview_message}
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Application Status Template -->
                <h2>Application Status Update Email (To Applicant)</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Subject</th>
                        <td>
                            <input type="text" name="application_status_subject" class="large-text" 
                                   value="<?php echo esc_attr($templates['application_status']['subject']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Body</th>
                        <td>
                            <textarea name="application_status_body" rows="10" class="large-text"><?php 
                                echo esc_textarea($templates['application_status']['body']); 
                            ?></textarea>
                            <p class="description">
                                Available variables: {applicant_name}, {job_title}, {company_name}, 
                                {status}, {application_url}
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Welcome Employee Template -->
                <h2>Welcome Email (To New Employee)</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Subject</th>
                        <td>
                            <input type="text" name="welcome_employee_subject" class="large-text" 
                                   value="<?php echo esc_attr($templates['welcome_employee']['subject']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Body</th>
                        <td>
                            <textarea name="welcome_employee_body" rows="10" class="large-text"><?php 
                                echo esc_textarea($templates['welcome_employee']['body']); 
                            ?></textarea>
                            <p class="description">
                                Available variables: {username}, {site_name}, {login_url}
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Welcome Employer Template -->
                <h2>Welcome Email (To New Employer)</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Subject</th>
                        <td>
                            <input type="text" name="welcome_employer_subject" class="large-text" 
                                   value="<?php echo esc_attr($templates['welcome_employer']['subject']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Body</th>
                        <td>
                            <textarea name="welcome_employer_body" rows="10" class="large-text"><?php 
                                echo esc_textarea($templates['welcome_employer']['body']); 
                            ?></textarea>
                            <p class="description">
                                Available variables: {username}, {company_name}, {site_name}, {login_url}
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button('Save Email Templates'); ?>
        </form>
    </div>
    <?php
}

// Email Notification Functions
function giggajob_send_email_notification($template_key, $to_email, $variables = array()) {
    error_log('=== START Email Notification Process ===');
    error_log("Template: $template_key");
    error_log("To: $to_email");
    error_log("Variables: " . print_r($variables, true));
    
    // Get email templates
    $templates = get_option('giggajob_email_templates');
    if (!isset($templates[$template_key])) {
        error_log("ERROR: Email template not found: $template_key");
        return false;
    }

    // Get template
    $template = $templates[$template_key];
    error_log("Template found: " . print_r($template, true));
    
    // Add common variables
    $variables['site_name'] = get_bloginfo('name');
    $variables['login_url'] = wp_login_url();
    
    // Replace variables in subject and body
    $subject = $template['subject'];
    $body = $template['body'];
    
    foreach ($variables as $key => $value) {
        $subject = str_replace('{' . $key . '}', $value, $subject);
        $body = str_replace('{' . $key . '}', $value, $body);
    }
    
    error_log("Prepared email content:");
    error_log("Subject: $subject");
    error_log("Body: $body");
    
    // Set up email headers
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    );
    
    error_log("Headers: " . print_r($headers, true));
    
    // Send email
    error_log("Attempting wp_mail()...");
    $sent = wp_mail($to_email, $subject, $body, $headers);
    
    // Log result
    if (!$sent) {
        error_log("ERROR: Failed to send email");
        error_log("PHP mailer error: " . print_r(error_get_last(), true));
        
        // Get WordPress error if available
        global $phpmailer;
        if (isset($phpmailer) && is_object($phpmailer)) {
            error_log("PHPMailer error: " . $phpmailer->ErrorInfo);
        }
    } else {
        error_log("SUCCESS: Email sent successfully");
    }
    
    error_log('=== END Email Notification Process ===');
    return $sent;
}

// Send welcome email on registration
function giggajob_send_welcome_email($user_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) return;

    $variables = array(
        'username' => $user->user_login
    );

    if (in_array('employer', $user->roles)) {
        $company_name = get_user_meta($user_id, 'company_name', true);
        $variables['company_name'] = $company_name;
        giggajob_send_email_notification('welcome_employer', $user->user_email, $variables);
    } else {
        giggajob_send_email_notification('welcome_employee', $user->user_email, $variables);
    }
}
add_action('user_register', 'giggajob_send_welcome_email');

// Send new application notification
function giggajob_send_application_notification($application_id) {
    error_log('=== START Application Notification ===');
    error_log("Processing application notification for application ID: $application_id");
    
    $application = get_post($application_id);
    if (!$application || $application->post_type !== 'job_application') {
        error_log("Invalid application or wrong post type");
        return;
    }

    $job_id = get_post_meta($application_id, 'job_id', true);
    $job = get_post($job_id);
    if (!$job) {
        error_log("Job not found for application");
        return;
    }

    $employer = get_user_by('id', $job->post_author);
    $applicant = get_user_by('id', $application->post_author);
    
    error_log("Application details - Job: {$job->post_title}, Employer: {$employer->user_email}, Applicant: {$applicant->user_email}");
    
    // Check employer notification preferences
    $employer_preferences = get_user_meta($employer->ID, 'notification_preferences', true);
    error_log("Employer preferences: " . print_r($employer_preferences, true));
    
    if (!empty($employer_preferences['new_application'])) {
        $variables = array(
            'employer_name' => $employer->display_name,
            'job_title' => $job->post_title,
            'applicant_name' => $applicant->display_name,
            'applicant_email' => $applicant->user_email,
            'application_url' => home_url('/employer-dashboard/?tab=applications')
        );
        
        giggajob_send_email_notification('new_application', $employer->user_email, $variables);
    } else {
        error_log("Employer has disabled new application notifications");
    }
    
    error_log('=== END Application Notification ===');
}

// Send interview scheduled notification
function giggajob_send_interview_notification($application_id) {
    error_log('=== START Interview Notification ===');
    error_log("Application ID: $application_id");
    
    $application = get_post($application_id);
    if (!$application || $application->post_type !== 'job_application') {
        error_log("ERROR: Invalid application or wrong post type");
        error_log("Application: " . print_r($application, true));
        return;
    }

    $job_id = get_post_meta($application_id, 'job_id', true);
    $job = get_post($job_id);
    if (!$job) {
        error_log("ERROR: Job not found for application");
        return;
    }

    $applicant = get_user_by('id', $application->post_author);
    error_log("Applicant: " . print_r($applicant, true));
    
    // Check applicant notification preferences
    $applicant_preferences = get_user_meta($applicant->ID, 'notification_preferences', true);
    error_log("Applicant preferences: " . print_r($applicant_preferences, true));
    
    if (!empty($applicant_preferences['interview_scheduled'])) {
        $variables = array(
            'applicant_name' => $applicant->display_name,
            'job_title' => $job->post_title,
            'company_name' => get_post_meta($job_id, 'company_name', true),
            'interview_date' => get_post_meta($application_id, 'interview_date', true),
            'interview_time' => get_post_meta($application_id, 'interview_time', true),
            'interview_location' => get_post_meta($application_id, 'interview_location', true),
            'interview_message' => get_post_meta($application_id, 'interview_message', true)
        );
        
        error_log("Sending interview notification with variables: " . print_r($variables, true));
        giggajob_send_email_notification('interview_scheduled', $applicant->user_email, $variables);
    } else {
        error_log("Notification skipped: Applicant has disabled interview notifications");
    }
    
    error_log('=== END Interview Notification ===');
}

// Send application status update notification
function giggajob_send_status_update_notification($application_id) {
    error_log('=== START Status Update Notification ===');
    error_log("Processing status update notification for application ID: $application_id");
    
    $application = get_post($application_id);
    if (!$application || $application->post_type !== 'job_application') {
        error_log("Invalid application or wrong post type");
        return;
    }

    $job_id = get_post_meta($application_id, 'job_id', true);
    $job = get_post($job_id);
    if (!$job) {
        error_log("Job not found for application");
        return;
    }

    $applicant = get_user_by('id', $application->post_author);
    error_log("Applicant: " . print_r($applicant, true));
    
    // Check applicant notification preferences
    $applicant_preferences = get_user_meta($applicant->ID, 'notification_preferences', true);
    error_log("Applicant preferences: " . print_r($applicant_preferences, true));
    
    if (!empty($applicant_preferences['application_status'])) {
        $status = get_post_meta($application_id, 'status', true);
        $variables = array(
            'applicant_name' => $applicant->display_name,
            'job_title' => $job->post_title,
            'company_name' => get_post_meta($job_id, 'company_name', true),
            'status' => ucfirst($status),
            'application_url' => home_url('/employee-dashboard/?tab=applications')
        );
        
        error_log("Sending status update notification with variables: " . print_r($variables, true));
        giggajob_send_email_notification('application_status', $applicant->user_email, $variables);
    } else {
        error_log("Applicant has disabled status update notifications");
    }
    
    error_log('=== END Status Update Notification ===');
}

// Hook into application status changes
function giggajob_handle_application_status_change($meta_id, $object_id, $meta_key, $_meta_value) {
    error_log('=== START Status Change Handler ===');
    error_log("Meta ID: $meta_id");
    error_log("Object ID: $object_id");
    error_log("Meta Key: $meta_key");
    error_log("Meta Value: $_meta_value");
    
    if ($meta_key === 'status') {
        if ($_meta_value === 'interview_scheduled') {
            error_log('Triggering interview notification...');
            giggajob_send_interview_notification($object_id);
        } else {
            error_log('Triggering status update notification...');
            giggajob_send_status_update_notification($object_id);
        }
    }
    
    error_log('=== END Status Change Handler ===');
}
add_action('updated_post_meta', 'giggajob_handle_application_status_change', 10, 4);

// Add Email Test Page
add_action('admin_menu', 'giggajob_add_email_test_page');
function giggajob_add_email_test_page() {
    add_submenu_page(
        'giggajob-email-templates',
        'Email Test',
        'Email Test',
        'manage_options',
        'giggajob-email-test',
        'giggajob_email_test_page'
    );
}

function giggajob_email_test_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle test email submission
    if (isset($_POST['test_email_nonce']) && 
        wp_verify_nonce($_POST['test_email_nonce'], 'send_test_email')) {
        
        $to = sanitize_email($_POST['test_email']);
        $subject = 'GiggaJob Email Test';
        $message = 'This is a test email from your GiggaJob website. If you receive this, email sending is working correctly.';
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        // Test wp_mail function
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            echo '<div class="notice notice-success"><p>Test email sent successfully! Please check your inbox (and spam folder).</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Failed to send test email. Please check your server\'s mail configuration.</p></div>';
            error_log('Test email failed. wp_mail() error info: ' . print_r(error_get_last(), true));
        }
    }

    // Display PHP mail configuration
    $mail_config = array(
        'SMTP' => ini_get('SMTP'),
        'smtp_port' => ini_get('smtp_port'),
        'sendmail_path' => ini_get('sendmail_path'),
        'sendmail_from' => ini_get('sendmail_from'),
        'wp_mail_smtp_plugin' => is_plugin_active('wp-mail-smtp/wp_mail_smtp.php') ? 'Active' : 'Not Active'
    );
    ?>
    <div class="wrap">
        <h1>Email Test</h1>
        
        <div class="card">
            <h2>Mail Configuration</h2>
            <table class="form-table">
                <?php foreach ($mail_config as $key => $value): ?>
                <tr>
                    <th scope="row"><?php echo esc_html($key); ?></th>
                    <td><?php echo esc_html($value ?: 'Not set'); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card">
            <h2>Send Test Email</h2>
            <form method="post" action="">
                <?php wp_nonce_field('send_test_email', 'test_email_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Email Address</th>
                        <td>
                            <input type="email" name="test_email" class="regular-text" 
                                   value="<?php echo esc_attr(get_option('admin_email')); ?>" required>
                            <p class="description">Enter the email address where you want to send the test email.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Send Test Email'); ?>
            </form>
        </div>

        <div class="card">
            <h2>Troubleshooting Tips</h2>
            <ol>
                <li>If emails are not being sent, consider installing a SMTP plugin like "WP Mail SMTP".</li>
                <li>Check your server's mail logs for any errors.</li>
                <li>Verify that your hosting provider allows sending emails.</li>
                <li>Make sure your sender email domain has proper SPF and DKIM records.</li>
            </ol>
        </div>
    </div>
    <?php
}

// Debug WordPress mail
add_action('wp_mail_failed', 'giggajob_log_mailer_errors', 10, 1);
function giggajob_log_mailer_errors($wp_error) {
    error_log('WordPress Mail Error: ' . print_r($wp_error, true));
}

// Add debug info to wp_mail
add_filter('wp_mail', 'giggajob_debug_email', 10, 1);
function giggajob_debug_email($args) {
    error_log('Attempting to send email with the following details:');
    error_log('To: ' . print_r($args['to'], true));
    error_log('Subject: ' . $args['subject']);
    error_log('Headers: ' . print_r($args['headers'], true));
    return $args;
}