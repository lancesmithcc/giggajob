<?php
if (!defined('ABSPATH')) exit;

/**
 * Register Custom Taxonomies
 */
function giggajob_register_taxonomies() {
    // Industries Taxonomy
    register_taxonomy('industry', array('jobs', 'employer_profile', 'resume'), array(
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
        'show_in_rest' => true,
    ));

    // Job Types Taxonomy
    register_taxonomy('job_type', 'jobs', array(
        'labels' => array(
            'name' => __('Job Types', 'giggajob'),
            'singular_name' => __('Job Type', 'giggajob'),
            'search_items' => __('Search Job Types', 'giggajob'),
            'all_items' => __('All Job Types', 'giggajob'),
            'edit_item' => __('Edit Job Type', 'giggajob'),
            'update_item' => __('Update Job Type', 'giggajob'),
            'add_new_item' => __('Add New Job Type', 'giggajob'),
            'new_item_name' => __('New Job Type Name', 'giggajob'),
            'menu_name' => __('Job Types', 'giggajob'),
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_quick_edit' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'job-type'),
        'show_in_rest' => true,
        'meta_box_cb' => 'post_categories_meta_box',
        'capabilities' => array(
            'manage_terms' => 'manage_categories',
            'edit_terms' => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts'
        ),
    ));
}
add_action('init', 'giggajob_register_taxonomies');

/**
 * Register Custom Post Types
 */
function giggajob_register_post_types() {
    // Jobs Post Type
    register_post_type('jobs', array(
        'labels' => array(
            'name' => __('Jobs', 'giggajob'),
            'singular_name' => __('Job', 'giggajob'),
            'add_new' => __('Add New Job', 'giggajob'),
            'add_new_item' => __('Add New Job', 'giggajob'),
            'edit_item' => __('Edit Job', 'giggajob'),
            'all_items' => __('All Jobs', 'giggajob'),
            'view_item' => __('View Job', 'giggajob'),
            'search_items' => __('Search Jobs', 'giggajob'),
            'not_found' => __('No jobs found', 'giggajob'),
            'not_found_in_trash' => __('No jobs found in trash', 'giggajob'),
            'menu_name' => __('Jobs', 'giggajob'),
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-businessman',
        'menu_position' => 20,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'rewrite' => array('slug' => 'jobs'),
        'taxonomies' => array('industry', 'job_type')
    ));

    // Job Applications Post Type
    register_post_type('job_application', array(
        'labels' => array(
            'name' => __('Applications', 'giggajob'),
            'singular_name' => __('Application', 'giggajob'),
            'add_new' => __('Add New Application', 'giggajob'),
            'add_new_item' => __('Add New Application', 'giggajob'),
            'edit_item' => __('Edit Application', 'giggajob'),
            'all_items' => __('All Applications', 'giggajob'),
            'view_item' => __('View Application', 'giggajob'),
            'search_items' => __('Search Applications', 'giggajob'),
            'not_found' => __('No applications found', 'giggajob'),
            'not_found_in_trash' => __('No applications found in trash', 'giggajob'),
            'menu_name' => __('Applications', 'giggajob'),
        ),
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_admin_bar' => true,
        'menu_icon' => 'dashicons-portfolio',
        'menu_position' => 21,
        'supports' => array('title', 'editor', 'custom-fields')
    ));

    // Resume Post Type
    register_post_type('resume', array(
        'labels' => array(
            'name' => __('Resumes', 'giggajob'),
            'singular_name' => __('Resume', 'giggajob'),
            'add_new' => __('Add New Resume', 'giggajob'),
            'add_new_item' => __('Add New Resume', 'giggajob'),
            'edit_item' => __('Edit Resume', 'giggajob'),
            'all_items' => __('All Resumes', 'giggajob'),
            'view_item' => __('View Resume', 'giggajob'),
            'search_items' => __('Search Resumes', 'giggajob'),
            'not_found' => __('No resumes found', 'giggajob'),
            'not_found_in_trash' => __('No resumes found in trash', 'giggajob'),
            'menu_name' => __('Resumes', 'giggajob'),
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_admin_bar' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-id',
        'menu_position' => 22,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'rewrite' => array('slug' => 'resumes'),
        'taxonomies' => array('industry')
    ));

    // Employer Profile Post Type
    register_post_type('employer_profile', array(
        'labels' => array(
            'name' => __('Employer Profiles', 'giggajob'),
            'singular_name' => __('Employer Profile', 'giggajob'),
            'add_new' => __('Add New Profile', 'giggajob'),
            'add_new_item' => __('Add New Profile', 'giggajob'),
            'edit_item' => __('Edit Profile', 'giggajob'),
            'all_items' => __('All Profiles', 'giggajob'),
            'view_item' => __('View Profile', 'giggajob'),
            'search_items' => __('Search Profiles', 'giggajob'),
            'not_found' => __('No profiles found', 'giggajob'),
            'not_found_in_trash' => __('No profiles found in trash', 'giggajob'),
            'menu_name' => __('Employer Profiles', 'giggajob'),
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_admin_bar' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-building',
        'menu_position' => 23,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'rewrite' => array('slug' => 'employer-profiles'),
        'taxonomies' => array('industry')
    ));
}
add_action('init', 'giggajob_register_post_types');

// Add admin columns for Jobs
function giggajob_add_job_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = __('Job Title', 'giggajob');
    $new_columns['company'] = __('Company', 'giggajob');
    $new_columns['location'] = __('Location', 'giggajob');
    $new_columns['job_type'] = __('Job Type', 'giggajob');
    $new_columns['industry'] = __('Industry', 'giggajob');
    $new_columns['status'] = __('Status', 'giggajob');
    $new_columns['date'] = __('Date', 'giggajob');
    return $new_columns;
}
add_filter('manage_jobs_posts_columns', 'giggajob_add_job_columns');

// Make columns sortable
function giggajob_sortable_job_columns($columns) {
    $columns['company'] = 'company';
    $columns['location'] = 'location';
    $columns['status'] = 'status';
    return $columns;
}
add_filter('manage_edit-jobs_sortable_columns', 'giggajob_sortable_job_columns');

// Add filters above the jobs table
function giggajob_add_job_filters() {
    global $typenow;
    if ($typenow == 'jobs') {
        // Company filter
        $company = isset($_GET['company_filter']) ? sanitize_text_field($_GET['company_filter']) : '';
        $companies = get_posts(array(
            'post_type' => 'jobs',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $unique_companies = array();
        foreach ($companies as $post_id) {
            $company_name = get_post_meta($post_id, 'company_name', true);
            if (!empty($company_name)) {
                $unique_companies[$company_name] = $company_name;
            }
        }
        if (!empty($unique_companies)) {
            echo '<select name="company_filter">';
            echo '<option value="">' . __('Filter by Company', 'giggajob') . '</option>';
            foreach ($unique_companies as $company_name) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($company_name),
                    selected($company, $company_name, false),
                    esc_html($company_name)
                );
            }
            echo '</select>';
        }

        // Location filter
        $location = isset($_GET['location_filter']) ? sanitize_text_field($_GET['location_filter']) : '';
        $locations = get_posts(array(
            'post_type' => 'jobs',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $unique_locations = array();
        foreach ($locations as $post_id) {
            $job_location = get_post_meta($post_id, 'job_location', true);
            if (!empty($job_location)) {
                $unique_locations[$job_location] = $job_location;
            }
        }
        if (!empty($unique_locations)) {
            echo '<select name="location_filter">';
            echo '<option value="">' . __('Filter by Location', 'giggajob') . '</option>';
            foreach ($unique_locations as $location_name) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($location_name),
                    selected($location, $location_name, false),
                    esc_html($location_name)
                );
            }
            echo '</select>';
        }

        // Job Type filter
        $job_type = isset($_GET['job_type']) ? sanitize_text_field($_GET['job_type']) : '';
        $job_types = get_terms(array(
            'taxonomy' => 'job_type',
            'hide_empty' => false,
        ));
        if (!empty($job_types) && !is_wp_error($job_types)) {
            echo '<select name="job_type">';
            echo '<option value="">' . __('Filter by Job Type', 'giggajob') . '</option>';
            foreach ($job_types as $type) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($type->slug),
                    selected($job_type, $type->slug, false),
                    esc_html($type->name)
                );
            }
            echo '</select>';
        }

        // Status filter
        $status = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        $statuses = array(
            'active' => 'Active',
            'draft' => 'Draft',
            'expired' => 'Expired'
        );
        echo '<select name="status_filter">';
        echo '<option value="">' . __('Filter by Status', 'giggajob') . '</option>';
        foreach ($statuses as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($status, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }
}
add_action('restrict_manage_posts', 'giggajob_add_job_filters');

// Handle the custom filters
function giggajob_handle_job_filters($query) {
    global $pagenow;
    $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
    
    if (is_admin() && $pagenow == 'edit.php' && $post_type == 'jobs' && $query->is_main_query()) {
        $meta_query = array();

        // Company filter
        if (!empty($_GET['company_filter'])) {
            $meta_query[] = array(
                'key' => 'company_name',
                'value' => sanitize_text_field($_GET['company_filter']),
                'compare' => '='
            );
        }

        // Location filter
        if (!empty($_GET['location_filter'])) {
            $meta_query[] = array(
                'key' => 'job_location',
                'value' => sanitize_text_field($_GET['location_filter']),
                'compare' => '='
            );
        }

        // Status filter
        if (!empty($_GET['status_filter'])) {
            $meta_query[] = array(
                'key' => 'job_status',
                'value' => sanitize_text_field($_GET['status_filter']),
                'compare' => '='
            );
        }

        // Job Type filter
        if (!empty($_GET['job_type'])) {
            $tax_query = array(
                array(
                    'taxonomy' => 'job_type',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_GET['job_type'])
                )
            );
            $query->set('tax_query', $tax_query);
        }

        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $query->set('meta_query', $meta_query);
        }

        // Handle sorting
        if (!empty($_GET['orderby'])) {
            switch ($_GET['orderby']) {
                case 'company':
                    $query->set('meta_key', 'company_name');
                    $query->set('orderby', 'meta_value');
                    break;
                case 'location':
                    $query->set('meta_key', 'job_location');
                    $query->set('orderby', 'meta_value');
                    break;
                case 'status':
                    $query->set('meta_key', 'job_status');
                    $query->set('orderby', 'meta_value');
                    break;
            }
        }
    }
}
add_action('pre_get_posts', 'giggajob_handle_job_filters');

// Fill Job columns
function giggajob_fill_job_columns($column, $post_id) {
    switch ($column) {
        case 'company':
            echo esc_html(get_post_meta($post_id, 'company_name', true));
            break;
        case 'location':
            echo esc_html(get_post_meta($post_id, 'job_location', true));
            break;
        case 'job_type':
            $terms = get_the_terms($post_id, 'job_type');
            if ($terms && !is_wp_error($terms)) {
                $term_names = array();
                foreach ($terms as $term) {
                    $term_names[] = '<span class="job-type-' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</span>';
                }
                echo implode(', ', $term_names);
            }
            break;
        case 'remote_work':
            $remote_work = get_post_meta($post_id, 'remote_work', true);
            $options = array(
                'no' => 'Office Only',
                'hybrid' => 'Hybrid',
                'yes' => 'Fully Remote'
            );
            echo '<span class="remote-work-' . esc_attr($remote_work) . '">' . esc_html(isset($options[$remote_work]) ? $options[$remote_work] : $remote_work) . '</span>';
            break;
        case 'industry':
            $terms = get_the_terms($post_id, 'industry');
            if ($terms && !is_wp_error($terms)) {
                $term_names = wp_list_pluck($terms, 'name');
                echo esc_html(implode(', ', $term_names));
            }
            break;
        case 'status':
            $status = get_post_meta($post_id, 'job_status', true);
            $status_class = '';
            switch ($status) {
                case 'active':
                    $status_class = 'success';
                    break;
                case 'draft':
                    $status_class = 'warning';
                    break;
                case 'expired':
                    $status_class = 'error';
                    break;
            }
            echo '<span class="status-' . esc_attr($status_class) . '">' . esc_html(ucfirst($status)) . '</span>';
            break;
    }
}
add_action('manage_jobs_posts_custom_column', 'giggajob_fill_job_columns', 10, 2);

// Add capabilities to administrator role
function giggajob_add_admin_capabilities() {
    $admin = get_role('administrator');
    
    // Job capabilities
    $admin->add_cap('edit_job');
    $admin->add_cap('read_job');
    $admin->add_cap('delete_job');
    $admin->add_cap('edit_jobs');
    $admin->add_cap('edit_others_jobs');
    $admin->add_cap('publish_jobs');
    $admin->add_cap('read_private_jobs');
    $admin->add_cap('delete_jobs');

    // Application capabilities
    $admin->add_cap('edit_application');
    $admin->add_cap('read_application');
    $admin->add_cap('delete_application');
    $admin->add_cap('edit_applications');
    $admin->add_cap('edit_others_applications');
    $admin->add_cap('publish_applications');
    $admin->add_cap('read_private_applications');
    $admin->add_cap('delete_applications');

    // Resume capabilities
    $admin->add_cap('edit_resume');
    $admin->add_cap('read_resume');
    $admin->add_cap('delete_resume');
    $admin->add_cap('edit_resumes');
    $admin->add_cap('edit_others_resumes');
    $admin->add_cap('publish_resumes');
    $admin->add_cap('read_private_resumes');
    $admin->add_cap('delete_resumes');

    // Employer Profile capabilities
    $admin->add_cap('edit_employer_profile');
    $admin->add_cap('read_employer_profile');
    $admin->add_cap('delete_employer_profile');
    $admin->add_cap('edit_employer_profiles');
    $admin->add_cap('edit_others_employer_profiles');
    $admin->add_cap('publish_employer_profiles');
    $admin->add_cap('read_private_employer_profiles');
    $admin->add_cap('delete_employer_profiles');
}
add_action('admin_init', 'giggajob_add_admin_capabilities');

/**
 * Function to organize taxonomy terms hierarchically
 */
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

// Add admin columns for Job Applications
function giggajob_add_application_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = __('Title', 'giggajob');
    $new_columns['job'] = __('Job', 'giggajob');
    $new_columns['applicant'] = __('Applicant', 'giggajob');
    $new_columns['status'] = __('Status', 'giggajob');
    $new_columns['date'] = __('Date', 'giggajob');
    return $new_columns;
}
add_filter('manage_job_application_posts_columns', 'giggajob_add_application_columns');

// Fill Job Application columns
function giggajob_fill_application_columns($column, $post_id) {
    switch ($column) {
        case 'job':
            $job_id = get_post_meta($post_id, 'job_id', true);
            if ($job_id) {
                $job = get_post($job_id);
                if ($job) {
                    echo '<a href="' . get_edit_post_link($job_id) . '">' . esc_html($job->post_title) . '</a>';
                }
            }
            break;
        case 'applicant':
            $applicant_id = get_post_meta($post_id, 'applicant_id', true);
            if ($applicant_id) {
                $user = get_user_by('id', $applicant_id);
                if ($user) {
                    echo esc_html($user->display_name) . '<br>';
                    echo '<small>' . esc_html($user->user_email) . '</small>';
                }
            }
            break;
        case 'status':
            $status = get_post_meta($post_id, 'status', true);
            $status_class = '';
            switch ($status) {
                case 'pending':
                    $status_class = 'warning';
                    break;
                case 'interview_scheduled':
                    $status_class = 'info';
                    break;
                case 'accepted':
                    $status_class = 'success';
                    break;
                case 'rejected':
                    $status_class = 'error';
                    break;
            }
            echo '<span class="status-' . esc_attr($status_class) . '">' . esc_html(ucfirst($status)) . '</span>';
            break;
    }
}
add_action('manage_job_application_posts_custom_column', 'giggajob_fill_application_columns', 10, 2);

// Add admin columns for Resumes
function giggajob_add_resume_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = __('Name', 'giggajob');
    $new_columns['professional_title'] = __('Professional Title', 'giggajob');
    $new_columns['location'] = __('Location', 'giggajob');
    $new_columns['industry'] = __('Industry', 'giggajob');
    $new_columns['date'] = __('Date', 'giggajob');
    return $new_columns;
}
add_filter('manage_resume_posts_columns', 'giggajob_add_resume_columns');

// Fill Resume columns
function giggajob_fill_resume_columns($column, $post_id) {
    switch ($column) {
        case 'professional_title':
            echo esc_html(get_post_meta($post_id, 'professional_title', true));
            break;
        case 'location':
            echo esc_html(get_post_meta($post_id, 'location', true));
            break;
        case 'industry':
            $terms = get_the_terms($post_id, 'industry');
            if ($terms && !is_wp_error($terms)) {
                $industry_names = array_map(function($term) {
                    return $term->name;
                }, $terms);
                echo esc_html(implode(', ', $industry_names));
            }
            break;
    }
}
add_action('manage_resume_posts_custom_column', 'giggajob_fill_resume_columns', 10, 2);

// Add admin columns for Employer Profiles
function giggajob_add_employer_profile_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = __('Company Name', 'giggajob');
    $new_columns['industry'] = __('Industry', 'giggajob');
    $new_columns['location'] = __('Location', 'giggajob');
    $new_columns['company_size'] = __('Company Size', 'giggajob');
    $new_columns['date'] = __('Date', 'giggajob');
    return $new_columns;
}
add_filter('manage_employer_profile_posts_columns', 'giggajob_add_employer_profile_columns');

// Fill Employer Profile columns
function giggajob_fill_employer_profile_columns($column, $post_id) {
    switch ($column) {
        case 'industry':
            $terms = get_the_terms($post_id, 'industry');
            if ($terms && !is_wp_error($terms)) {
                $industry_names = array_map(function($term) {
                    return $term->name;
                }, $terms);
                echo esc_html(implode(', ', $industry_names));
            }
            break;
        case 'location':
            $city = get_post_meta($post_id, 'city', true);
            $state = get_post_meta($post_id, 'state', true);
            $country = get_post_meta($post_id, 'country', true);
            $location_parts = array_filter(array($city, $state, $country));
            echo esc_html(implode(', ', $location_parts));
            break;
        case 'company_size':
            echo esc_html(get_post_meta($post_id, 'company_size', true));
            break;
    }
}
add_action('manage_employer_profile_posts_custom_column', 'giggajob_fill_employer_profile_columns', 10, 2);

// Add custom CSS for status indicators
function giggajob_admin_custom_css() {
    echo '
    <style>
        .status-success { color: #46b450; }
        .status-warning { color: #ffb900; }
        .status-error { color: #dc3232; }
        .status-info { color: #00a0d2; }
        
        /* Column widths for Jobs list */
        .post-type-jobs .wp-list-table {
            table-layout: fixed;
        }
        .post-type-jobs .column-title { width: 20%; }
        .post-type-jobs .column-company { width: 15%; }
        .post-type-jobs .column-location { width: 12%; }
        .post-type-jobs .column-job_type { width: 12%; }
        .post-type-jobs .column-industry { width: 15%; }
        .post-type-jobs .column-status { width: 8%; }
        .post-type-jobs .column-date { width: 10%; }
        
        /* Remote work options styling */
        .remote-work-yes { color: #46b450; }
        .remote-work-hybrid { color: #00a0d2; }
        .remote-work-no { color: #666; }
        
        /* Column widths for other post types */
        .column-status { width: 10%; }
        .column-industry { width: 15%; }
        .column-location { width: 15%; }
        
        /* Make status more visible */
        .status-success,
        .status-warning,
        .status-error,
        .status-info,
        .remote-work-yes,
        .remote-work-hybrid,
        .remote-work-no {
            padding: 3px 8px;
            border-radius: 3px;
            background: rgba(0,0,0,0.05);
            display: inline-block;
        }
        
        /* Ensure text wraps properly in cells */
        .widefat td {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>';
}
add_action('admin_head', 'giggajob_admin_custom_css');

// Add default job types
function giggajob_add_default_job_types() {
    $default_job_types = array(
        'Full Time' => 'Full time employment',
        'Part Time' => 'Part time employment',
        'Contract' => 'Contract based work',
        'Temporary' => 'Temporary position',
        'Internship' => 'Internship position'
    );

    foreach ($default_job_types as $name => $description) {
        if (!term_exists($name, 'job_type')) {
            wp_insert_term($name, 'job_type', array(
                'description' => $description,
                'slug' => sanitize_title($name)
            ));
        }
    }
}
add_action('init', 'giggajob_add_default_job_types', 11); // Run after taxonomy registration 