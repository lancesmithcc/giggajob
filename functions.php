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

    // Job Categories Taxonomy
    register_taxonomy('job_category', array('jobs'), array(
        'labels' => array(
            'name' => __('Job Categories', 'giggajob'),
            'singular_name' => __('Job Category', 'giggajob'),
            'search_items' => __('Search Categories', 'giggajob'),
            'all_items' => __('All Categories', 'giggajob'),
            'parent_item' => __('Parent Category', 'giggajob'),
            'parent_item_colon' => __('Parent Category:', 'giggajob'),
            'edit_item' => __('Edit Category', 'giggajob'),
            'update_item' => __('Update Category', 'giggajob'),
            'add_new_item' => __('Add New Category', 'giggajob'),
            'new_item_name' => __('New Category Name', 'giggajob'),
            'menu_name' => __('Job Categories', 'giggajob'),
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'job-category'),
        'show_in_rest' => true, // Enable Gutenberg editor support
    ));
}
add_action('init', 'giggajob_register_taxonomies');

// Handle Job Submission
function giggajob_handle_job_submission() {
    // Check nonce
    if (!isset($_POST['job_nonce']) || !wp_verify_nonce($_POST['job_nonce'], 'post_job_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // Check if user is logged in and is an employer
    if (!is_user_logged_in() || !in_array('employer', wp_get_current_user()->roles)) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

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
            wp_send_json_error(array('message' => $label . ' is required.'));
        }
    }

    // Check if editing or creating new job
    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $editing = false;

    if ($job_id) {
        $job = get_post($job_id);
        if (!$job || $job->post_author != get_current_user_id()) {
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
        $job_id = wp_update_post($job_data);
    } else {
        $job_id = wp_insert_post($job_data);
    }

    if (is_wp_error($job_id)) {
        wp_send_json_error(array('message' => 'Failed to ' . ($editing ? 'update' : 'create') . ' job post.'));
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

    foreach ($meta_fields as $key => $value) {
        update_post_meta($job_id, $key, $value);
    }

    // Set industries
    if (!empty($_POST['industry']) && is_array($_POST['industry'])) {
        $industries = array_map('intval', $_POST['industry']);
        wp_set_object_terms($job_id, $industries, 'industry');
    }

    // Set job categories
    if (!empty($_POST['job_category']) && is_array($_POST['job_category'])) {
        $categories = array_map('intval', $_POST['job_category']);
        wp_set_object_terms($job_id, $categories, 'job_category');
    }

    // Set expiry date (30 days from now)
    $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
    update_post_meta($job_id, 'job_expiry_date', $expiry_date);

    // Send success response
    wp_send_json_success(array(
        'message' => 'Job ' . ($editing ? 'updated' : 'posted') . ' successfully!',
        'redirect_url' => add_query_arg('tab', 'manage-jobs', remove_query_arg('job_id'))
    ));
}
add_action('wp_ajax_post_job', 'giggajob_handle_job_submission');

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
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'taxonomies' => array('industry', 'job_category')
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
        'capability_type' => 'post',
        'map_meta_cap' => true,
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
        'capability_type' => 'post',
        'map_meta_cap' => true,
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
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'taxonomies' => array('industry') // Add industry taxonomy to employer profiles
    ));
}
add_action('init', 'giggajob_register_post_types');

// Add custom capabilities to roles
function giggajob_add_role_caps() {
    // Get the employer role
    $employer = get_role('employer');
    if ($employer) {
        $employer->add_cap('read');
        $employer->add_cap('edit_jobs');
        $employer->add_cap('publish_jobs');
        $employer->add_cap('edit_published_jobs');
        $employer->add_cap('delete_published_jobs');
    }

    // Get the employee role
    $employee = get_role('employee');
    if ($employee) {
        $employee->add_cap('read');
        $employee->add_cap('edit_resume');
        $employee->add_cap('publish_resume');
        $employee->add_cap('edit_published_resume');
        $employee->add_cap('delete_published_resume');
    }
}
add_action('init', 'giggajob_add_role_caps');

// Restrict Admin Access
function giggajob_restrict_admin_access() {
    if (is_admin() && !current_user_can('administrator') && 
        !(defined('DOING_AJAX') && DOING_AJAX)) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('init', 'giggajob_restrict_admin_access');

// Redirect users to their respective dashboards after login
function giggajob_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('employee', $user->roles)) {
            return home_url('/employee-dashboard');
        } elseif (in_array('employer', $user->roles)) {
            return home_url('/employer-dashboard');
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'giggajob_login_redirect', 10, 3);

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

// Handle Application Actions (reject, cancel interview)
add_action('wp_ajax_handle_application_action', 'giggajob_handle_application_action');
function giggajob_handle_application_action() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'giggajob_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // Check if user is logged in and is an employer
    if (!is_user_logged_in() || !in_array('employer', wp_get_current_user()->roles)) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
    $action = isset($_POST['application_action']) ? sanitize_text_field($_POST['application_action']) : '';

    if (!$application_id || !$action) {
        wp_send_json_error(array('message' => 'Invalid request parameters.'));
    }

    // Get the application
    $application = get_post($application_id);
    if (!$application || $application->post_type !== 'job_application') {
        wp_send_json_error(array('message' => 'Invalid application.'));
    }

    // Get the associated job
    $job_id = get_post_meta($application_id, 'job_id', true);
    $job = get_post($job_id);

    // Verify ownership
    if (!$job || $job->post_author != get_current_user_id()) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    switch ($action) {
        case 'reject':
            update_post_meta($application_id, 'status', 'rejected');
            wp_send_json_success(array('message' => 'Application rejected successfully.'));
            break;

        case 'cancel_interview':
            update_post_meta($application_id, 'status', 'pending');
            delete_post_meta($application_id, 'interview_date');
            delete_post_meta($application_id, 'interview_time');
            delete_post_meta($application_id, 'interview_location');
            delete_post_meta($application_id, 'interview_message');
            wp_send_json_success(array('message' => 'Interview cancelled successfully.'));
            break;

        default:
            wp_send_json_error(array('message' => 'Invalid action.'));
            break;
    }
}

// Handle Interview Scheduling
add_action('wp_ajax_handle_interview_schedule', 'giggajob_handle_interview_schedule');
function giggajob_handle_interview_schedule() {
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

    error_log('Interview Schedule Handler - Successfully scheduled interview for application: ' . $application_id);
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
    $notifications = isset($preferences['notifications']) ? $preferences['notifications'] : array();

    // Sanitize each preference
    $notifications = array_map('sanitize_text_field', $notifications);

    // Save preferences
    $result = update_user_meta(get_current_user_id(), 'notification_preferences', $notifications);

    if ($result) {
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

    // Update password
    wp_set_password($new_password, $current_user->ID);

    // Log the user back in
    $creds = array(
        'user_login' => $current_user->user_login,
        'user_password' => $new_password,
        'remember' => true
    );

    wp_signon($creds, false);

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

    // Send notification to employer
    $employer_id = $job->post_author;
    // TODO: Add notification system

    wp_send_json_success(['message' => 'Application submitted successfully!']);
}
