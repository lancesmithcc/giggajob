<?php
/**
 * Template for displaying single employer profile posts
 */

get_header();
?>

<div class="employer-profile-single">
    <?php
    while (have_posts()) :
        the_post();
        get_template_part('template-parts/content', 'employer_profile');
    endwhile;
    ?>
</div>

<?php
get_footer(); 