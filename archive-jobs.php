<?php
/**
 * Template for displaying job archives
 */

get_header();
?>

<div class="jobs-archive py-4">
    <header class="page-header mb-4">
        <div class="container">
            <h1 class="page-title h2">Job Listings</h1>
            
            <!-- Job Search Form -->
            <form role="search" method="get" class="job-search-form mt-4" action="<?php echo esc_url(home_url('/')); ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Keywords" name="s" value="<?php echo get_search_query(); ?>">
                        <input type="hidden" name="post_type" value="jobs">
                    </div>
                    <div class="col-md-3">
                        <select name="job_type" class="form-select">
                            <option value="">All Job Types</option>
                            <option value="full-time" <?php selected(get_query_var('job_type'), 'full-time'); ?>>Full Time</option>
                            <option value="part-time" <?php selected(get_query_var('job_type'), 'part-time'); ?>>Part Time</option>
                            <option value="contract" <?php selected(get_query_var('job_type'), 'contract'); ?>>Contract</option>
                            <option value="temporary" <?php selected(get_query_var('job_type'), 'temporary'); ?>>Temporary</option>
                            <option value="internship" <?php selected(get_query_var('job_type'), 'internship'); ?>>Internship</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Location" name="job_location" value="<?php echo get_query_var('job_location'); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </header>

    <div class="container">
        <?php if (have_posts()) : ?>
            <div class="job-listings">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('template-parts/content', 'jobs');
                endwhile;
                ?>
            </div>

            <?php
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => __('Previous', 'giggajob'),
                'next_text' => __('Next', 'giggajob'),
                'class' => 'mt-4',
            ));
            ?>

        <?php else : ?>
            <div class="no-jobs-found text-center py-5">
                <h2 class="h4">No Jobs Found</h2>
                <p class="text-muted">Try adjusting your search criteria or browse all available positions.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer(); 