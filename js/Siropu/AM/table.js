! function($, window, document, _undefined) {
    $(function() {
        function makeTablesResponsive() {
            if ($('.SamResponsive').length) {
                if (window.matchMedia('only screen and (max-width: 767px)').matches) {
                    if (!$('#SamCSS').length) {
                        $('.SamResponsive:first').before('<div id="SamCSS"><style>.SamResponsive table, .SamResponsive thead, .SamResponsive tbody, .SamResponsive th, .SamResponsive td, .SamResponsive tr {display: block;} .SamResponsive tr th {position: absolute; top: -9999px; left: -9999px;} .SamResponsive tr {border: 1px solid #d7edfc; } .SamResponsive td {position: relative; padding-left: 45% !important;} .SamResponsive td:before {content: attr(data-content); position: absolute; top: 6px; left: 6px; width: 40%; padding-right: 10px; white-space: nowrap; text-align: right; font-weight: bold;}</style></div>');
                    }
                    $('.SamResponsive').each(function() {
                        var $this = $(this);
                        $this.find('th').each(function() {
                            var text = $(this).text();
                            var item = $this.find('td:nth-of-type(' + Number($(this).index() + 1) + ')');
                            if (!item.data('content')) {
                                item.attr('data-content', (text ? text + ':' : ''));
                                if (item.css('text-align')) {
                                    item.css('text-align', '');
                                }
                            }
                        });
                    });
                } else if ($('#SamCSS').length) {
                    $('#SamCSS').remove();
                }
            }
        }
        makeTablesResponsive();
        $(window).resize(function() {
            makeTablesResponsive();
        });
    });
}
(jQuery, this, document);
