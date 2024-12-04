<?php
/**
 * Template part for displaying resumes
 */

if (!defined('ABSPATH')) exit;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('resume-profile mb-4 p-4 border rounded'); ?>>
    <header class="resume-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-2 text-center mb-3 mb-md-0">
                <?php if (has_post_thumbnail()): ?>
                    <?php the_post_thumbnail('thumbnail', ['class' => 'rounded-circle img-fluid']); ?>
                <?php endif; ?>
            </div>
            <div class="col-md-10">
                <h1 class="resume-title h3 mb-2">
                    <?php 
                    $your_name = get_post_meta(get_the_ID(), 'your_name', true);
                    echo $your_name ? esc_html($your_name) : get_the_title();
                    ?>
                </h1>
                <?php 
                $tagline = get_post_meta(get_the_ID(), 'tagline', true);
                if ($tagline): 
                ?>
                    <p class="lead mb-2"><?php echo esc_html($tagline); ?></p>
                <?php endif; ?>

                <?php 
                $social_links = get_post_meta(get_the_ID(), 'social_media_links', true);
                if ($social_links && is_array($social_links)): 
                ?>
                    <div class="social-links">
                        <?php foreach ($social_links as $platform => $url): ?>
                            <a href="<?php echo esc_url($url); ?>" class="btn btn-outline-secondary btn-sm me-2 mb-2" target="_blank">
                                <i class="bi bi-<?php echo esc_attr($platform); ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="resume-content">
        <?php 
        $bio = get_post_meta(get_the_ID(), 'bio', true);
        if ($bio): 
        ?>
            <section class="bio mb-4">
                <h2 class="h4 mb-3">About Me</h2>
                <div class="bio-content">
                    <?php echo wp_kses_post($bio); ?>
                </div>
            </section>
        <?php endif; ?>

        <?php 
        $skills = get_post_meta(get_the_ID(), 'skills', true);
        if ($skills && is_array($skills)): 
        ?>
            <section class="skills mb-4">
                <h2 class="h4 mb-3">Skills</h2>
                <div class="skills-content">
                    <?php foreach ($skills as $skill): ?>
                        <span class="badge bg-primary me-2 mb-2"><?php echo esc_html($skill); ?></span>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php 
        $experience = get_post_meta(get_the_ID(), 'experience', true);
        if ($experience && is_array($experience)): 
        ?>
            <section class="experience mb-4">
                <h2 class="h4 mb-3">Experience</h2>
                <?php foreach ($experience as $job): ?>
                    <div class="experience-item mb-3 p-3 bg-light rounded">
                        <h3 class="h5 mb-1"><?php echo esc_html($job['title']); ?></h3>
                        <p class="company mb-1"><?php echo esc_html($job['company']); ?></p>
                        <p class="period text-muted mb-2">
                            <?php echo esc_html($job['start_date']); ?> - 
                            <?php echo isset($job['end_date']) ? esc_html($job['end_date']) : 'Present'; ?>
                        </p>
                        <div class="description">
                            <?php echo wp_kses_post($job['description']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php 
        $education = get_post_meta(get_the_ID(), 'education', true);
        if ($education && is_array($education)): 
        ?>
            <section class="education mb-4">
                <h2 class="h4 mb-3">Education</h2>
                <?php foreach ($education as $edu): ?>
                    <div class="education-item mb-3">
                        <h3 class="h5 mb-1"><?php echo esc_html($edu['degree']); ?></h3>
                        <p class="institution mb-1"><?php echo esc_html($edu['institution']); ?></p>
                        <p class="period text-muted mb-2"><?php echo esc_html($edu['year']); ?></p>
                        <?php if (isset($edu['description'])): ?>
                            <div class="description">
                                <?php echo wp_kses_post($edu['description']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php 
        $certifications = get_post_meta(get_the_ID(), 'awards_certifications', true);
        if ($certifications && is_array($certifications)): 
        ?>
            <section class="certifications">
                <h2 class="h4 mb-3">Certifications & Awards</h2>
                <?php foreach ($certifications as $cert): ?>
                    <div class="certification-item mb-3">
                        <h3 class="h5 mb-1"><?php echo esc_html($cert['title']); ?></h3>
                        <p class="issuer mb-1"><?php echo esc_html($cert['issuer']); ?></p>
                        <p class="date text-muted"><?php echo esc_html($cert['date']); ?></p>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</article> 