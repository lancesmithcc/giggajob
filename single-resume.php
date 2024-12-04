<?php
/**
 * Template for displaying single resume posts
 */

get_header();
?>

<div class="resume-single">
    <?php
    while (have_posts()) :
        the_post();
        get_template_part('template-parts/content', 'resume');
    endwhile;
    ?>
</div>

<?php
get_footer(); 