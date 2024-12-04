<?php
/**
 * The main template file
 */

get_header();
?>

<div class="content-area py-5">
    <div class="container">
        <?php if (have_posts()) : ?>
            <div class="row">
                <?php while (have_posts()) : the_post(); ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <article id="post-<?php the_ID(); ?>" <?php post_class('card h-100'); ?>>
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="card-img-top">
                                    <?php the_post_thumbnail('medium', ['class' => 'img-fluid']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h2 class="card-title h5">
                                    <a href="<?php the_permalink(); ?>" class="text-decoration-none">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>
                                
                                <div class="card-text">
                                    <?php the_excerpt(); ?>
                                </div>
                            </div>
                            
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    <?php echo get_the_date(); ?> by <?php the_author(); ?>
                                </small>
                            </div>
                        </article>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => __('Previous', 'giggajob'),
                'next_text' => __('Next', 'giggajob'),
                'screen_reader_text' => __('Posts navigation', 'giggajob'),
                'class' => 'mt-4',
            ));
            ?>

        <?php else : ?>
            <div class="alert alert-info">
                <h3 class="alert-heading h5">No Content Found</h3>
                <p class="mb-0">It seems we can't find what you're looking for.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?> 