$(document).ready(function(){hiddenSelectorTrigger()});
function hiddenSelectorTrigger(){var b;$(".hiddenSelector").change(function(){b=$(this).closest(".hideParent");$(this).val()?(b.children(".hiddenContainer").addClass("active").hide(),b.children(".hiddenContainer.hiddenContainer_"+$(this).val()).show()):b.children(".hiddenContainer").hide()});$(".hiddenSelector").each(function(){b=$(this).closest(".hideParent");$(this).val()?(b.children(".hiddenContainer").addClass("active").hide(),b.children(".hiddenContainer.hiddenContainer_"+$(this).val()).removeClass("active").show()):
b.children(".hiddenContainer").addClass("active").hide()})}
!function(b,f,g,h){XenForo.BRMSTabListener=function(a){this.__construct(a)};XenForo.BRMSTabListener.prototype={__construct:function(a){this.$element=a;this.$tabKind=a.find("select.tabKind").change(b.context(this,"rebuilHeader"));var c=a.find(".hiddenContainer_"+this.$tabKind.val()+" select.tabType");a.find("select.tabType").change(b.context(this,"rebuilHeader"));this.$tabHeader=a.find(".textHeading .tabHeader");this.$tabKind.find("option:selected").text()&&c.find("option:selected").text()&&this.$tabHeader.text(this.$tabKind.find("option:selected").text()+
" - "+c.find("option:selected").text())},rebuilHeader:function(){var a=this.$tabKind,b=this.$element.find(".hiddenContainer_"+a.val()+" select.tabType");a.find("option:selected").text()&&b.find("option:selected").text()&&this.$tabHeader.text(a.find("option:selected").text()+" - "+b.find("option:selected").text())}};XenForo.BRMSNewTabListener=function(a){this.__construct(a)};XenForo.BRMSNewTabListener.prototype={__construct:function(a){a.find("select.tabType").one("change",b.context(this,"createChoice"));
var c=a.find("select.tabKind"),d=a.find("select.tabType"),e=a.find(".textHeading .tabHeader");c.val()&&d.val()&&e.text(c.val()+" - "+d.val());this.$element=a;this.$base||(this.$base=a.clone())},createChoice:function(){var a=this.$base.clone(),c=this.$element.parent().children().length;a.find('input:not([type="button"], [type="checkbox"], [type="submit"])').val("");a.find(".spinBoxButton").remove();a.find("*[name]").each(function(){var a=b(this);a.attr("name",a.attr("name").replace(/\[(\d+)\]/,"["+
c+"]"))});var d=parseInt(b(".lastOrderHolder").val())+1;a.find(".orderValue").val(d);d=b(".lastOrderHolder").val(d);a.find("label").each(function(){b(this).removeAttr("for")});a.find("*[id]").each(function(){var a=b(this);a.removeAttr("id");XenForo.uniqueId(a);XenForo.formCtrl&&XenForo.formCtrl.clean(a)});a.xfInsert("insertAfter",this.$element);hiddenSelectorTrigger();this.__construct(a)}};XenForo.BRMSCollapse=function(a){var c=b(".listTabs");a.click(function(b){b.preventDefault();a.hasClass("collapsed")?
(a.removeClass("collapsed"),c.find(".tabContent").xfSlideDown(500)):(a.addClass("collapsed"),c.find(".tabContent").xfSlideUp(500));return!1})};XenForo.register("li.BRMSTabListener","XenForo.BRMSTabListener");XenForo.register(".BRMSCollapse","XenForo.BRMSCollapse");XenForo.register("li.BRMSNewTabListener","XenForo.BRMSNewTabListener")}(jQuery,this,document);