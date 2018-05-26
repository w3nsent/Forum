! function($, window, document, _undefined) {
    $(function() {
        $('.continentList input').change(function() {
            var optgroup = $(this).parents('.ctrlUnit').find('optgroup[label="' + $(this).val() + '"]');
            if ($(this).is(':checked')) {
                optgroup.find('option').prop('selected', true);
            } else {
                optgroup.find('option').prop('selected', false);
            }
        });

        function continentListSelection() {
            $('select[name^="geoip_criteria"] optgroup').each(function() {
                var id = $(this).parent().attr('id');
                var input = $('.continentList[data-id="' + id + '"] input[value="' + $(this).attr('label') + '"]');
                if (($(this).find('option').length - $(this).find('option:selected').length) == 0) {
                    input.prop('checked', true);
                } else {
                    input.prop('checked', false);
                }
            });
        }
        continentListSelection();
        $('select[name^="geoip_criteria"] optgroup').click(function() {
            continentListSelection();
        });
    });
}(jQuery, this, document);
