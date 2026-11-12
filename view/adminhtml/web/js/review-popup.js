/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

/**
 * Review popup: the Magefan analyst peeks from the bottom edge and pops out only on hover.
 * Click on him or "Leave a review" opens the review form, "Maybe later" (or closing the form) postpones the module.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/alert'
], function ($, alert) {
    /**
     * @param {String} text
     * @return {String}
     */
    function escape(text) {
        return $('<div>').text(text || '').html();
    }

    /**
     * @param {Object} config
     * @param {String} config.requestUrl
     * @param {String} config.formKey
     * @param {String} config.module
     * @param {Number} config.showDelay
     * @param {Object} config.texts
     * @param {HTMLElement} element
     */
    return function (config, element) {
        var $root = $(element),
            $analyst = $root.find('.mf-ra'),
            texts = config.texts || {};

        /**
         * @param {String} title
         * @param {String} content
         */
        function message(title, content) {
            alert({
                title: escape(title),
                content: escape(content)
            });
        }

        /**
         * @param {Object} data
         * @param {Function} [onSuccess]
         * @param {Function} [onFail]
         */
        function sendRequest(data, onSuccess, onFail) {
            data.module = config.module;
            data.form_key = config.formKey;

            $.ajax({
                url: config.requestUrl,
                type: 'POST',
                data: data,
                dataType: 'json',
                showLoader: true
            }).done(function (response) {
                if (response && response.success) {
                    if (onSuccess) {
                        onSuccess(response);
                    }

                    return;
                }

                if (onSuccess) {
                    message(texts.error, response && response.message || texts.errorMessage);
                }

                if (onFail) {
                    onFail();
                }
            }).fail(function () {
                if (onSuccess) {
                    message(texts.error, texts.errorMessage);
                }

                if (onFail) {
                    onFail();
                }
            });
        }

        function remindLater() {
            sendRequest({action: 'cancel'});
        }

        /**
         * @param {String} token
         */
        function showReviewForm(token) {
            var submitted = false;

            alert({
                title: escape(texts.formTitle),
                // own class, as the default "confirm" modal is limited to 50rem and the form looks narrow
                modalClass: 'confirm mf-review-modal',
                content: $root.find('.mf-review-form-template').html(),
                actions: {
                    always: function () {
                        // Closed without submitting counts as "not now"
                        if (!submitted) {
                            submitted = true;
                            remindLater();
                        }
                    }
                },
                buttons: [{
                    text: escape(texts.submit),
                    class: 'action primary',
                    click: function () {
                        var modal = this,
                            $form = modal.element.find('.mf-review-form'),
                            data = {};

                        if (!$form[0].checkValidity()) {
                            $form[0].reportValidity();

                            return;
                        }

                        $.each($form.serializeArray(), function (i, item) {
                            data[item.name] = item.value;
                        });
                        data.action = 'submit';
                        data.token = token;

                        sendRequest(data, function () {
                            submitted = true;
                            modal.closeModal(false);
                            message(texts.thankYou, texts.submitted);
                        });
                    }
                }]
            });
        }

        /**
         * A technical error (e.g. Magefan is not available) does not count as "not now":
         * the analyst is just hidden until the next page load
         */
        function requestToken() {
            sendRequest({action: 'token'}, function (response) {
                if (response.reviewed) {
                    message(texts.thankYou, texts.alreadyReviewed);

                    return;
                }

                showReviewForm(response.token);
            });
        }

        function hideAnalyst() {
            $analyst.removeClass('is-in');
            setTimeout(function () {
                $analyst.remove();
            }, 600);
        }

        $analyst.on('click', '.mf-ra-open', function () {
            hideAnalyst();
            requestToken();
        });

        $analyst.on('click', '.mf-ra-later', function () {
            hideAnalyst();
            remindLater();
        });

        setTimeout(function () {
            $analyst.prop('hidden', false);
            // next frame, so the slide-in transition runs from the hidden position
            setTimeout(function () {
                $analyst.addClass('is-in');
            }, 50);
        }, config.showDelay || 3000);
    };
});
