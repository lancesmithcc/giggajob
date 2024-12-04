<?php
/**
 * The template for displaying all single posts
 */

get_header();
?>

<div class="content-area py-5">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header mb-4">
                    <h1 class="entry-title">
                        <?php the_title(); ?>
                    </h1>

                    <div class="entry-meta text-muted mb-3">
                        <small>
                            Posted on <?php echo get_the_date(); ?> by <?php the_author(); ?>
                            <?php
                            $categories_list = get_the_category_list(', ');
                            if ($categories_list) {
                                echo ' in ' . $categories_list;
                            }
                            ?>
                        </small>
                    </div>
                </header>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="entry-thumbnail mb-4">
                        <?php the_post_thumbnail('large', ['class' => 'img-fluid rounded']); ?>
                    </div>
                <?php endif; ?>

                <div class="entry-content">
                    <?php
                    the_content();

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . __('Pages:', 'giggajob'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>

                <footer class="entry-footer mt-4">
                    <?php
                    $tags_list = get_the_tag_list('', ', ');
                    if ($tags_list) {
                        echo '<div class="tags-links mb-3">';
                        echo '<strong>Tags:</strong> ' . $tags_list;
                        echo '</div>';
                    }
                    ?>

                    <nav class="navigation post-navigation">
                        <div class="nav-links">
                            <?php
                            $prev_post = get_previous_post();
                            if (!empty($prev_post)) :
                            ?>
                                <div class="nav-previous">
                                    <a href="<?php echo get_permalink($prev_post); ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-left me-2"></i>
                                        <?php echo get_the_title($prev_post); ?>
                                    </a>
                                </div>
                            <?php endif;

                            $next_post = get_next_post();
                            if (!empty($next_post)) :
                            ?>
                                <div class="nav-next">
                                    <a href="<?php echo get_permalink($next_post); ?>" class="btn btn-outline-primary">
                                        <?php echo get_the_title($next_post); ?>
                                        <i class="bi bi-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </nav>
                </footer>
            </article>

            <?php
            // If comments are open or we have at least one comment, load up the comment template.
            if (comments_open() || get_comments_number()) :
                comments_template();
            endif;
            ?>

        <?php endwhile; ?>
    </div>
</div>

<?php get_footer(); ?> 