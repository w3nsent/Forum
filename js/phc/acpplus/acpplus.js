!function(a,b,c,d){if(XenForo.copyClipboard=function(b){var d="";this.$actionMessage="",b.click(function(c){d=b.data("clipboard-target");var f=(a("#error_text_"+d.replace("#error_","")).text(),a(".error_text"));f.hide();var g="#error_text_"+d.replace("#error_","");a(g).show();var h=new Clipboard(".copyClipboard");b.append('<div class="cliboardStatus"></div>'),h.on("success",function(b){b.clearSelection(),a(".cliboardStatus").addClass("success").text("Copied!")}),h.on("error",function(b){var c="",d="cut"===b.action?"X":"C";c=/iPhone|iPad/i.test(navigator.userAgent)?"No support :(":/Mac/i.test(navigator.userAgent)?"Press ?-"+d+" to "+b.action:"Press Ctrl-"+d+" to "+b.action,a(".cliboardStatus").addClass("alert").text(c)}),a(".cliboardStatus").delay(1e3).fadeOut()})},XenForo.checkACPProtected=function(a){this.__construct(a)},XenForo.checkACPProtected.prototype={__construct:function(b){XenForo.ajax("index.php?/misc/checkacprotected",{},function(b){401!=b.http_code&&a(".checkacpprotected").show(300)},{cache:!1})}},XenForo.acppTemplateFavorit=function(b){b.click(function(c){var d=b.data("toggleurl"),e=a("#acpptid").val();return!!e&&void XenForo.ajax(d,{tid:e,_xfConfirm:1},function(a,c){return!XenForo.hasResponseError(a)&&void b.find("img").attr("src",a.favorit)})})},XenForo.TemplateEditor){var e=XenForo.TemplateEditor.prototype;e.updateEditor=function(b){var c=this.editors[b],d=this.templateData[b];if(c.templateId!=d.template_id){if(this.isPrimaryTemplate(b)){console.log("Primary template updated"),this.$templateId.val(d.template_id);var e=a("#TemplateDeleteButton");d.deleteLink?(e.data("href",d.deleteLink).show(),a(".acppTemplateFavorit").show(),a("#acpptid").val(d.template_id)):(e.hide(),a(".acppTemplateFavorit").hide())}c.$tab.find("a").removeClass("master custom inherited").addClass(this.getInheritanceState(b)),c.$textarea.attr("name","templateArray["+d.template_id+"]"),c.$title.attr("name","titleArray["+d.template_id+"]"),c.$styleId.attr("name","styleidArray["+d.template_id+"]"),c.$styleId.val(d.style_id),c.templateId=d.template_id}this.handleTemplateChange(b)}}XenForo.PhraseQuickForm=function(a){this.__construct(a)},XenForo.PhraseQuickForm.prototype={__construct:function(b){var c=function(a){a.ajaxData.redirect&&XenForo.redirect(a.ajaxData.redirect)};b.on("AutoValidationComplete",function(d){if(d.preventDefault(),!d.ajaxData.templateHtml||!d.ajaxData.IsPhrase)return void c(d);var e=b.closest(".xenOverlay");if(!e.length||!e.data("overlay"))return void c(d);var f=e.data("overlay"),g=f.getTrigger(),h=g.closest(".tr_edit_link");if(!h.length)return void c(d);d.preventDefault();var i=a(a.parseHTML(d.ajaxData.templateHtml));h.replaceWith(i),i.parent().xfActivate(),f.close()})}},XenForo.register(".acppTemplateFavorit","XenForo.acppTemplateFavorit"),XenForo.register(".checkacpprotected","XenForo.checkACPProtected"),XenForo.register(".copyClipboard","XenForo.copyClipboard"),XenForo.register("form.PhraseQuickForm","XenForo.PhraseQuickForm")}(jQuery,this,document),jQuery(document).ready(function(){jQuery(document).on("click",".quickAddonlist .SubmitOnChange",function(){var a=jQuery(this).attr("name").replace("id[","_").replace("]","");jQuery("#"+a).hide()}),$(window).width()<=800&&$(".templateToggleButton").hide(),$(".templateToggleButton").toggle(function(){$("#content").addClass("contentEditorExpand"),$(".templateToggleButton > span").addClass("active"),$("textarea[name*=templateArray]").addClass("templateArrayExpand ");var a=$(window).height()-280;$(window).width()<800&&(a=$(window).height()-350),$("#textareaWrapper").insertAfter("#editorTabs").css("width","100%").css("height",a+"px"),$(".ctrlUnit.fullWidth.surplusLabel").hide()},function(){$("#content").removeClass("contentEditorExpand"),$("textarea[name*=templateArray]").removeClass("templateArrayExpand"),$(".ctrlUnit.fullWidth.surplusLabel").show(),$("#textareaWrapper").prependTo(".ctrlUnit.fullWidth.surplusLabel > dd").css("width","100%").css("height","auto")}),$(window).resize(function(){var a=$(window).height()-280;$(window).width()<800&&(a=$(window).height()-350),$(".templateToggleButton > span").is(".active")&&$("#textareaWrapper").insertAfter("#editorTabs").css("width","100%").css("height",a+"px")}),$(".deleteConfirmForm").submit(function(a){confirm(XenForo.phrases.acpp_are_you_sure_that_you_want_to_run_the_query)||a.preventDefault()}),$(".acppExpandAll").click(function(){$("#piGroups > table > thead > tr").addClass("expand"),$(".acppTbody").show()}),$(".acpppPrimaryHeader").click(function(){var a=$(this),b=a.parent().parent();return $(a).is(".expand")?($("#piGroups > table > thead > tr").removeClass("expand"),$(".acppTbody").hide(),!1):($("#piGroups > table > thead > tr").removeClass("expand"),$(".acppTbody").hide(),b.children("tbody").show(100),a.toggleClass("expand"),void $("html, body").animate({scrollTop:$("#"+b.attr("id")).offset().top-70},500))});var a="FilterList_"+encodeURIComponent($(".tms").attr("action")).replace(/[\.\+\/]|(%([0-9a-f]{2}|u\d+))/gi,""),b=$.getCookie(a);b?($(".tms .FilterList > li").addClass("expand"),$(".subs").show()):$(".subs").hide(),$(".acppExpandAll").click(function(){$(".tms .FilterList > li").addClass("expand"),$(".subs").show()}),$(".tms #ctrl_filter").keypress(function(a){$(".tms .FilterList > li").addClass("expand"),$(".subs").show()}),$(".tms [name=clearfilter]").click(function(a){$(".subs").hide(),$(".tms .FilterList > li").removeClass("expand")}),$(".tms ol li h3").click(function(){var a=$(this).parent();return a.is(".expand")?(a.removeClass("expand"),$(".subs").hide(),$(".tms .FilterList > li").removeClass("expand"),!1):($(".subs").hide(),$(".tms .FilterList > li").removeClass("expand"),a.find("ol").show(100),a.toggleClass("expand"),void $("html, body").animate({scrollTop:$("#"+a.attr("id")).offset().top-80},500))})});