/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	XenForo.BriviumPresetComment = function($element) { this.__construct($element); };
	XenForo.BriviumPresetComment.prototype =
	{
		__construct: function($element)
		{
			this.$element = $element;
			this.$form = $element.closest('form');
			$selectBox = $element.find('select#ctrl_preset_comment');
			$selectBox.change($.context(this, 'update'));
		},

		update: function(e)
		{
			var $presetId  = $(e.target).val();
			if($presetId)
			{
				var $form = this.$form;
				var $comment = this.$element.find('.preset_'+$presetId).text();
				$form.find('textarea[name="comment"]').val($comment);
			}
		},
	};
	
	XenForo.register('.BriviumPresetComment', 'XenForo.BriviumPresetComment');
}
(jQuery, this, document);