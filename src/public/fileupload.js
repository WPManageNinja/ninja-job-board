const addQueryString = function (url, queryString) {
    var isQuestionMarkPresent = url && url.indexOf('?') !== -1,
        separator = '';

    if (queryString) {
        separator = isQuestionMarkPresent ? '&' : '?';
        url += separator + queryString;
    }

    return url;
};
window.Dropzone.autoDiscover = false;

jQuery(document).ready(function ($) {
    const wpfFileUploader = {
        initUploaders() {
            let $forms = $('.wpjb_form_has_file_upload');
            $.each($forms, (index, fileForm) => {
                this.initUploader($(fileForm));
            });

            $('.upload_error_message').on('click', function () {
                $(this).html('').removeClass('wpjb_has_error');
            });
        },

        initUploader($theForm) {
            var that = this;
            $theForm.find('input.wpjb_file_upload_element').each(function (key, el) {
                var element = $(el);

                let elementName = element.data('target_name');
                let formData = {
                    form_id: $theForm.data('wpjb_form_id'),
                    element_name: elementName,
                    action: 'wpjb_file_upload_process',
                    wpjb_nonce: window.wp_job_board_general.nonce
                }
                let uploadUrl = window.wp_job_board_general.ajax_url;
                uploadUrl = addQueryString(uploadUrl, $.param(formData));
                let $dropZoneParent = element.parent();
                let i18n = window.wp_job_board_general.i18n || {};
                let dropZoneInstance = $dropZoneParent.dropzone({
                    url: uploadUrl,
                    maxFiles: element.data('max_files'),
                    maxFilesize: element.data('max_file_size'),
                    addRemoveLinks: true,
                    filesizeBase: 1024,
                    dictDefaultMessage: element.data('btn_txt'),
                    acceptedFiles: element.attr('accept'),
                    dictFileTooBig: i18n.file_too_big || 'The file is too big ({{filesize}} MB). Maximum file size: {{maxFilesize}} MB.',
                    dictInvalidFileType: i18n.invalid_file_type || 'You cannot upload files of this type.',
                    dictResponseError: i18n.response_error || 'The server responded with code {{statusCode}}.',
                    dictCancelUpload: i18n.cancel_upload || 'Cancel upload',
                    dictUploadCanceled: i18n.upload_canceled || 'Upload canceled.',
                    dictCancelUploadConfirmation: i18n.cancel_confirm || 'Are you sure you want to cancel this upload?',
                    dictRemoveFile: i18n.remove_file || 'Remove file',
                    dictMaxFilesExceeded: i18n.max_files || 'You cannot upload any more files.',
                    success(file, response) {
                        $('<input>', {
                            type: 'hidden',
                            'data-file_id': file.upload.uuid,
                            name: element.data('associate_key')+'[]',
                            value: response.file
                        }).appendTo(file.previewElement);
                    },
                    error(file, message, serverResponse){
                        this.removeFile(file);
                        that.showErrorMessage(message, serverResponse, $dropZoneParent);
                    }
                });

                $theForm.trigger('file_upload_initiated', [dropZoneInstance, element]);
            });
        },

        showErrorMessage(message, serverResponse, $dropZoneParent)
        {
            let $errorWrapper = $dropZoneParent.parent().find('.upload_error_message');
            if(serverResponse && serverResponse.response) {
                let response = $.parseJSON(serverResponse.response);
                if(response.data && response.data.message) {
                    message = response.data.message;
                } else {
                    message = serverResponse.responseText;
                }
            }
            $errorWrapper.html(message).addClass('wpjb_has_error');
        }

    }
    wpfFileUploader.initUploaders();
});



