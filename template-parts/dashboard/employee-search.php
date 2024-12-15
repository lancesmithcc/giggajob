<?php
if (!defined('ABSPATH')) exit;

// Get all job categories
$job_categories = get_terms([
    'taxonomy' => 'job_category',
    'hide_empty' => false,
]);

// Get unique locations from existing jobs
global $wpdb;
$locations = $wpdb->get_col("
    SELECT DISTINCT meta_value 
    FROM {$wpdb->postmeta} 
    WHERE meta_key = 'job_location' 
    AND meta_value != ''
");

// Get employment types
$employment_types = $wpdb->get_col("
    SELECT DISTINCT meta_value 
    FROM {$wpdb->postmeta} 
    WHERE meta_key = 'job_type' 
    AND meta_value != ''
");

// Get current search parameters
$search_query = isset($_GET['job_search']) ? sanitize_text_field($_GET['job_search']) : '';
$selected_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$selected_location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
$selected_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';

// Simplified query to get all jobs first
$args = [
    'post_type' => 'jobs',
    'posts_per_page' => -1, // Get all posts for debugging
    'post_status' => ['publish', 'draft', 'pending'], // Include all statuses for debugging
];

$jobs_query = new WP_Query($args);

// Debug information
if (current_user_can('administrator')) {
    echo '<div class="alert alert-info">';
    echo '<h5>Debug Information:</h5>';
    echo '<pre>';
    echo 'Total posts found: ' . $jobs_query->found_posts . "\n";
    echo 'Query parameters: ' . print_r($args, true) . "\n";
    echo 'Last SQL Query: ' . $jobs_query->request . "\n";
    echo '</pre>';
    echo '</div>';
}

// Get current user's applications
$current_user_id = get_current_user_id();
$user_applications = get_posts([
    'post_type' => 'job_application',
    'author' => $current_user_id,
    'posts_per_page' => -1,
    'fields' => 'ids'
]);

$applied_job_ids = [];
foreach ($user_applications as $application_id) {
    $job_id = get_post_meta($application_id, 'job_id', true);
    if ($job_id) {
        $applied_job_ids[] = $job_id;
    }
}
?>

<div class="card bg-dark border-secondary mb-4">
    <div class="card-body">
        <h4 class="card-title mb-4">Search Jobs</h4>
        
        <form method="get" class="mb-4">
            <input type="hidden" name="tab" value="search">
            
            <div class="row g-3">
                <!-- Search Query -->
                <div class="col-md-12">
                    <div class="form-floating">
                        <input type="text" class="form-control bg-dark text-light border-secondary" 
                               id="job_search" name="job_search" placeholder="Search jobs..."
                               value="<?php echo esc_attr($search_query); ?>">
                        <label for="job_search">Search jobs...</label>
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select bg-dark text-light border-secondary" 
                                id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($job_categories as $category): ?>
                                <option value="<?php echo esc_attr($category->slug); ?>" 
                                        <?php selected($selected_category, $category->slug); ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="category">Category</label>
                    </div>
                </div>

                <!-- Location Filter -->
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select bg-dark text-light border-secondary" 
                                id="location" name="location">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo esc_attr($location); ?>" 
                                        <?php selected($selected_location, $location); ?>>
                                    <?php echo esc_html($location); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="location">Location</label>
                    </div>
                </div>

                <!-- Employment Type Filter -->
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select bg-dark text-light border-secondary" 
                                id="type" name="type">
                            <option value="">All Types</option>
                            <?php foreach ($employment_types as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>" 
                                        <?php selected($selected_type, $type); ?>>
                                    <?php echo esc_html($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="type">Employment Type</label>
                    </div>
                </div>

                <!-- Search Button -->
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-2"></i>Search
                    </button>
                    <?php if (!empty($_GET)): ?>
                        <a href="<?php echo add_query_arg('tab', 'search', remove_query_arg(['job_search', 'category', 'location', 'type'])); ?>" 
                           class="btn btn-secondary ms-2">
                            <i class="bi bi-x-circle me-2"></i>Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- Results -->
        <?php if ($jobs_query->have_posts()): ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Salary</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($jobs_query->have_posts()): $jobs_query->the_post(); 
                            $job_id = get_the_ID();
                            $job_category = wp_get_post_terms($job_id, 'job_category', ['fields' => 'names']);
                            $job_location = get_post_meta($job_id, 'job_location', true);
                            $job_type = get_post_meta($job_id, 'job_type', true);
                            $job_salary = get_post_meta($job_id, 'job_salary', true);
                            $employer_id = get_post_field('post_author', $job_id);
                            $employer_profile = get_posts([
                                'post_type' => 'employer_profile',
                                'author' => $employer_id,
                                'posts_per_page' => 1
                            ]);
                            $company_name = !empty($employer_profile) ? get_the_title($employer_profile[0]) : 'Unknown Company';
                            $has_applied = in_array($job_id, $applied_job_ids);
                            $show_application_form = isset($_GET['apply']) && $_GET['apply'] == $job_id;
                        ?>
                            <tr>
                                <td>
                                    <a href="<?php the_permalink(); ?>" class="text-decoration-none">
                                        <?php the_title(); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($company_name); ?></td>
                                <td><?php echo implode(', ', $job_category); ?></td>
                                <td><?php echo esc_html($job_location); ?></td>
                                <td><?php echo esc_html($job_type); ?></td>
                                <td><?php echo esc_html($job_salary); ?></td>
                                <td><?php echo get_the_date(); ?></td>
                                <td>
                                    <a href="<?php the_permalink(); ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($has_applied): ?>
                                        <button class="btn btn-sm btn-success" disabled>
                                            <i class="bi bi-check-circle"></i> Applied
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo add_query_arg(['tab' => 'search', 'apply' => $job_id]); ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-send"></i> Apply
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($show_application_form): 
                                // Get the user's resume
                                $resume = get_posts([
                                    'post_type' => 'resume',
                                    'author' => get_current_user_id(),
                                    'posts_per_page' => 1
                                ]);
                                
                                if (empty($resume)): ?>
                                    <tr class="application-form-row">
                                        <td colspan="8">
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                Please create your resume first before applying for jobs.
                                                <a href="<?php echo add_query_arg('tab', 'resume'); ?>" class="btn btn-warning btn-sm ms-3">
                                                    Create Resume
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <tr class="application-form-row">
                                        <td colspan="8">
                                            <div class="card bg-dark border-secondary">
                                                <div class="card-body">
                                                    <h5 class="card-title">Apply for <?php the_title(); ?></h5>
                                                    <form action="" method="post" class="job-application-form" id="job-application-form-<?php echo $job_id; ?>">
                                                        <?php wp_nonce_field('submit_job_application', 'job_application_nonce'); ?>
                                                        <input type="hidden" name="action" value="submit_job_application">
                                                        <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                                                        <input type="hidden" name="resume_id" value="<?php echo $resume[0]->ID; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="cover_letter" class="form-label">Cover Letter</label>
                                                            <textarea class="form-control bg-dark text-light border-secondary" 
                                                                    id="cover_letter" name="cover_letter" rows="5" 
                                                                    placeholder="Tell us why you're a great fit for this position..." 
                                                                    required></textarea>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="bi bi-send me-2"></i>Submit Application
                                                            </button>
                                                            <a href="<?php echo remove_query_arg('apply'); ?>" 
                                                            class="btn btn-secondary">
                                                                <i class="bi bi-x-circle me-2"></i>Cancel
                                                            </a>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php
            echo '<div class="pagination justify-content-center">';
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $jobs_query->max_num_pages,
                'current' => max(1, get_query_var('paged')),
                'type' => 'list'
            ]);
            echo '</div>';
            ?>

            <script>
            jQuery(document).ready(function($) {
                $('.job-application-form').on('submit', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var submitBtn = form.find('button[type="submit"]');
                    submitBtn.prop('disabled', true);

                    $.ajax({
                        url: giggajob_ajax.ajax_url,
                        type: 'POST',
                        data: form.serialize(),
                        success: function(response) {
                            if (response.success) {
                                window.location.href = window.location.href.split('?')[0] + '?tab=applications';
                            } else {
                                alert(response.data.message || 'Application submission failed.');
                                submitBtn.prop('disabled', false);
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                            submitBtn.prop('disabled', false);
                        }
                    });
                });
            });
            </script>

        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>No jobs found matching your criteria.
            </div>
        <?php endif; 
        wp_reset_postdata(); ?>
    </div>
</div>

<style>
/* Dark theme pagination styles */
.pagination {
    margin-top: 1rem;
}
.pagination .page-numbers {
    position: relative;
    display: block;
    padding: 0.5rem 0.75rem;
    margin-left: -1px;
    line-height: 1.25;
    color: #fff;
    background-color: #343a40;
    border: 1px solid #6c757d;
    text-decoration: none;
}
.pagination .page-numbers:hover {
    background-color: #2b3035;
}
.pagination .page-numbers.current {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.pagination .page-numbers.dots {
    color: #6c757d;
}

/* Application form styles */
.application-form-row {
    background-color: #2b3035;
}
.application-form-row td {
    padding: 1rem !important;
}
</style> 