<?php
/**
 * Template part for displaying employee job search
 */

// Security check
if (!defined('ABSPATH')) exit;

// Get search parameters
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$selected_industry = isset($_GET['industry']) ? sanitize_text_field($_GET['industry']) : '';
$selected_type = isset($_GET['job_type']) ? sanitize_text_field($_GET['job_type']) : '';
$selected_remote = isset($_GET['remote_option']) ? sanitize_text_field($_GET['remote_option']) : '';

// Get all industries
$industries = get_terms(array(
    'taxonomy' => 'industry',
    'hide_empty' => true
));

// Job types
$job_types = array(
    'full-time' => 'Full Time',
    'part-time' => 'Part Time',
    'contract' => 'Contract',
    'temporary' => 'Temporary',
    'internship' => 'Internship'
);

// Remote options
$remote_options = array(
    'no' => 'Office Only',
    'hybrid' => 'Hybrid',
    'yes' => 'Fully Remote'
);
?>

<div class="job-search-section">
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?php echo esc_url(add_query_arg(array('tab' => 'search'), remove_query_arg(array('paged', 'page')))); ?>" method="get">
                <input type="hidden" name="tab" value="search">
                <div class="row g-3">
                    <!-- Keyword Search -->
                    <div class="col-md-12">
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" 
                                   name="s" value="<?php echo esc_attr($search_query); ?>" 
                                   placeholder="Job title, keywords, or company">
                        </div>
                    </div>

                    <!-- Industry Filter -->
                    <div class="col-md-4">
                        <select class="form-select" name="industry">
                            <option value="">All Industries</option>
                            <?php foreach ($industries as $industry): ?>
                                <option value="<?php echo esc_attr($industry->slug); ?>" 
                                        <?php selected($selected_industry, $industry->slug); ?>>
                                    <?php echo esc_html($industry->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Job Type Filter -->
                    <div class="col-md-4">
                        <select class="form-select" name="job_type">
                            <option value="">All Job Types</option>
                            <?php foreach ($job_types as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected($selected_type, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Remote Option Filter -->
                    <div class="col-md-4">
                        <select class="form-select" name="remote_option">
                            <option value="">Any Work Style</option>
                            <?php foreach ($remote_options as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected($selected_remote, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Search Button -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i>Search Jobs
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Job Listings -->
    <?php
    // Build query arguments
    $paged = (get_query_var('paged')) ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);
    
    // Base query arguments
    $args = array(
        'post_type' => 'jobs',
        'posts_per_page' => 12,
        'paged' => $paged,
        'post_status' => 'publish'
    );

    // Add meta query for active jobs
    $args['meta_query'] = array(
        array(
            'key' => 'job_status',
            'value' => 'active',
            'compare' => '='
        )
    );

    // Add search query if exists
    if (!empty($search_query)) {
        $args['s'] = $search_query;
    }

    // Add industry filter
    if (!empty($selected_industry)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'industry',
                'field' => 'slug',
                'terms' => $selected_industry
            )
        );
    }

    // Add job type filter
    if (!empty($selected_type)) {
        $args['meta_query'][] = array(
            'key' => 'job_type',
            'value' => $selected_type,
            'compare' => '='
        );
    }

    // Add remote option filter
    if (!empty($selected_remote)) {
        $args['meta_query'][] = array(
            'key' => 'remote_option',
            'value' => $selected_remote,
            'compare' => '='
        );
    }

    // Handle sorting
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
    switch ($orderby) {
        case 'title':
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
        case 'company':
            $args['orderby'] = 'meta_value';
            $args['meta_key'] = 'company_name';
            $args['order'] = 'ASC';
            break;
        default: // date
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
    }

    // Debug query for administrators
    if (current_user_can('administrator') && isset($_GET['debug'])) {
        echo '<pre class="bg-dark text-light p-3 mb-4">';
        echo 'Query Args: ' . print_r($args, true);
        echo '</pre>';
    }

    $jobs_query = new WP_Query($args);

    // Debug results for administrators
    if (current_user_can('administrator') && isset($_GET['debug'])) {
        echo '<pre class="bg-dark text-light p-3 mb-4">';
        echo 'Found Posts: ' . $jobs_query->found_posts . "\n";
        echo 'Post Count: ' . $jobs_query->post_count . "\n";
        echo 'Max Num Pages: ' . $jobs_query->max_num_pages . "\n";
        echo 'Current Page: ' . $paged . "\n";
        echo 'Request: ' . $jobs_query->request . "\n";
        echo '</pre>';
    }
    ?>

    <!-- Results Count and Sort -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="results-count">
            <?php if ($jobs_query->have_posts()): ?>
                <h2 class="h5 mb-0">
                    Found <?php echo $jobs_query->found_posts; ?> job<?php echo $jobs_query->found_posts !== 1 ? 's' : ''; ?>
                </h2>
            <?php endif; ?>
        </div>
        <div class="sort-options">
            <?php 
            // Get current URL with all parameters except paged and orderby
            $current_url = remove_query_arg(array('paged', 'page', 'orderby'));
            ?>
            <select class="form-select" id="sort-jobs" onchange="window.location.href=this.value">
                <option value="<?php echo esc_url(add_query_arg('orderby', 'date', $current_url)); ?>" 
                        <?php selected($orderby, 'date'); ?>>Most Recent</option>
                <option value="<?php echo esc_url(add_query_arg('orderby', 'title', $current_url)); ?>" 
                        <?php selected($orderby, 'title'); ?>>Job Title</option>
                <option value="<?php echo esc_url(add_query_arg('orderby', 'company', $current_url)); ?>" 
                        <?php selected($orderby, 'company'); ?>>Company</option>
            </select>
        </div>
    </div>

    <!-- Job Cards -->
    <?php if ($jobs_query->have_posts()): ?>
        <div class="row g-4">
            <?php 
            while ($jobs_query->have_posts()): $jobs_query->the_post(); 
                echo '<div class="col-md-6 col-lg-4">';
                get_template_part('template-parts/content', 'jobs');
                echo '</div>';
            endwhile; 
            wp_reset_postdata(); // Reset post data before pagination
            ?>
        </div>

        <!-- Pagination -->
        <?php if ($jobs_query->max_num_pages > 1): ?>
            <div class="pagination-wrapper mt-5">
                <?php
                // Get the current page URL with all parameters
                $current_url = add_query_arg(null, null);
                
                // Get the base URL (current page URL without pagination parameters)
                $base_url = remove_query_arg(array('paged', 'page'), $current_url);
                
                // Make sure we preserve the tab parameter
                if (!strpos($base_url, 'tab=')) {
                    $base_url = add_query_arg('tab', 'search', $base_url);
                }
                
                // Debug information for administrators
                if (current_user_can('administrator') && isset($_GET['debug'])) {
                    echo '<pre class="bg-dark text-light p-3 mt-3">';
                    echo "Current URL: " . $current_url . "\n";
                    echo "Base URL: " . $base_url . "\n";
                    echo "Page: " . $paged . "\n";
                    echo "Max Pages: " . $jobs_query->max_num_pages . "\n";
                    echo "Query: " . $jobs_query->request . "\n";
                    echo '</pre>';
                }
                
                // Output the pagination links
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%', $base_url),
                    'format' => '',
                    'current' => $paged,
                    'total' => $jobs_query->max_num_pages,
                    'prev_text' => '<i class="bi bi-chevron-left"></i> Previous',
                    'next_text' => 'Next <i class="bi bi-chevron-right"></i>',
                    'type' => 'list',
                    'end_size' => 2,
                    'mid_size' => 2
                ));
                ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-results text-center py-5">
            <i class="bi bi-search display-1 text-muted mb-4"></i>
            <h3>No Jobs Found</h3>
            <p class="text-muted">Try adjusting your search criteria or removing some filters.</p>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize select2 for better dropdowns
    $('.form-select').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script> 