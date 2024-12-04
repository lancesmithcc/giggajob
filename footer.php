    </div><!-- .container -->
</main>

<footer class="site-footer bg-light mt-5">
    <div class="container py-4">
        <div class="row">
            <div class="col-md-4">
                <h5><?php echo get_bloginfo('name'); ?></h5>
                <p class="text-muted">Find your next opportunity or the perfect candidate.</p>
            </div>
            <div class="col-md-4">
                <h5>Quick Links</h5>
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'footer',
                    'container' => false,
                    'menu_class' => 'list-unstyled',
                    'fallback_cb' => '__return_false'
                ));
                ?>
            </div>
            <div class="col-md-4">
                <h5>Contact</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo esc_url(home_url('/contact')); ?>">Contact Us</a></li>
                    <li><a href="<?php echo esc_url(home_url('/about')); ?>">About Us</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12 text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
