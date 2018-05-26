! function($, window, document, _undefined) {
    $(function() {
        $('#transactionList input[name="submit"]').hide();
        $('#paymentOptions').show();
        var submitButtons = $('#paymentOptions').find('input[name="submit"]');
        submitButtons.prop('disabled', true).addClass('disabled');

        function calculateTotalAndPopulateForm() {
            var id = $('input[name="id[]"]');
            var idList = [];
            var total = 0;
            id.each(function() {
                if ($(this).is(':checked')) {
                    total += Number($(this).data('cost'));
                    idList.push($(this).attr('value'));
                }
            });
            var currency = id.first().data('currency');
            var decimal = 2;
            switch (currency) {
                case 'TWD':
                case 'HUF':
                case 'JPY':
                case 'IRR':
                    decimal = 0;
                    break;
            }
            if (total) {
                $('#total span').html(total.toFixed(decimal) + ' ' + id.first().data('currency-alt'));
                $('.PaymentAmount').each(function() {
                    $(this).val(total.toFixed(decimal));
                });
                $('.PaymentCurrency').each(function() {
                    $(this).val(currency);
                });
                $('.PaymentInvoice').each(function() {
                    $(this).val(idList.join(','));
                });
            }
        }
        var id = $('input[name="id[]"]');
        id.click(function() {
            if (id.is(':checked')) {
                submitButtons.prop('disabled', false).removeClass('disabled');
                $('#total').fadeIn();
            } else {
                submitButtons.prop('disabled', true).addClass('disabled');
                $('#total').hide();
            }
            calculateTotalAndPopulateForm();
        });
    });
    var ajaxPath = 'index.php?ajax/';
    XenForo.RobokassaSig = function($item) {
        var currentForm = $('form[data-option="ROBOKASSA"]');
        $('input[name="id[]"]').click(function() {
            setTimeout(function() {
                XenForo.ajax(ajaxPath + 'GetRobokassaSigValue', {
                    OutSum: currentForm.find('.PaymentAmount').val(),
                    IncCurrLabel: currentForm.find('.PaymentCurrency').val(),
                    Shp_item: currentForm.find('.PaymentInvoice').val()
                }, function(response) {
                    if (response.sigValue != undefined) {
                        $item.val(response.sigValue);
                        currentForm.find('.PaymentCurrency').val(response.IncCurrLabel);
                    }
                }, {
                    error: false
                });
            }, 500);
        });
    }
    XenForo.register('#RobokassaSig', 'XenForo.RobokassaSig');
}(jQuery, this, document);
