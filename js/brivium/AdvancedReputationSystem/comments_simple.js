/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	XenForo.BriviumReputationLoader = function($element) { this.__construct($element); };
	XenForo.BriviumReputationLoader.prototype =
	{
		__construct: function($link)
		{
			this.$link = $link;

			$link.click($.context(this, 'click'));
		},

		click: function(e)
		{
			var params = this.$link.data('loadparams');

			if (typeof params != 'object')
			{
				params = {};
			}

			e.preventDefault();

			XenForo.ajax(
				this.$link.attr('href'),
				params,
				$.context(this, 'loadSuccess'),
				{ type: 'GET' }
			);
		},

		loadSuccess: function(ajaxData)
		{
			var $replace,
				replaceSelector = this.$link.data('replace'),
				els = [], $els, i;
			if (XenForo.hasResponseError(ajaxData))
			{
				return false;
			}

			if (replaceSelector)
			{
				$replace = $(replaceSelector);
			}
			else
			{
				$replace = this.$link.parent();
			}

			$reputationList = this.$link.closest('ol.BriviumReputationList');
			
			if (ajaxData.reputations && ajaxData.reputations.length)
			{
				for (i = 0; i < ajaxData.reputations.length; i++)
				{
					$id = $(ajaxData.reputations[i]).attr('id');
					if(!$reputationList.find('#'+$id).length)
					{
						$.merge(els, $(ajaxData.reputations[i]));
					}
				}

				// xfInsert didn't like this
				$els = $(els).hide();
				$replace.xfFadeUp().replaceWith($els);
				$els.xfActivate().xfFadeDown();

				for (i = 0; i < ajaxData.reputations.length; i++)
				{
					$(ajaxData.reputations[i]).xfInsert('insertBefore', $replace);
				}
				$replace.xfHide();
			}
			else
			{
				$replace.xfRemove();
			}
		}
	};

	XenForo.BriviumReputationPoster = function($element) { this.__construct($element); };
	XenForo.BriviumReputationPoster.prototype =
	{
		__construct: function($link)
		{
			this.$link = $link;
			this.$reputationArea = $($link.data('reputationarea'));
			this.submitUrl = $link.attr('href');

			$link.click($.context(this, 'click'));
			this.$reputationArea.find('input:submit, button').click($.context(this, 'submit'));
		},

		click: function(e)
		{
			e.preventDefault();

			this.$reputationArea.xfFadeDown(XenForo.speed.fast, function()
			{
				$(this).find('textarea[name="comment"]').focus();
			});
		},

		submit: function(e)
		{
			e.preventDefault();

			var $points = this.$reputationArea.find('input[name="points"]').val();
			var $comment = this.$reputationArea.find('textarea[name="comment"]').val();
			var $checkBox = this.$reputationArea.find('input[name="is_anonymous"]');
			
			var $isAnonymous = 0;
			if($checkBox.is(':checked'))
			{
				$isAnonymous = 1;
			}
			console.log($isAnonymous);
			XenForo.ajax(
				this.submitUrl,
				{
					points: $points,
					comment: $comment,
					is_anonymous: $isAnonymous
				},
				$.context(this, 'submitSuccess')
			);
		},

		submitSuccess: function(ajaxData)
		{

			if (XenForo.hasResponseError(ajaxData))
			{
				return false;
			}

			if (ajaxData.reputation)
			{
				$(ajaxData.reputation).xfInsert('insertBefore', this.$reputationArea);
			}else if(ajaxData._redirectTarget.length)
			{
				if(ajaxData._redirectMessage.length)
				{
					XenForo.alert(ajaxData._redirectMessage, '', 4000);
				}
				XenForo.redirect(ajaxData._redirectTarget);
			}
			
			this.$reputationArea.remove();
			this.$reputationArea.find('input[name="points"]').val(0);
			this.$reputationArea.find('textarea[name="comment"]').val('');
			this.$reputationArea.find('input[name="is_anonymous"]').attr('checked', false);
		}
	};

	XenForo.register('a.BriviumReputationLoader', 'XenForo.BriviumReputationLoader');
	XenForo.register('a.BriviumReputationPoster', 'XenForo.BriviumReputationPoster');

}
(jQuery, this, document);