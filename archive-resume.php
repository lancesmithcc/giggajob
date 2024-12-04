<?php
/**
 * Template for displaying resume archives
 */

get_header();

// Check if user has permission to view resumes
if (!current_user_can('administrator') && !current_user_can('employer')) {
    wp_redirect(home_url());
    exit;
}
?>

<div class="resumes-archive py-4">
    <header class="page-header mb-4">
        <div class="container">
            <h1 class="page-title h2">Resume Database</h1>
            
            <!-- Resume Search Form -->
            <form role="search" method="get" class="resume-search-form mt-4" action="<?php echo esc_url(home_url('/')); ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Keywords or Skills" name="s" value="<?php echo get_search_query(); ?>">
                        <input type="hidden" name="post_type" value="resume">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Location" name="location" value="<?php echo get_query_var('location'); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="experience_level" class="form-select">
                            <option value="">Experience Level</option>
                            <option value="entry" <?php selected(get_query_var('experience_level'), 'entry'); ?>>Entry Level</option>
                            <option value="intermediate" <?php selected(get_query_var('experience_level'), 'intermediate'); ?>>Intermediate</option>
                            <option value="senior" <?php selected(get_query_var('experience_level'), 'senior'); ?>>Senior</option>
                        </select>
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
            <div class="resume-listings">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('template-parts/content', 'resume');
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
            <div class="no-resumes-found text-center py-5">
                <h2 class="h4">No Resumes Found</h2>
                <p class="text-muted">Try adjusting your search criteria or browse all available resumes.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer(); 