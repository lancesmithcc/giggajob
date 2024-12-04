<?php
/**
 * Template for displaying employer profile archives
 */

get_header();
?>

<div class="employer-profiles-archive py-4">
    <header class="page-header mb-4">
        <div class="container">
            <h1 class="page-title h2">Companies</h1>
            
            <!-- Employer Search Form -->
            <form role="search" method="get" class="employer-search-form mt-4" action="<?php echo esc_url(home_url('/')); ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Company Name" name="s" value="<?php echo get_search_query(); ?>">
                        <input type="hidden" name="post_type" value="employer_profile">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Location" name="location" value="<?php echo get_query_var('location'); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="industry" class="form-select">
                            <option value="">All Industries</option>
                            <?php
                            $industries = get_terms(array(
                                'taxonomy' => 'industry',
                                'hide_empty' => true,
                            ));
                            
                            if ($industries && !is_wp_error($industries)) {
                                foreach ($industries as $industry) {
                                    echo '<option value="' . esc_attr($industry->slug) . '" ' . 
                                         selected(get_query_var('industry'), $industry->slug, false) . '>' . 
                                         esc_html($industry->name) . '</option>';
                                }
                            }
                            ?>
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
            <div class="employer-listings row">
                <?php
                while (have_posts()) :
                    the_post();
                    echo '<div class="col-md-6 col-lg-4 mb-4">';
                    get_template_part('template-parts/content', 'employer_profile');
                    echo '</div>';
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
            <div class="no-employers-found text-center py-5">
                <h2 class="h4">No Companies Found</h2>
                <p class="text-muted">Try adjusting your search criteria or browse all companies.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer(); 