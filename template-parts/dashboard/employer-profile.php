<?php
/**
 * Template part for displaying employer profile form
 */

// Security check
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$employer_profile = get_posts(array(
    'post_type' => 'employer_profile',
    'author' => $current_user->ID,
    'posts_per_page' => 1
));

$editing = !empty($employer_profile);
$profile_data = array();

if ($editing) {
    $profile = $employer_profile[0];
    $profile_data = array(
        'company_name' => get_post_meta($profile->ID, 'company_name', true),
        'company_website' => get_post_meta($profile->ID, 'company_website', true),
        'company_size' => get_post_meta($profile->ID, 'company_size', true),
        'founded_year' => get_post_meta($profile->ID, 'founded_year', true),
        'company_email' => get_post_meta($profile->ID, 'company_email', true),
        'phone_number' => get_post_meta($profile->ID, 'phone_number', true),
        'address' => get_post_meta($profile->ID, 'address', true),
        'city' => get_post_meta($profile->ID, 'city', true),
        'state' => get_post_meta($profile->ID, 'state', true),
        'country' => get_post_meta($profile->ID, 'country', true),
        'postal_code' => get_post_meta($profile->ID, 'postal_code', true),
        'social_media' => get_post_meta($profile->ID, 'social_media', true),
        'description' => $profile->post_content,
        'logo' => get_post_thumbnail_id($profile->ID)
    );
}
?>

<div class="employer-profile-form">
    <h2 class="h4 mb-4"><?php echo $editing ? 'Update Company Profile' : 'Create Company Profile'; ?></h2>

    <form id="employer-profile-form" class="needs-validation" method="post" enctype="multipart/form-data" novalidate>
        <?php wp_nonce_field('employer_profile_nonce', 'profile_nonce'); ?>
        <input type="hidden" name="action" value="save_employer_profile">
        <?php if ($editing): ?>
            <input type="hidden" name="profile_id" value="<?php echo $profile->ID; ?>">
        <?php endif; ?>

        <div class="row g-3">
            <!-- Basic Information -->
            <div class="col-12">
                <h3 class="h5 mb-3">Basic Information</h3>
            </div>

            <!-- Company Name -->
            <div class="col-md-6">
                <label for="company_name" class="form-label">Company Name *</label>
                <input type="text" class="form-control" id="company_name" name="company_name" 
                       value="<?php echo esc_attr($profile_data['company_name'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide your company name.</div>
            </div>

            <!-- Company Website -->
            <div class="col-md-6">
                <label for="company_website" class="form-label">Company Website</label>
                <input type="url" class="form-control" id="company_website" name="company_website" 
                       value="<?php echo esc_url($profile_data['company_website'] ?? ''); ?>">
            </div>

            <!-- Company Size -->
            <div class="col-md-6">
                <label for="company_size" class="form-label">Company Size *</label>
                <select class="form-select" id="company_size" name="company_size" required>
                    <option value="">Select Company Size</option>
                    <?php
                    $sizes = array(
                        '1-10' => '1-10 employees',
                        '11-50' => '11-50 employees',
                        '51-200' => '51-200 employees',
                        '201-500' => '201-500 employees',
                        '501-1000' => '501-1000 employees',
                        '1001-5000' => '1001-5000 employees',
                        '5000+' => '5000+ employees'
                    );
                    foreach ($sizes as $value => $label):
                        $selected = isset($profile_data['company_size']) && $profile_data['company_size'] === $value ? 'selected' : '';
                    ?>
                        <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please select your company size.</div>
            </div>

            <!-- Founded Year -->
            <div class="col-md-6">
                <label for="founded_year" class="form-label">Founded Year</label>
                <input type="number" class="form-control" id="founded_year" name="founded_year" 
                       value="<?php echo esc_attr($profile_data['founded_year'] ?? ''); ?>" 
                       min="1800" max="<?php echo date('Y'); ?>">
            </div>

            <!-- Contact Information -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Contact Information</h3>
            </div>

            <!-- Company Email -->
            <div class="col-md-6">
                <label for="company_email" class="form-label">Company Email *</label>
                <input type="email" class="form-control" id="company_email" name="company_email" 
                       value="<?php echo esc_attr($profile_data['company_email'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide a valid company email.</div>
            </div>

            <!-- Phone Number -->
            <div class="col-md-6">
                <label for="phone_number" class="form-label">Phone Number *</label>
                <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                       value="<?php echo esc_attr($profile_data['phone_number'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide a phone number.</div>
            </div>

            <!-- Address -->
            <div class="col-12">
                <label for="address" class="form-label">Address *</label>
                <input type="text" class="form-control" id="address" name="address" 
                       value="<?php echo esc_attr($profile_data['address'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide your company address.</div>
            </div>

            <!-- City -->
            <div class="col-md-6">
                <label for="city" class="form-label">City *</label>
                <input type="text" class="form-control" id="city" name="city" 
                       value="<?php echo esc_attr($profile_data['city'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide your city.</div>
            </div>

            <!-- State/Province -->
            <div class="col-md-6">
                <label for="state" class="form-label">State/Province *</label>
                <input type="text" class="form-control" id="state" name="state" 
                       value="<?php echo esc_attr($profile_data['state'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide your state/province.</div>
            </div>

            <!-- Country -->
            <div class="col-md-6">
                <label for="country" class="form-label">Country *</label>
                <input type="text" class="form-control" id="country" name="country" 
                       value="<?php echo esc_attr($profile_data['country'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide your country.</div>
            </div>

            <!-- Postal Code -->
            <div class="col-md-6">
                <label for="postal_code" class="form-label">Postal Code *</label>
                <input type="text" class="form-control" id="postal_code" name="postal_code" 
                       value="<?php echo esc_attr($profile_data['postal_code'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide your postal code.</div>
            </div>

            <!-- Social Media -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Social Media</h3>
            </div>

            <!-- LinkedIn -->
            <div class="col-md-6">
                <label for="linkedin" class="form-label">LinkedIn</label>
                <input type="url" class="form-control" id="linkedin" name="social_media[linkedin]" 
                       value="<?php echo esc_url($profile_data['social_media']['linkedin'] ?? ''); ?>">
            </div>

            <!-- Twitter -->
            <div class="col-md-6">
                <label for="twitter" class="form-label">Twitter</label>
                <input type="url" class="form-control" id="twitter" name="social_media[twitter]" 
                       value="<?php echo esc_url($profile_data['social_media']['twitter'] ?? ''); ?>">
            </div>

            <!-- Facebook -->
            <div class="col-md-6">
                <label for="facebook" class="form-label">Facebook</label>
                <input type="url" class="form-control" id="facebook" name="social_media[facebook]" 
                       value="<?php echo esc_url($profile_data['social_media']['facebook'] ?? ''); ?>">
            </div>

            <!-- Instagram -->
            <div class="col-md-6">
                <label for="instagram" class="form-label">Instagram</label>
                <input type="url" class="form-control" id="instagram" name="social_media[instagram]" 
                       value="<?php echo esc_url($profile_data['social_media']['instagram'] ?? ''); ?>">
            </div>

            <!-- Company Logo -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Company Logo</h3>
                <div class="mb-3">
                    <?php if (!empty($profile_data['logo'])): ?>
                        <div class="current-logo mb-3">
                            <?php echo wp_get_attachment_image($profile_data['logo'], 'thumbnail'); ?>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/*">
                    <div class="form-text">Recommended size: 400x400 pixels</div>
                </div>
            </div>

            <!-- Company Description -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Company Description</h3>
                <?php 
                wp_editor($profile_data['description'] ?? '', 'company_description', array(
                    'media_buttons' => false,
                    'textarea_name' => 'company_description',
                    'textarea_rows' => 10,
                    'teeny' => true,
                    'quicktags' => false
                ));
                ?>
            </div>

            <!-- Industry -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Industry</h3>
                <select class="form-select" id="industry" name="industry[]" multiple required>
                    <?php 
                    $industries = get_terms(array(
                        'taxonomy' => 'industry',
                        'hide_empty' => false
                    ));
                    
                    $selected_industries = array();
                    if ($editing) {
                        $selected_industries = wp_get_object_terms($profile->ID, 'industry', array('fields' => 'ids'));
                    }
                    
                    foreach ($industries as $industry) {
                        $selected = in_array($industry->term_id, $selected_industries) ? 'selected' : '';
                        echo '<option value="' . esc_attr($industry->term_id) . '" ' . $selected . '>' . 
                             esc_html($industry->name) . '</option>';
                    }
                    ?>
                </select>
                <div class="invalid-feedback">Please select at least one industry.</div>
            </div>

            <!-- Submit Button -->
            <div class="col-12">
                <hr class="my-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i><?php echo $editing ? 'Update Profile' : 'Create Profile'; ?>
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
        $('#industry').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select industries'
        });

        // Form validation and submission
        $('#employer-profile-form').submit(function(e) {
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
                        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...'
                    );
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $('button[type="submit"]').prop('disabled', false).html(
                        '<i class="bi bi-check-circle me-2"></i><?php echo $editing ? 'Update Profile' : 'Create Profile'; ?>'
                    );
                }
            });
        });
    });
    </script>
</div> 