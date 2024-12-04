<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <?php
            if (has_custom_logo()) {
                the_custom_logo();
            } else {
                echo '<a class="navbar-brand" href="' . esc_url(home_url('/')) . '">' . get_bloginfo('name') . '</a>';
            }
            ?>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primary-menu" aria-controls="primary-menu" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="primary-menu">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container' => false,
                    'menu_class' => 'navbar-nav ms-auto mb-2 mb-lg-0',
                    'fallback_cb' => '__return_false',
                    'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                    'depth' => 2,
                    'walker' => new Bootstrap_5_Nav_Walker()
                ));

                if (is_user_logged_in()) {
                    $current_user = wp_get_current_user();
                    if (in_array('employee', $current_user->roles)) {
                        echo '<a href="' . esc_url(home_url('/employee-dashboard')) . '" class="btn btn-primary ms-2">Dashboard</a>';
                    } elseif (in_array('employer', $current_user->roles)) {
                        echo '<a href="' . esc_url(home_url('/employer-dashboard')) . '" class="btn btn-primary ms-2">Dashboard</a>';
                    }
                    echo '<a href="' . esc_url(wp_logout_url(home_url())) . '" class="btn btn-outline-primary ms-2">Logout</a>';
                } else {
                    echo '<a href="' . esc_url(wp_login_url()) . '" class="btn btn-outline-primary ms-2">Login</a>';
                    echo '<a href="' . esc_url(home_url('/register')) . '" class="btn btn-primary ms-2">Register</a>';
                }
                ?>
            </div>
        </div>
    </nav>
</header>

<main id="main" class="site-main">
    <div class="container py-4">
