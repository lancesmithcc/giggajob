<?php
/**
 * Template part for displaying employer profiles
 */

if (!defined('ABSPATH')) exit;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('employer-profile mb-4 p-4 border rounded'); ?>>
    <header class="employer-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-3 text-center mb-3 mb-md-0">
                <?php if (has_post_thumbnail()): ?>
                    <?php the_post_thumbnail('medium', ['class' => 'img-fluid rounded']); ?>
                <?php endif; ?>
            </div>
            <div class="col-md-9">
                <h1 class="employer-title h3 mb-2">
                    <?php 
                    $company_name = get_post_meta(get_the_ID(), 'company_name', true);
                    echo $company_name ? esc_html($company_name) : get_the_title();
                    ?>
                </h1>

                <?php 
                $industry = get_post_meta(get_the_ID(), 'industry', true);
                if ($industry): 
                ?>
                    <p class="industry mb-2">
                        <i class="bi bi-briefcase me-2"></i>
                        <?php echo esc_html($industry); ?>
                    </p>
                <?php endif; ?>

                <?php 
                $location = get_post_meta(get_the_ID(), 'address', true);
                if ($location): 
                ?>
                    <p class="location mb-2">
                        <i class="bi bi-geo-alt me-2"></i>
                        <?php echo esc_html($location); ?>
                    </p>
                <?php endif; ?>

                <?php 
                $phone = get_post_meta(get_the_ID(), 'phone_number', true);
                if ($phone): 
                ?>
                    <p class="phone mb-2">
                        <i class="bi bi-telephone me-2"></i>
                        <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                    </p>
                <?php endif; ?>

                <?php 
                $social_links = get_post_meta(get_the_ID(), 'social_media_links', true);
                if ($social_links && is_array($social_links)): 
                ?>
                    <div class="social-links mt-3">
                        <?php foreach ($social_links as $platform => $url): ?>
                            <a href="<?php echo esc_url($url); ?>" class="btn btn-outline-secondary btn-sm me-2" target="_blank">
                                <i class="bi bi-<?php echo esc_attr($platform); ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="employer-content">
        <?php if (get_the_content()): ?>
            <section class="company-description mb-4">
                <h2 class="h4 mb-3">About the Company</h2>
                <div class="description-content">
                    <?php the_content(); ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (is_singular()): ?>
            <?php
            // Get current jobs from this employer
            $args = array(
                'post_type' => 'jobs',
                'posts_per_page' => 5,
                'meta_query' => array(
                    array(
                        'key' => 'company_name',
                        'value' => $company_name,
                        'compare' => '='
                    )
                )
            );
            $current_jobs = new WP_Query($args);
            
            if ($current_jobs->have_posts()): 
            ?>
                <section class="current-openings mb-4">
                    <h2 class="h4 mb-3">Current Job Openings</h2>
                    <div class="job-listings">
                        <?php while ($current_jobs->have_posts()): $current_jobs->the_post(); ?>
                            <div class="job-item mb-3 p-3 bg-light rounded">
                                <h3 class="h5 mb-2">
                                    <a href="<?php the_permalink(); ?>" class="text-decoration-none">
                                        <?php the_title(); ?>
                                    </a>
                                </h3>
                                <div class="job-meta text-muted small">
                                    <?php
                                    $job_type = get_post_meta(get_the_ID(), 'job_type', true);
                                    $job_location = get_post_meta(get_the_ID(), 'job_location', true);
                                    ?>
                                    <?php if ($job_type): ?>
                                        <span class="job-type me-3">
                                            <i class="bi bi-briefcase"></i> <?php echo esc_html($job_type); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($job_location): ?>
                                        <span class="job-location">
                                            <i class="bi bi-geo-alt"></i> <?php echo esc_html($job_location); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</article> 