jQuery(document).ready(function ($) {

    // Tooltips
    $('.edd-ecourse-tip').tooltip({
        content: function () {
            return $(this).prop('title');
        },
        tooltipClass: 'edd-ecourse-tooltip',
        position: {
            my: 'center top',
            at: 'center bottom+10',
            collision: 'flipfit'
        },
        hide: false,
        show: false
    });

    /**
     * E-Courses
     */
    var EDD_ECourse = {

        /**
         * Initialize all the things.
         */
        init: function () {
            this.remove();
        },

        /**
         * Remove E-Course
         */
        remove: function () {

            $(document.body).on('click', '.edd-course-action-delete', function (e) {

                e.preventDefault();

                if (!confirm(edd_ecourse_vars.l10n.confirm_delete_course)) {
                    return false;
                }

                var actionsWrap = $(this).parents('.edd-course-actions');

                // Add spinner.
                actionsWrap.append('<span class="spinner is-active"></span>');

                // Deactivate all buttons.
                actionsWrap.find('.button').each(function () {
                    $(this).addClass('disabled').attr('disabled', true);
                });

                var course_id = $(this).parents('.edd-ecourse').data('course-id');

                var data = {
                    action: 'edd_ecourse_delete_course',
                    course_id: course_id,
                    nonce: $(this).data('nonce')
                };

            });

        }

    };

    EDD_ECourse.init();

});