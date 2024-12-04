<?php
/**
 * Template part for displaying resume form
 */

// Security check
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$resume = get_posts(array(
    'post_type' => 'resume',
    'author' => $current_user->ID,
    'posts_per_page' => 1
));

$editing = !empty($resume);
$resume_data = array();

if ($editing) {
    $resume = $resume[0];
    $resume_data = array(
        'full_name' => get_post_meta($resume->ID, 'full_name', true),
        'professional_title' => get_post_meta($resume->ID, 'professional_title', true),
        'email' => get_post_meta($resume->ID, 'email', true),
        'phone' => get_post_meta($resume->ID, 'phone', true),
        'location' => get_post_meta($resume->ID, 'location', true),
        'website' => get_post_meta($resume->ID, 'website', true),
        'social_media' => get_post_meta($resume->ID, 'social_media', true),
        'skills' => get_post_meta($resume->ID, 'skills', true),
        'education' => get_post_meta($resume->ID, 'education', true),
        'experience' => get_post_meta($resume->ID, 'experience', true),
        'certifications' => get_post_meta($resume->ID, 'certifications', true),
        'languages' => get_post_meta($resume->ID, 'languages', true),
        'summary' => $resume->post_content,
        'photo' => get_post_thumbnail_id($resume->ID)
    );
}
?>

<div class="resume-form">
    <h2 class="h4 mb-4"><?php echo $editing ? 'Update Resume' : 'Create Resume'; ?></h2>

    <form id="resume-form" class="needs-validation" method="post" enctype="multipart/form-data" novalidate>
        <?php wp_nonce_field('resume_nonce', 'resume_nonce'); ?>
        <input type="hidden" name="action" value="save_resume">
        <?php if ($editing): ?>
            <input type="hidden" name="resume_id" value="<?php echo $resume->ID; ?>">
        <?php endif; ?>

        <div class="row g-3">
            <!-- Personal Information -->
            <div class="col-12">
                <h3 class="h5 mb-3">Personal Information</h3>
            </div>

            <!-- Full Name -->
            <div class="col-md-6">
                <label for="full_name" class="form-label">Full Name *</label>
                <input type="text" class="form-control" id="full_name" name="full_name" 
                       value="<?php echo esc_attr($resume_data['full_name'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide your full name.</div>
            </div>

            <!-- Professional Title -->
            <div class="col-md-6">
                <label for="professional_title" class="form-label">Professional Title *</label>
                <input type="text" class="form-control" id="professional_title" name="professional_title" 
                       value="<?php echo esc_attr($resume_data['professional_title'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide your professional title.</div>
            </div>

            <!-- Email -->
            <div class="col-md-6">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo esc_attr($resume_data['email'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide a valid email address.</div>
            </div>

            <!-- Phone -->
            <div class="col-md-6">
                <label for="phone" class="form-label">Phone *</label>
                <input type="tel" class="form-control" id="phone" name="phone" 
                       value="<?php echo esc_attr($resume_data['phone'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide your phone number.</div>
            </div>

            <!-- Location -->
            <div class="col-md-6">
                <label for="location" class="form-label">Location *</label>
                <input type="text" class="form-control" id="location" name="location" 
                       value="<?php echo esc_attr($resume_data['location'] ?? ''); ?>" required>
                <div class="invalid-feedback">Please provide your location.</div>
            </div>

            <!-- Website -->
            <div class="col-md-6">
                <label for="website" class="form-label">Website</label>
                <input type="url" class="form-control" id="website" name="website" 
                       value="<?php echo esc_url($resume_data['website'] ?? ''); ?>">
            </div>

            <!-- Social Media -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Social Media</h3>
            </div>

            <!-- LinkedIn -->
            <div class="col-md-6">
                <label for="linkedin" class="form-label">LinkedIn</label>
                <input type="url" class="form-control" id="linkedin" name="social_media[linkedin]" 
                       value="<?php echo esc_url($resume_data['social_media']['linkedin'] ?? ''); ?>">
            </div>

            <!-- GitHub -->
            <div class="col-md-6">
                <label for="github" class="form-label">GitHub</label>
                <input type="url" class="form-control" id="github" name="social_media[github]" 
                       value="<?php echo esc_url($resume_data['social_media']['github'] ?? ''); ?>">
            </div>

            <!-- Profile Photo -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Profile Photo</h3>
                <div class="mb-3">
                    <?php if (!empty($resume_data['photo'])): ?>
                        <div class="current-photo mb-3">
                            <?php echo wp_get_attachment_image($resume_data['photo'], 'thumbnail'); ?>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                    <div class="form-text">Recommended size: 400x400 pixels</div>
                </div>
            </div>

            <!-- Professional Summary -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Professional Summary *</h3>
                <?php 
                wp_editor($resume_data['summary'] ?? '', 'professional_summary', array(
                    'media_buttons' => false,
                    'textarea_name' => 'professional_summary',
                    'textarea_rows' => 6,
                    'teeny' => true,
                    'quicktags' => false
                ));
                ?>
            </div>

            <!-- Skills -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Skills *</h3>
                <div class="skills-container">
                    <?php 
                    $skills = $resume_data['skills'] ?? array('');
                    foreach ($skills as $index => $skill): 
                    ?>
                        <div class="skill-item mb-2">
                            <div class="input-group">
                                <input type="text" class="form-control" name="skills[]" 
                                       value="<?php echo esc_attr($skill); ?>" required>
                                <button type="button" class="btn btn-outline-danger remove-skill">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="btn btn-outline-secondary add-skill">
                        <i class="bi bi-plus-circle me-2"></i>Add Skill
                    </button>
                </div>
            </div>

            <!-- Experience -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Work Experience</h3>
                <div class="experience-container">
                    <?php 
                    $experiences = $resume_data['experience'] ?? array(array());
                    foreach ($experiences as $index => $experience): 
                    ?>
                        <div class="experience-item card mb-3">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Job Title *</label>
                                        <input type="text" class="form-control" name="experience[<?php echo $index; ?>][title]" 
                                               value="<?php echo esc_attr($experience['title'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Company *</label>
                                        <input type="text" class="form-control" name="experience[<?php echo $index; ?>][company]" 
                                               value="<?php echo esc_attr($experience['company'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Start Date *</label>
                                        <input type="month" class="form-control" name="experience[<?php echo $index; ?>][start_date]" 
                                               value="<?php echo esc_attr($experience['start_date'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">End Date</label>
                                        <input type="month" class="form-control" name="experience[<?php echo $index; ?>][end_date]" 
                                               value="<?php echo esc_attr($experience['end_date'] ?? ''); ?>">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input current-job" type="checkbox" 
                                                   name="experience[<?php echo $index; ?>][current]" 
                                                   <?php echo isset($experience['current']) && $experience['current'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Current Job</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="experience[<?php echo $index; ?>][description]" 
                                                  rows="3" required><?php echo esc_textarea($experience['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <?php if ($index > 0): ?>
                                    <button type="button" class="btn btn-outline-danger remove-experience mt-3">
                                        <i class="bi bi-trash me-2"></i>Remove Experience
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="btn btn-outline-secondary add-experience">
                        <i class="bi bi-plus-circle me-2"></i>Add Experience
                    </button>
                </div>
            </div>

            <!-- Education -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Education</h3>
                <div class="education-container">
                    <?php 
                    $education = $resume_data['education'] ?? array(array());
                    foreach ($education as $index => $edu): 
                    ?>
                        <div class="education-item card mb-3">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Degree/Certificate *</label>
                                        <input type="text" class="form-control" name="education[<?php echo $index; ?>][degree]" 
                                               value="<?php echo esc_attr($edu['degree'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Institution *</label>
                                        <input type="text" class="form-control" name="education[<?php echo $index; ?>][institution]" 
                                               value="<?php echo esc_attr($edu['institution'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Start Date *</label>
                                        <input type="month" class="form-control" name="education[<?php echo $index; ?>][start_date]" 
                                               value="<?php echo esc_attr($edu['start_date'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">End Date</label>
                                        <input type="month" class="form-control" name="education[<?php echo $index; ?>][end_date]" 
                                               value="<?php echo esc_attr($edu['end_date'] ?? ''); ?>">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input current-education" type="checkbox" 
                                                   name="education[<?php echo $index; ?>][current]" 
                                                   <?php echo isset($edu['current']) && $edu['current'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Currently Studying</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="education[<?php echo $index; ?>][description]" 
                                                  rows="3"><?php echo esc_textarea($edu['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <?php if ($index > 0): ?>
                                    <button type="button" class="btn btn-outline-danger remove-education mt-3">
                                        <i class="bi bi-trash me-2"></i>Remove Education
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="btn btn-outline-secondary add-education">
                        <i class="bi bi-plus-circle me-2"></i>Add Education
                    </button>
                </div>
            </div>

            <!-- Certifications -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Certifications</h3>
                <div class="certifications-container">
                    <?php 
                    $certifications = $resume_data['certifications'] ?? array(array());
                    foreach ($certifications as $index => $cert): 
                    ?>
                        <div class="certification-item card mb-3">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Certification Name *</label>
                                        <input type="text" class="form-control" name="certifications[<?php echo $index; ?>][name]" 
                                               value="<?php echo esc_attr($cert['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Issuing Organization *</label>
                                        <input type="text" class="form-control" name="certifications[<?php echo $index; ?>][organization]" 
                                               value="<?php echo esc_attr($cert['organization'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Issue Date *</label>
                                        <input type="month" class="form-control" name="certifications[<?php echo $index; ?>][issue_date]" 
                                               value="<?php echo esc_attr($cert['issue_date'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Expiry Date</label>
                                        <input type="month" class="form-control" name="certifications[<?php echo $index; ?>][expiry_date]" 
                                               value="<?php echo esc_attr($cert['expiry_date'] ?? ''); ?>">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input no-expiry" type="checkbox" 
                                                   name="certifications[<?php echo $index; ?>][no_expiry]" 
                                                   <?php echo isset($cert['no_expiry']) && $cert['no_expiry'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">No Expiry</label>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($index > 0): ?>
                                    <button type="button" class="btn btn-outline-danger remove-certification mt-3">
                                        <i class="bi bi-trash me-2"></i>Remove Certification
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="btn btn-outline-secondary add-certification">
                        <i class="bi bi-plus-circle me-2"></i>Add Certification
                    </button>
                </div>
            </div>

            <!-- Languages -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Languages</h3>
                <div class="languages-container">
                    <?php 
                    $languages = $resume_data['languages'] ?? array(array());
                    foreach ($languages as $index => $lang): 
                    ?>
                        <div class="language-item card mb-3">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Language *</label>
                                        <input type="text" class="form-control" name="languages[<?php echo $index; ?>][name]" 
                                               value="<?php echo esc_attr($lang['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Proficiency Level *</label>
                                        <select class="form-select" name="languages[<?php echo $index; ?>][level]" required>
                                            <option value="">Select Level</option>
                                            <?php
                                            $levels = array(
                                                'native' => 'Native',
                                                'fluent' => 'Fluent',
                                                'advanced' => 'Advanced',
                                                'intermediate' => 'Intermediate',
                                                'basic' => 'Basic'
                                            );
                                            foreach ($levels as $value => $label):
                                                $selected = isset($lang['level']) && $lang['level'] === $value ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php if ($index > 0): ?>
                                    <button type="button" class="btn btn-outline-danger remove-language mt-3">
                                        <i class="bi bi-trash me-2"></i>Remove Language
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="btn btn-outline-secondary add-language">
                        <i class="bi bi-plus-circle me-2"></i>Add Language
                    </button>
                </div>
            </div>

            <!-- Industry -->
            <div class="col-12">
                <h3 class="h5 mb-3 mt-4">Industries of Interest *</h3>
                <select class="form-select" id="industry" name="industry[]" multiple required>
                    <?php 
                    $industries = get_terms(array(
                        'taxonomy' => 'industry',
                        'hide_empty' => false
                    ));
                    
                    $selected_industries = array();
                    if ($editing) {
                        $selected_industries = wp_get_object_terms($resume->ID, 'industry', array('fields' => 'ids'));
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
                    <i class="bi bi-check-circle me-2"></i><?php echo $editing ? 'Update Resume' : 'Create Resume'; ?>
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

        // Handle dynamic form fields
        function initializeDynamicFields() {
            // Skills
            $('.add-skill').click(function() {
                var skillItem = $('.skill-item:first').clone();
                skillItem.find('input').val('');
                $('.skills-container').find('.skill-item:last').after(skillItem);
            });

            $(document).on('click', '.remove-skill', function() {
                if ($('.skill-item').length > 1) {
                    $(this).closest('.skill-item').remove();
                }
            });

            // Experience
            $('.add-experience').click(function() {
                var experienceItem = $('.experience-item:first').clone();
                experienceItem.find('input, textarea').val('');
                experienceItem.find('.current-job').prop('checked', false);
                $('.experience-container').find('.experience-item:last').after(experienceItem);
            });

            $(document).on('click', '.remove-experience', function() {
                $(this).closest('.experience-item').remove();
            });

            // Education
            $('.add-education').click(function() {
                var educationItem = $('.education-item:first').clone();
                educationItem.find('input, textarea').val('');
                educationItem.find('.current-education').prop('checked', false);
                $('.education-container').find('.education-item:last').after(educationItem);
            });

            $(document).on('click', '.remove-education', function() {
                $(this).closest('.education-item').remove();
            });

            // Certifications
            $('.add-certification').click(function() {
                var certificationItem = $('.certification-item:first').clone();
                certificationItem.find('input').val('');
                certificationItem.find('.no-expiry').prop('checked', false);
                $('.certifications-container').find('.certification-item:last').after(certificationItem);
            });

            $(document).on('click', '.remove-certification', function() {
                $(this).closest('.certification-item').remove();
            });

            // Languages
            $('.add-language').click(function() {
                var languageItem = $('.language-item:first').clone();
                languageItem.find('input').val('');
                languageItem.find('select').val('');
                $('.languages-container').find('.language-item:last').after(languageItem);
            });

            $(document).on('click', '.remove-language', function() {
                $(this).closest('.language-item').remove();
            });

            // Handle current job checkbox
            $('.current-job').change(function() {
                var endDateInput = $(this).closest('.col-md-6').find('input[type="month"]');
                endDateInput.prop('disabled', this.checked);
                if (this.checked) {
                    endDateInput.val('');
                }
            });

            // Handle current education checkbox
            $('.current-education').change(function() {
                var endDateInput = $(this).closest('.col-md-6').find('input[type="month"]');
                endDateInput.prop('disabled', this.checked);
                if (this.checked) {
                    endDateInput.val('');
                }
            });

            // Handle no expiry checkbox
            $('.no-expiry').change(function() {
                var expiryDateInput = $(this).closest('.col-md-6').find('input[type="month"]');
                expiryDateInput.prop('disabled', this.checked);
                if (this.checked) {
                    expiryDateInput.val('');
                }
            });
        }

        initializeDynamicFields();

        // Form validation and submission
        $('#resume-form').submit(function(e) {
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
                        '<i class="bi bi-check-circle me-2"></i><?php echo $editing ? 'Update Resume' : 'Create Resume'; ?>'
                    );
                }
            });
        });
    });
    </script>
</div> 