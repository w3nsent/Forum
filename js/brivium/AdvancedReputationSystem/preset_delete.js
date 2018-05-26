/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	XenForo.BriviumPresetDelete = function($element) { this.__construct($element); };
	XenForo.BriviumPresetDelete.prototype =
	{
		__construct: function($element)
		{
			this.$element = $element;
			this.$form = $element.closest('form');
			this.$selectBox = $element.find('select#ctrl_preset_delete');
			this.$selectBox.change($.context(this, 'update'));
		},

		update: function(e)
		{
			var $presetId  = $(e.target).val();
			var $form = this.$form;
			$form.find('input[name="reason"]').val('').hide();
			$form.find('textarea[name="author_alert_reason"]').text('').hide();
			if($presetId)
			{
				var $form = this.$form;
				var $comment = this.$element.find('.preset_del_'+$presetId).text();
				var $reason = this.$selectBox.children("option").filter(":selected").text();
				console.log($reason);
				$form.find('input[name="reason"]').val($reason);
				$form.find('textarea[name="author_alert_reason"]').text($comment);
			}
			
			if($presetId=='other')
			{
				$form.find('input[name="reason"]').show();
				$form.find('input[name="reason"]').val('');
				$form.find('textarea[name="author_alert_reason"]').show();
			}
		},
	};
	
	XenForo.register('.BriviumPresetDelete', 'XenForo.BriviumPresetDelete');
}
(jQuery, this, document);