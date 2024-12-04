<?php
/**
 * Template for displaying single job posts
 */

get_header();
?>

<div class="job-single">
    <?php
    while (have_posts()) :
        the_post();
        get_template_part('template-parts/content', 'jobs');
    endwhile;
    ?>
</div>

<?php
get_footer(); 