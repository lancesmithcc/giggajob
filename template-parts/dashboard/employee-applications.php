<?php
/**
 * Template part for displaying employee job applications
 */

// Security check
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Get applications
$args = array(
    'post_type' => 'job_application',
    'posts_per_page' => 10,
    'paged' => $paged,
    'meta_query' => array(
        array(
            'key' => 'applicant_id',
            'value' => $current_user->ID
        )
    ),
    'orderby' => 'date',
    'order' => 'DESC'
);

$applications_query = new WP_Query($args);
?>

<div class="applications-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">My Applications</h2>
    </div>

    <?php if ($applications_query->have_posts()) : ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Applied Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($applications_query->have_posts()) : $applications_query->the_post(); 
                        $job_id = get_post_meta(get_the_ID(), 'job_id', true);
                        $job = get_post($job_id);
                        $company_name = get_post_meta($job_id, 'company_name', true);
                        $status = get_post_meta(get_the_ID(), 'status', true);
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo get_the_title($job_id); ?></strong>
                                <div class="small text-muted">
                                    <?php echo get_post_meta($job_id, 'job_location', true); ?>
                                </div>
                            </td>
                            <td><?php echo esc_html($company_name); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $status === 'pending' ? 'warning' : 
                                        ($status === 'accepted' ? 'success' : 
                                        ($status === 'rejected' ? 'danger' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td><?php echo get_the_date(); ?></td>
                            <td>
                                <a href="<?php echo get_permalink($job_id); ?>" 
                                   class="btn btn-sm btn-outline-primary" 
                                   title="View Job">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php
        // Pagination
        $big = 999999999;
        echo '<div class="pagination justify-content-center">';
        echo paginate_links(array(
            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format' => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total' => $applications_query->max_num_pages,
            'prev_text' => '<i class="bi bi-chevron-left"></i>',
            'next_text' => '<i class="bi bi-chevron-right"></i>',
            'type' => 'list',
            'end_size' => 3,
            'mid_size' => 2
        ));
        echo '</div>';
        ?>

    <?php else: ?>
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading"><i class="bi bi-info-circle me-2"></i>No Applications Found</h4>
            <p class="mb-0">You haven't applied to any jobs yet.</p>
        </div>
    <?php endif; 
    wp_reset_postdata();
    ?>
</div> 