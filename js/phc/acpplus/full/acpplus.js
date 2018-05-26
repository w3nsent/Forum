!function($, window, document, _undefined)
{
    XenForo.copyClipboard = function($btn)
    {
        var clipboard = null,
            errorId = '';

        this.$actionMessage = '';

        $btn.click(function(e)
        {
            errorId = $btn.data('clipboard-target');
            var errorTxt = $('#error_text_' + errorId.replace('#error_', '')).text(),
                copyTA = $('.error_text');

            copyTA.hide();

            var idString = '#error_text_' + errorId.replace('#error_', '');
            $(idString).show();

            var clipboard = new Clipboard('.copyClipboard');

            $btn.append('<div class="cliboardStatus"></div>');

            clipboard.on('success', function(e)
            {
                e.clearSelection();
                $('.cliboardStatus').addClass('success').text('Copied!');
            });
            clipboard.on('error', function(e)
            {
                var actionMessage = '';
                var actionKey = (e.action === 'cut' ? 'X' : 'C');
                if (/iPhone|iPad/i.test(navigator.userAgent))
                {
                    actionMessage = 'No support :(';
                }
                else if (/Mac/i.test(navigator.userAgent))
                {
                    actionMessage = 'Press ?-' + actionKey + ' to ' + e.action;
                }
                else
                {
                    actionMessage = 'Press Ctrl-' + actionKey + ' to ' + e.action;
                }

                $('.cliboardStatus').addClass('alert').text(actionMessage);
            });

            $('.cliboardStatus').delay(1000).fadeOut();
        });
    };

    XenForo.checkACPProtected = function($element) { this.__construct($element); };
    XenForo.checkACPProtected.prototype =
    {
        __construct: function($element)
        {
            XenForo.ajax(
                "index.php?/misc/checkacprotected", {},
                function(json)
                {
                    if(json.http_code != 401)
                        $('.checkacpprotected').show(300);
                },
                {cache: false}
            );
        }
    };

    XenForo.acppTemplateFavorit = function($favorit)
    {
        $favorit.click(function(e)
        {
            var $url = $favorit.data('toggleurl');
            var $tid = $('#acpptid').val();

            if(!$tid)
                return false;

            XenForo.ajax(
                $url,
                {
                    tid: $tid,
                    _xfConfirm: 1
                },
                function (ajaxData, textStatus)
                {
                    if (XenForo.hasResponseError(ajaxData))
                    {
                        return false;
                    }

                    $favorit.find('img').attr('src', ajaxData.favorit);
                }
            );
        });
    };

    if(XenForo.TemplateEditor)
    {
        var targetPrototype = XenForo.TemplateEditor.prototype;

        targetPrototype.updateEditor = function(templateTitle)
        {
            var editor = this.editors[templateTitle],
                data = this.templateData[templateTitle];

            if (editor.templateId != data.template_id)
            {
                if (this.isPrimaryTemplate(templateTitle))
                {
                    console.log('Primary template updated');

                    this.$templateId.val(data.template_id);

                    var $deleteButton = $('#TemplateDeleteButton');
                    if (data.deleteLink)
                    {

                        $deleteButton.data('href', data.deleteLink).show();
                        $('.acppTemplateFavorit').show();
                        $('#acpptid').val(data.template_id);
                    }
                    else
                    {
                        $deleteButton.hide();
                        $('.acppTemplateFavorit').hide();
                    }
                }

                editor.$tab.find('a')
                    .removeClass('master custom inherited')
                    .addClass(this.getInheritanceState(templateTitle));

                editor.$textarea.attr('name', 'templateArray[' + data.template_id + ']');

                editor.$title.attr('name', 'titleArray[' + data.template_id + ']');

                editor.$styleId.attr('name', 'styleidArray[' + data.template_id + ']');
                editor.$styleId.val(data.style_id);

                editor.templateId = data.template_id;
            }

            this.handleTemplateChange(templateTitle);
        }
    }

    XenForo.PhraseQuickForm = function($element) { this.__construct($element); };
    XenForo.PhraseQuickForm.prototype =
        {
            __construct: function($form)
            {
                var redirect = function(e)
                {
                    if (e.ajaxData.redirect)
                    {
                        XenForo.redirect(e.ajaxData.redirect);
                    }
                };

                $form.on('AutoValidationComplete', function(e)
                {
                    e.preventDefault();

                    if (!e.ajaxData.templateHtml || !e.ajaxData.IsPhrase)
                    {
                        redirect(e);
                        return;
                    }

                    var $overlay = $form.closest('.xenOverlay');
                    if (!$overlay.length || !$overlay.data('overlay'))
                    {
                        redirect(e);
                        return;
                    }

                    var overlay = $overlay.data('overlay'),
                        $trigger = overlay.getTrigger(),
                        $phraseContainer = $trigger.closest('.tr_edit_link');

                    if (!$phraseContainer.length)
                    {
                        redirect(e);
                        return;
                    }

                    e.preventDefault();

                    var $new = $($.parseHTML(e.ajaxData.templateHtml));

                    $phraseContainer.replaceWith($new);
                    $new.parent().xfActivate();

                    overlay.close();
                });
            }
        };

    XenForo.register('.acppTemplateFavorit', 'XenForo.acppTemplateFavorit');
    XenForo.register('.checkacpprotected', 'XenForo.checkACPProtected');
    XenForo.register('.copyClipboard', 'XenForo.copyClipboard');
    XenForo.register('form.PhraseQuickForm', 'XenForo.PhraseQuickForm');
}
(jQuery, this, document);


jQuery(document).ready(function()
{
    jQuery(document).on("click", '.quickAddonlist .SubmitOnChange', function()
    {
        var liId = jQuery(this).attr('name').replace('id[', '_').replace(']', '');
        jQuery('#' + liId).hide();
    });

    if($(window).width() <= 800)
        $('.templateToggleButton').hide();

    $('.templateToggleButton').toggle(function()
        {
            $('#content').addClass('contentEditorExpand');
            $('.templateToggleButton > span').addClass("active");

            $('textarea[name*=templateArray]').addClass('templateArrayExpand ');

            var $height = $(window).height() - 280;
            if($(window).width() < 800)
            {
                $height = $(window).height() - 350;
            }
            $("#textareaWrapper").insertAfter('#editorTabs').css("width", "100%").css("height", $height + 'px');
            $('.ctrlUnit.fullWidth.surplusLabel').hide();
        },
        function()
        {
            $('#content').removeClass('contentEditorExpand');
            $('textarea[name*=templateArray]').removeClass('templateArrayExpand');

            $('.ctrlUnit.fullWidth.surplusLabel').show();
            $("#textareaWrapper").prependTo('.ctrlUnit.fullWidth.surplusLabel > dd').css("width", "100%").css("height", 'auto');
        });

    $(window).resize(function()
    {
        var $height = $(window).height() - 280;
        if($(window).width() < 800)
        {
            $height = $(window).height() - 350;
        }

        if($('.templateToggleButton > span').is('.active'))
        {
            $("#textareaWrapper").insertAfter('#editorTabs').css("width", "100%").css("height", $height + 'px');
        }
    });

    $('.deleteConfirmForm').submit(function(event) {
        if(!confirm(XenForo.phrases.acpp_are_you_sure_that_you_want_to_run_the_query) )
            event.preventDefault();
    });


    /*
     * Permissions
     */
    // Expand ALL
    $('.acppExpandAll').click(function () {
        $('#piGroups > table > thead > tr').addClass('expand');
        $('.acppTbody').show();
    });

    //Collapse/Expand
    $('.acpppPrimaryHeader').click(function () {
        var elm = $(this),
            parentElm = elm.parent().parent();

        if($(elm).is('.expand'))
        {
            $('#piGroups > table > thead > tr').removeClass('expand');
            $('.acppTbody').hide();
            return false;
        }

        $('#piGroups > table > thead > tr').removeClass('expand');
        $('.acppTbody').hide();
        parentElm.children('tbody').show(100);

        elm.toggleClass('expand');
        $('html, body').animate({
            scrollTop: $('#' + parentElm.attr('id')).offset().top - 70
        }, 500);
    });

    /*
     *TMS
     */
    // Check Cookie State
    var cookieName = 'FilterList_' + encodeURIComponent($('.tms').attr('action')).replace(/[\.\+\/]|(%([0-9a-f]{2}|u\d+))/gi, '');
    var cookie = $.getCookie(cookieName);

    if(cookie)
    {
        $('.tms .FilterList > li').addClass('expand');
        $('.subs').show();
    }
    else
    {
        $('.subs').hide();
    }

    // Expand ALL
    $('.acppExpandAll').click(function () {
        $('.tms .FilterList > li').addClass('expand');
        $('.subs').show();
    });

    // Filter Options
    $('.tms #ctrl_filter').keypress(function( event ) {
        $('.tms .FilterList > li').addClass('expand');
        $('.subs').show();
    });

    $('.tms [name=clearfilter]').click(function( event ) {
        $('.subs').hide();
        $('.tms .FilterList > li').removeClass('expand');
    });

    $('.tms ol li h3').click(function ()
    {
        var $parent = $(this).parent();

        if($parent.is('.expand'))
        {
            $parent.removeClass('expand');
            $('.subs').hide();
            $('.tms .FilterList > li').removeClass('expand');
            return false;
        }

        $('.subs').hide();
        $('.tms .FilterList > li').removeClass('expand');

        $parent.find('ol').show(100);
        $parent.toggleClass('expand');

        $('html, body').animate({
            scrollTop: $('#' + $parent.attr('id')).offset().top - 80
        }, 500);
    });
});