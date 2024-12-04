<?php
/**
 * Template part for displaying job submission form
 */

// Security check
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$employer_profile = get_posts(array(
    'post_type' => 'employer_profile',
    'author' => $current_user->ID,
    'posts_per_page' => 1
));

// Get company name from employer profile
$company_name = '';
if (!empty($employer_profile)) {
    $company_name = get_post_meta($employer_profile[0]->ID, 'company_name', true);
}

// Get all job categories and industries
$job_categories = get_terms(array(
    'taxonomy' => 'job_category',
    'hide_empty' => false,
));

$industries = get_terms(array(
    'taxonomy' => 'industry',
    'hide_empty' => false,
));

// Organize terms into hierarchy
function organize_terms_hierarchically($terms, $parent = 0) {
    $hierarchy = array();
    foreach ($terms as $term) {
        if ($term->parent == $parent) {
            $term->children = organize_terms_hierarchically($terms, $term->term_id);
            $hierarchy[] = $term;
        }
    }
    return $hierarchy;
}

$hierarchical_categories = organize_terms_hierarchically($job_categories);
$hierarchical_industries = organize_terms_hierarchically($industries);

// Function to output hierarchical options
function output_hierarchical_options($terms, $level = 0) {
    foreach ($terms as $term) {
        echo '<option value="' . esc_attr($term->term_id) . '">' . 
             str_repeat('&mdash; ', $level) . esc_html($term->name) . 
             '</option>';
        if (!empty($term->children)) {
            output_hierarchical_options($term->children, $level + 1);
        }
    }
}

// Get job data if editing
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$editing = false;
$job_data = array();

if ($job_id) {
    $job = get_post($job_id);
    if ($job && $job->post_author == $current_user->ID) {
        $editing = true;
        $job_data = array(
            'title' => $job->post_title,
            'description' => $job->post_content,
            'company_name' => get_post_meta($job_id, 'company_name', true),
            'job_type' => get_post_meta($job_id, 'job_type', true),
            'job_location' => get_post_meta($job_id, 'job_location', true),
            'remote_option' => get_post_meta($job_id, 'remote_option', true),
            'salary' => get_post_meta($job_id, 'salary', true),
            'categories' => wp_get_post_terms($job_id, 'job_category', array('fields' => 'ids')),
            'industries' => wp_get_post_terms($job_id, 'industry', array('fields' => 'ids'))
        );
    }
}
?>

<div class="post-job-form">
    <h2 class="h4 mb-4">Post a New Job</h2>

    <?php if (empty($employer_profile)): ?>
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Complete Your Profile First</h4>
            <p class="mb-0">Please complete your employer profile before posting a job.</p>
            <hr>
            <a href="<?php echo add_query_arg('tab', 'profile'); ?>" class="btn btn-warning">Complete Profile</a>
        </div>
    <?php else: ?>
        <form id="post-job-form" class="needs-validation" method="post" novalidate>
            <?php wp_nonce_field('post_job_nonce', 'job_nonce'); ?>
            <input type="hidden" name="action" value="post_job">
            <?php if ($editing): ?>
                <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
            <?php endif; ?>
            
            <div class="row g-3">
                <!-- Job Title -->
                <div class="col-12">
                    <label for="job_title" class="form-label">Job Title *</label>
                    <input type="text" class="form-control" id="job_title" name="job_title" 
                           value="<?php echo $editing ? esc_attr($job_data['title']) : ''; ?>" required>
                    <div class="invalid-feedback">Please provide a job title.</div>
                </div>

                <!-- Company Name -->
                <div class="col-md-6">
                    <label for="company_name" class="form-label">Company Name *</label>
                    <input type="text" class="form-control" id="company_name" name="company_name" 
                           value="<?php echo esc_attr($company_name); ?>" required readonly>
                </div>

                <!-- Job Type -->
                <div class="col-md-6">
                    <label for="job_type" class="form-label">Job Type *</label>
                    <select class="form-select" id="job_type" name="job_type" required>
                        <option value="">Select Job Type</option>
                        <?php
                        $job_types = array(
                            'full-time' => 'Full Time',
                            'part-time' => 'Part Time',
                            'contract' => 'Contract',
                            'temporary' => 'Temporary',
                            'internship' => 'Internship'
                        );
                        foreach ($job_types as $value => $label):
                            $selected = $editing && $job_data['job_type'] === $value ? 'selected' : '';
                        ?>
                            <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a job type.</div>
                </div>

                <!-- Job Category -->
                <div class="col-md-6">
                    <label for="job_category" class="form-label">Job Category *</label>
                    <select class="form-select" id="job_category" name="job_category[]" multiple required>
                        <?php 
                        foreach ($hierarchical_categories as $category) {
                            $selected = $editing && in_array($category->term_id, $job_data['categories']) ? 'selected' : '';
                            echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . 
                                 esc_html($category->name) . '</option>';
                            if (!empty($category->children)) {
                                foreach ($category->children as $child) {
                                    $selected = $editing && in_array($child->term_id, $job_data['categories']) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($child->term_id) . '" ' . $selected . '>' . 
                                         str_repeat('&mdash; ', 1) . esc_html($child->name) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                    <div class="invalid-feedback">Please select at least one job category.</div>
                </div>

                <!-- Industry -->
                <div class="col-md-6">
                    <label for="industry" class="form-label">Industry *</label>
                    <select class="form-select" id="industry" name="industry[]" multiple required>
                        <?php 
                        foreach ($hierarchical_industries as $industry) {
                            $selected = $editing && in_array($industry->term_id, $job_data['industries']) ? 'selected' : '';
                            echo '<option value="' . esc_attr($industry->term_id) . '" ' . $selected . '>' . 
                                 esc_html($industry->name) . '</option>';
                            if (!empty($industry->children)) {
                                foreach ($industry->children as $child) {
                                    $selected = $editing && in_array($child->term_id, $job_data['industries']) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($child->term_id) . '" ' . $selected . '>' . 
                                         str_repeat('&mdash; ', 1) . esc_html($child->name) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                    <div class="invalid-feedback">Please select at least one industry.</div>
                </div>

                <!-- Location -->
                <div class="col-md-6">
                    <label for="job_location" class="form-label">Location *</label>
                    <input type="text" class="form-control" id="job_location" name="job_location" 
                           value="<?php echo $editing ? esc_attr($job_data['job_location']) : ''; ?>" required>
                    <div class="invalid-feedback">Please provide a job location.</div>
                </div>

                <!-- Salary Range -->
                <div class="col-md-6">
                    <label for="salary_type" class="form-label">Salary Information *</label>
                    <div class="input-group">
                        <select class="form-select" id="salary_type" name="salary_type" required>
                            <option value="range">Salary Range</option>
                            <option value="fixed">Fixed Amount</option>
                            <option value="exempt" <?php echo $editing && $job_data['salary'] === 'legal exemption for non-disclosure' ? 'selected' : ''; ?>>Legal Exemption</option>
                        </select>
                        <input type="number" class="form-control salary-input" id="salary_min" name="salary_min" 
                               placeholder="Min" <?php echo $editing && $job_data['salary'] !== 'legal exemption for non-disclosure' ? 'value="' . esc_attr(explode(' - ', $job_data['salary'])[0]) . '"' : ''; ?>>
                        <input type="number" class="form-control salary-input" id="salary_max" name="salary_max" 
                               placeholder="Max" <?php echo $editing && $job_data['salary'] !== 'legal exemption for non-disclosure' ? 'value="' . esc_attr(explode(' - ', $job_data['salary'])[1]) . '"' : ''; ?>>
                        <select class="form-select salary-period" id="salary_period" name="salary_period">
                            <option value="year">Per Year</option>
                            <option value="month">Per Month</option>
                            <option value="hour">Per Hour</option>
                        </select>
                    </div>
                    <div class="invalid-feedback">Please provide salary information.</div>
                </div>

                <!-- Remote Options -->
                <div class="col-12">
                    <label class="form-label d-block">Remote Work Options *</label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="remote_option" id="remote_no" value="no" 
                               <?php echo $editing && $job_data['remote_option'] === 'no' ? 'checked' : ''; ?> required>
                        <label class="btn btn-outline-secondary" for="remote_no">No Remote Work</label>

                        <input type="radio" class="btn-check" name="remote_option" id="remote_hybrid" value="hybrid"
                               <?php echo $editing && $job_data['remote_option'] === 'hybrid' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-secondary" for="remote_hybrid">Hybrid</label>

                        <input type="radio" class="btn-check" name="remote_option" id="remote_yes" value="yes"
                               <?php echo $editing && $job_data['remote_option'] === 'yes' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-secondary" for="remote_yes">Fully Remote</label>
                    </div>
                    <div class="invalid-feedback">Please select a remote work option.</div>
                </div>

                <!-- Job Description -->
                <div class="col-12">
                    <label for="job_description" class="form-label">Job Description *</label>
                    <?php 
                    wp_editor($editing ? $job_data['description'] : '', 'job_description', array(
                        'media_buttons' => false,
                        'textarea_name' => 'job_description',
                        'textarea_rows' => 10,
                        'teeny' => true,
                        'quicktags' => false
                    ));
                    ?>
                    <div class="invalid-feedback">Please provide a job description.</div>
                </div>

                <!-- Submit Button -->
                <div class="col-12">
                    <hr class="my-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i><?php echo $editing ? 'Update Job' : 'Post Job'; ?>
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="history.back()">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                </div>
            </div>
        </form>

        <!-- Form Submission JavaScript -->
        <script>
        jQuery(document).ready(function($) {
            // Initialize Select2 for multiple select fields
            $('#job_category, #industry').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select options'
            });

            // Handle salary type changes
            $('#salary_type').change(function() {
                if ($(this).val() === 'exempt') {
                    $('.salary-input, .salary-period').prop('disabled', true);
                } else {
                    $('.salary-input, .salary-period').prop('disabled', false);
                }
            });

            // Form validation and submission
            $('#post-job-form').submit(function(e) {
                e.preventDefault();
                
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    $(this).addClass('was-validated');
                    return;
                }

                var formData = new FormData(this);
                
                $.ajax({
                    url: giggajob_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('button[type="submit"]').prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Posting...'
                        );
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    },
                    complete: function() {
                        $('button[type="submit"]').prop('disabled', false).html(
                            '<i class="bi bi-check-circle me-2"></i>Post Job'
                        );
                    }
                });
            });
        });
        </script>
    <?php endif; ?>
</div> 