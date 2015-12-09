(function ($) {
    var checkMembership = function () {
        var friendsFieldSet = $('#edit-friends');
        var membership = friendsFieldSet.find('#edit-membership');
        var inputElems = friendsFieldSet.find(':input').not(membership);

        if (membership.is(':checked')) {
            inputElems.removeAttr('disabled');
        }
        else {
            inputElems.attr('disabled', 'disabled');
        }
    };

    $(document).ready(function () {
        checkMembership();
        $('#edit-friends').find('#edit-membership').change(function () {
            checkMembership();
        });
    });
})(jQuery);