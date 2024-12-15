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

// Get job categories
$job_categories = get_terms(array(
    'taxonomy' => 'job_category',
    'hide_empty' => false,
));

if (!is_wp_error($job_categories)) {
    // Organize terms into hierarchy
    function organize_terms_hierarchically($terms, $parent = 0) {
        $hierarchy = array();
        foreach ($terms as $term) {
            if (is_object($term) && isset($term->parent) && $term->parent == $parent) {
                $children = organize_terms_hierarchically($terms, $term->term_id);
                if (!empty($children)) {
                    $term->children = $children;
                }
                $hierarchy[] = $term;
            }
        }
        return $hierarchy;
    }

    $hierarchical_categories = organize_terms_hierarchically($job_categories);
} else {
    $hierarchical_categories = array();
}

// Get industries
$industries = get_terms(array(
    'taxonomy' => 'industry',
    'hide_empty' => false,
));

if (!is_wp_error($industries)) {
    // Organize industries into hierarchy
    $hierarchical_industries = organize_terms_hierarchically($industries);
} else {
    $hierarchical_industries = array();
}

// Function to output hierarchical select options
function output_hierarchical_options($terms, $selected_terms = array(), $level = 0) {
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
    foreach ($terms as $term) {
        if (is_object($term) && isset($term->term_id) && isset($term->name)) {
            $selected = in_array($term->term_id, $selected_terms) ? 'selected' : '';
            printf(
                '<option value="%d" %s>%s%s</option>',
                esc_attr($term->term_id),
                $selected,
                $indent,
                esc_html($term->name)
            );
            if (!empty($term->children)) {
                output_hierarchical_options($term->children, $selected_terms, $level + 1);
            }
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
                        if (!empty($hierarchical_categories)) {
                            $selected_categories = $editing ? $job_data['categories'] : array();
                            output_hierarchical_options($hierarchical_categories, $selected_categories);
                        } else {
                            echo '<option value="">No categories found</option>';
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
                        if (!empty($hierarchical_industries)) {
                            $selected_industries = $editing ? $job_data['industries'] : array();
                            output_hierarchical_options($hierarchical_industries, $selected_industries);
                        } else {
                            echo '<option value="">No industries found</option>';
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

                <!-- Remote Option -->
                <div class="col-md-6">
                    <label for="remote_option" class="form-label">Remote Work *</label>
                    <select class="form-select" id="remote_option" name="remote_option" required>
                        <option value="">Select Remote Option</option>
                        <?php
                        $remote_options = array(
                            'no' => 'No remote work',
                            'hybrid' => 'Hybrid remote',
                            'full' => 'Fully remote'
                        );
                        foreach ($remote_options as $value => $label):
                            $selected = $editing && $job_data['remote_option'] === $value ? 'selected' : '';
                        ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php echo $selected; ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
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
                formData.append('action', 'post_job');
                formData.append('job_nonce', giggajob_ajax.nonce);
                
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
                            alert(response.data.message || 'An error occurred while posting the job.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        alert('An error occurred while posting the job. Please try again.');
                    },
                    complete: function() {
                        $('button[type="submit"]').prop('disabled', false).html(
                            '<i class="bi bi-check-circle me-2"></i>Post Job'
                        );
                    }
                });
            });

            // Initialize TinyMCE if it exists
            if (typeof tinyMCE !== 'undefined') {
                tinyMCE.init({
                    selector: '#job_description',
                    plugins: 'lists link',
                    toolbar: 'bold italic | bullist numlist | link',
                    menubar: false,
                    branding: false,
                    height: 300
                });
            }
        });
        </script>
    <?php endif; ?>
</div> 