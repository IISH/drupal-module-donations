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

    var toggleDonations = function (elem) {
        elem.next('.iish-donations-toggle').find('.iish-donations-toggle-div').slideToggle();
    };

    var setPayed = function (elem) {
        var orderId = elem.data('order-id');

        elem.attr('disabled', 'disabled');
        $.ajax({
            url: Drupal.settings.basePath + 'donations/overview/set-payed/' + orderId,
            success: function (data) {
                $('.iish-donations-confirm-payed[data-order-id=' + orderId + ']').each(function () {
                    $(this).closest('.iish-donations-payment-status').html(data);
                });
            },
            complete: function () {
                elem.removeAttr('disabled');
            }
        });
    };

    $(document).ready(function () {
        checkMembership();
        $('#edit-friends').find('#edit-membership').change(function () {
            checkMembership();
        });

        $('.iish-donations-table tr.iish-donations-click').click(function () {
            toggleDonations($(this));
        });

        $('button.iish-donations-confirm-payed').click(function (e) {
            e.stopPropagation();
            setPayed($(this));
        });
    });
})(jQuery);