<?php
/**
 * Template Name: Front Page
 */

get_header();

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

<div class="front-page">
    <!-- Hero Section with Search -->
    <section class="hero bg-primary text-white py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <h1 class="display-4 text-center mb-4">Find Your Dream Job</h1>
                    
                    <!-- Search Form -->
                    <form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="job-search-form bg-white p-4 rounded shadow">
                        <div class="row g-3">
                            <!-- Keyword Search -->
                            <div class="col-md-12">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
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
        </div>
    </section>

    <!-- Job Listings Section -->
    <section class="job-listings py-5">
        <div class="container">
            <?php
            // Build query arguments
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $args = array(
                'post_type' => 'jobs',
                'posts_per_page' => 10,
                'paged' => $paged,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'job_status',
                        'value' => 'active',
                        'compare' => '='
                    )
                )
            );

            // Add search query if exists
            if (!empty($search_query)) {
                $args['s'] = $search_query;
            }

            // Add industry filter
            if (!empty($selected_industry)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'industry',
                    'field' => 'slug',
                    'terms' => $selected_industry
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

            $jobs_query = new WP_Query($args);
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
                    <select class="form-select" id="sort-jobs">
                        <option value="date">Most Recent</option>
                        <option value="title">Job Title</option>
                        <option value="company">Company</option>
                    </select>
                </div>
            </div>

            <!-- Job Cards -->
            <?php if ($jobs_query->have_posts()): ?>
                <div class="row g-4">
                    <?php while ($jobs_query->have_posts()): $jobs_query->the_post(); 
                        $company_name = get_post_meta(get_the_ID(), 'company_name', true);
                        $job_type = get_post_meta(get_the_ID(), 'job_type', true);
                        $job_location = get_post_meta(get_the_ID(), 'job_location', true);
                        $remote_option = get_post_meta(get_the_ID(), 'remote_option', true);
                        $salary_type = get_post_meta(get_the_ID(), 'salary_type', true);
                        $salary_min = get_post_meta(get_the_ID(), 'salary_min', true);
                        $salary_max = get_post_meta(get_the_ID(), 'salary_max', true);
                        $salary_period = get_post_meta(get_the_ID(), 'salary_period', true);
                    ?>
                        <div class="col-md-6">
                            <div class="card job-card h-100">
                                <div class="card-body">
                                    <h3 class="h5 card-title mb-3">
                                        <a href="<?php the_permalink(); ?>" class="text-decoration-none">
                                            <?php the_title(); ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="company-info mb-3">
                                        <span class="company-name text-muted">
                                            <i class="bi bi-building me-2"></i><?php echo esc_html($company_name); ?>
                                        </span>
                                    </div>

                                    <div class="job-meta mb-3">
                                        <span class="badge bg-primary me-2"><?php echo esc_html($job_types[$job_type]); ?></span>
                                        <?php if ($remote_option === 'yes'): ?>
                                            <span class="badge bg-success me-2">Remote</span>
                                        <?php elseif ($remote_option === 'hybrid'): ?>
                                            <span class="badge bg-info me-2">Hybrid</span>
                                        <?php endif; ?>
                                        <?php
                                        $job_industries = get_the_terms(get_the_ID(), 'industry');
                                        if ($job_industries && !is_wp_error($job_industries)) {
                                            foreach ($job_industries as $industry) {
                                                echo '<span class="badge bg-secondary me-2">' . esc_html($industry->name) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>

                                    <div class="job-details">
                                        <?php if ($job_location): ?>
                                            <p class="mb-2">
                                                <i class="bi bi-geo-alt me-2"></i><?php echo esc_html($job_location); ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if ($salary_type !== 'exempt'): ?>
                                            <p class="mb-2">
                                                <i class="bi bi-currency-dollar me-2"></i>
                                                <?php
                                                if ($salary_type === 'fixed') {
                                                    echo esc_html(number_format($salary_min) . ' per ' . $salary_period);
                                                } else {
                                                    echo esc_html(number_format($salary_min) . ' - ' . number_format($salary_max) . ' per ' . $salary_period);
                                                }
                                                ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="job-excerpt mt-3">
                                        <?php echo wp_trim_words(get_the_content(), 20); ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Posted <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')); ?> ago
                                        </small>
                                        <a href="<?php the_permalink(); ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($jobs_query->max_num_pages > 1): ?>
                    <div class="pagination-wrapper mt-5">
                        <?php
                        echo paginate_links(array(
                            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                            'format' => '?paged=%#%',
                            'current' => max(1, get_query_var('paged')),
                            'total' => $jobs_query->max_num_pages,
                            'prev_text' => '<i class="bi bi-chevron-left"></i> Previous',
                            'next_text' => 'Next <i class="bi bi-chevron-right"></i>',
                            'type' => 'list',
                            'end_size' => 3,
                            'mid_size' => 3
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
            <?php wp_reset_postdata(); ?>
        </div>
    </section>

    <!-- Featured Categories Section -->
    <section class="featured-categories bg-light py-5">
        <div class="container">
            <h2 class="h3 text-center mb-4">Browse by Industry</h2>
            <div class="row g-4">
                <?php
                $featured_industries = get_terms(array(
                    'taxonomy' => 'industry',
                    'hide_empty' => true,
                    'number' => 8,
                    'orderby' => 'count',
                    'order' => 'DESC'
                ));

                foreach ($featured_industries as $industry):
                    $industry_jobs = get_posts(array(
                        'post_type' => 'jobs',
                        'posts_per_page' => -1,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'industry',
                                'field' => 'term_id',
                                'terms' => $industry->term_id
                            )
                        ),
                        'meta_query' => array(
                            array(
                                'key' => 'job_status',
                                'value' => 'active',
                                'compare' => '='
                            )
                        )
                    ));
                ?>
                    <div class="col-md-3 col-sm-6">
                        <a href="<?php echo esc_url(add_query_arg('industry', $industry->slug, home_url('/'))); ?>" 
                           class="text-decoration-none">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h3 class="h5 mb-3"><?php echo esc_html($industry->name); ?></h3>
                                    <p class="text-muted mb-0">
                                        <?php echo count($industry_jobs); ?> open position<?php echo count($industry_jobs) !== 1 ? 's' : ''; ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize select2 for better dropdowns
    $('.form-select').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Handle sorting
    $('#sort-jobs').change(function() {
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('orderby', $(this).val());
        window.location.href = currentUrl.toString();
    });
});
</script>

<?php get_footer(); ?> 