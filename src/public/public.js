(function ($) {
    window.wpJobBoardApp = {
        forms: {},
        general: window.wp_job_board_general,
        formData: {},
        init() {
            let body = $(document.body);
            this.forms = body.find('.wpjb_form');
            this.forms.each(function () {
                var form = $(this);
                wpJobBoardApp.initForm(form);
                body.trigger('wpjbFormProcessFormElements', [form]);
            });
            this.initDatePiker();

            $('.wpjb_form input').on('keypress', function (e) {
                return e.which !== 13;
            });

            this.showForm();
        },
        initForm(form) {
            let that = this;
            form.on('submit', function (e) {
                e.preventDefault();
                that.submitForm(form);
            });
            if (form.hasClass('wpjb_has_steps')) {
                this.initSteps(form);
            }
            jQuery(document.body).trigger('wpjbFormInitialized', [form]);
            jQuery(document.body).trigger('wpjbFormInitialized_' + form.data('wpjb_form_id'), [form]);
            form.addClass('wpjb_form_initialized');
        },
        submitForm(form) {
            var that = this;
            form.find('button.wpjb_submit_button').attr('disabled', true);
            form.addClass('wpjb_submitting_form');
            form.parent().find('.wpjb_form_notices').hide();
            let formId = form.data('wpjb_form_id');
            form.trigger('wpjb_form_submitting', formId);
            $.post(this.general.ajax_url, {
                action: 'wpjb_submit_form',
                form_id: formId,
                form_data: $(form).serialize()
            })
                .then(response => {
                    if (!response || !response.data || !response.data.confirmation) {
                        let $errorDiv = form.parent().find('.wpjb_form_errors');
                        $errorDiv.html('<p class="wpjb_form_error_heading">Something is wrong when submitting the form</p>').show();
                        $errorDiv.append('<div class="wpjb_error_items">Server Response: ');
                        $errorDiv.append('<p>' + response + '</p>');
                        $errorDiv.append('</div>');
                        form.parent().addClass('wpjb_form_has_errors');
                        form.trigger('wpjb_form_fail_submission', response);
                        form.removeClass('wpjb_submitting_form');

                        form.removeClass('wpjb_submitting_form');
                        form.find('button.wpjb_submit_button').removeAttr('disabled');
                        return;
                    }

                    let confirmation = response.data.confirmation;
                    form.parent().addClass('wpjb_form_submitted');
                    form.trigger('wpjb_form_submitted', response.data);
                    if (confirmation.redirectTo == 'samePage') {
                        form.removeClass('wpf_submitting_form');
                        form.find('button.wpf_submit_button').removeAttr('disabled');
                        form.parent().removeClass('wpjb_form_has_errors');

                        form.parent().find('.wpjb_form_success').html(confirmation.messageToShow).show();
                        if (confirmation.samePageFormBehavior == 'hide_form') {
                            form.hide();
                            $([document.documentElement, document.body]).animate({
                                scrollTop: form.parent().find('.wpjb_form_success').offset().top - 100
                            }, 200);
                        }
                        $('#wpjb_form_id_' + formId)[0].reset();
                        if (form.hasClass('wpjb_has_steps')) {
                            that.switchToStep(0, form);
                        }
                        form.trigger('stripe_clear');
                    } else if (confirmation.redirectTo == 'customUrl') {
                        if (confirmation.messageToShow) {
                            form.parent().find('.wpjb_form_success').html(confirmation.messageToShow).show();
                        }
                        window.location.href = confirmation.customUrl;
                        return false;
                    }
                })
                .fail(error => {
                    let $errorDiv = form.parent().find('.wpjb_form_errors');
                    $errorDiv.html('<p class="wpjb_form_error_heading">' + error.responseJSON.data.message + '</p>').show();
                    $errorDiv.append('<ul class="wpjb_error_items">');
                    $.each(error.responseJSON.data.errors, (errorId, errorText) => {
                        $errorDiv.append('<li class="error_item_' + errorId + '">' + errorText + '</li>');
                    });
                    $errorDiv.append('</ul>');
                    form.parent().addClass('wpjb_form_has_errors');
                    form.trigger('wpjb_form_fail_submission', error.responseJSON.data);
                    form.removeClass('wpjb_submitting_form');

                    form.removeClass('wpjb_submitting_form');
                    form.find('button.wpjb_submit_button').removeAttr('disabled');
                })
                .always(() => {

                });
        },
        initDatePiker() {
            let dateFields = $('.wpjb_form input.wpjb_date_field');
            if (dateFields.length) {
                flatpickr.localize(window.wp_job_board_general.date_i18n);
                dateFields.each(function (index, dateField) {
                    let config = $(this).data('date_config');
                    flatpickr(dateField, config);
                });
            }
        },
        initSteps(form) {
            form.find('.wpjb_step_start').hide();
            form.find('#wpjb_step_0').show();

            let that = this;
            form.find('.wpjb_step_next').on('click', function (e) {
                e.preventDefault();
                let nextStepNumber = $(this).data('step_number');
                let currentStepNumber = parseInt(nextStepNumber) - 1;
                let $scope = form.find('#wpjb_step_' + currentStepNumber);
                if (that.validateIntoDom($scope, form)) {
                    that.switchToStep(nextStepNumber, form);
                }
            });

            form.find('.wpjb_step_back').on('click', function (e) {
                e.preventDefault();
                let prevStepNumber = $(this).data('target_step_number');
                that.switchToStep(prevStepNumber, form);
            });
        },
        switchToStep(stepNumber, form) {
            if (!stepNumber) {
                stepNumber = 0;
            }
            form.find('.wpjb_step_start').hide();
            form.find('#wpjb_step_' + stepNumber).show();
            $([document.documentElement, document.body]).animate({
                scrollTop: form.find('#wpjb_step_' + stepNumber).offset().top - 100
            }, 200);
        },
        validateIntoDom($scope, form) {
            let $inputs = $scope.find('input[data-required="yes"],select[data-required="yes"]');
            let errors = {}
            let hasErrors = false;
            $.each($inputs, (index, input) => {
                let $input = $(input);
                if(!$input.val()) {
                    hasErrors = true;
                    let label = $input.closest('.wpjb_form_group').find('.wpjb_input_label label').text();
                    if(!label) {
                        label = $input.attr('placeholder');
                    }
                    errors[$input.attr('name')] = label + ' is required';
                }
            });

            let $radioItems = $scope.find('.form-check-input[required="1"]');
            $.each($radioItems, (index, input) => {
                let $input = $(input);
                if(! form.find('input[name="'+$input.attr('name')+'"]:checked').length ) {
                    hasErrors = true;
                    let label = $input.closest('.wpjb_form_group').find('.wpjb_input_label label').text();
                    errors[$input.attr('name')] = label + ' is required';
                }
            });

            let checkboxRequiredWrappers = $scope.find('.wpjb_item_checkbox[data-checkbox_required="yes"]');
            $.each(checkboxRequiredWrappers, (index, checkWrapper) => {
                 $checkWrapper = $(checkWrapper);
                 if(!$checkWrapper.find('input:checked').length) {
                     hasErrors = true;
                     errors['checkbox'] = $checkWrapper.find('.wpjb_input_label label').text() + ' is required';
                 }
            });

            let filesRequired = $scope.find('.wpjb_file_upload_element[data-file_required="yes"]');
            $.each(filesRequired, (index, fileInput) => {
                let $fileInput = $(fileInput);
                let associateKey = $fileInput.attr('data-associate_key');
                if(!form.find('input[name="'+associateKey+'[]"]').val()) {
                    hasErrors = true;
                    let label = $fileInput.closest('.wpjb_form_group').find('.wpjb_input_label label').text();
                    if(!label) {
                        label = 'File upload';
                    }
                    errors[associateKey] = label+' is required';
                }
            });

            let $errorDiv = form.parent().find('.wpjb_form_errors');
            $errorDiv.html('').hide();

            if(hasErrors) {
                $errorDiv.append('<ul class="wpjb_error_items">');
                $.each(errors, (errorId, errorText) => {
                    $errorDiv.append('<li class="error_item_' + errorId + '">' + errorText + '</li>');
                });
                $errorDiv.append('</ul>');
                $errorDiv.show();
            } else {
                return true;
            }
        },
        showForm() {
            $('.wpjb_job_apply_btn').on('click', function (e) {
                e.preventDefault();
                $(this).closest('.wpjb_job_application_wrapper').find('.wpjb_job_form_wrapper').toggle();
            });
        }
    };

    $(document).ready(function ($) {
        window.wpJobBoardApp.init();
    });

}(jQuery));