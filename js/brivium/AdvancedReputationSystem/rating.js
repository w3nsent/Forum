/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	XenForo.BriviumRatingWidget = function($widget)
	{
		var overlay = null,

		$hint = $widget.find('.Hint').each(function()
		{
			var $el = $(this);
			$el.data('text', $el.text());
		}),

		$currentRating = $widget.find('input[name="rating"]'),

		$stars = $widget.find('button').each(function()
		{
			var $el = $(this);
			$el.data('hint', $el.attr('title')).removeAttr('title');
		}),

		setStars = function(starValue)
		{
			$stars.each(function(i)
			{
				// i is 0-4, not 1-5
				$(this)
					.toggleClass('Full', (starValue >= i + 1))
					.toggleClass('Half', (starValue >= i + 0.5 && starValue < i + 1));
			});
		},

		resetStars = function()
		{
			setStars($currentRating.val());

			$hint.text($hint.data('text'));
		};

		$stars.bind(
		{
			mouseenter: function(e)
			{
				e.preventDefault();

				setStars($(this).val());

				$hint.text($(this).data('hint'));
			},

			click: function(e)
			{
				e.preventDefault();

				if (overlay)
				{
					overlay.load();
					return;
				}
				$numberValue = $(this).val();
				$currentRating.val($numberValue);
				if(XenForo.BRATR_isRatingNoForm)
				{
					$widget.submit();
				}
				resetStars();
			}
		});

		$widget.find('span.ratings').mouseleave(function(e)
		{
			resetStars();
		});
		
		resetStars();
	};

	// *********************************************************************

	XenForo.register('form.BriviumRatingWidget', 'XenForo.BriviumRatingWidget');

}
(jQuery, this, document);