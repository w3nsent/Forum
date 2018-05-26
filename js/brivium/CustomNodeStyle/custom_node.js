/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	XenForo.BRCNSToggle = function($button) { this.__construct($button); };
	XenForo.BRCNSToggle.prototype =
	{
		__construct: function($button)
		{
			this.$button = $button;
			this.$button.click($.context(this,'eclick'));
			if (XenForo.isRTL())
			{
				this.$button.css('left', '10px');
			}
			else
			{
				this.$button.css('right', '10px');
			}

			this.$container = this.$button.closest('.node.level_1');
			this.$nodeList = this.$container.find('.nodeList');
			this.$id = this.$container.attr('id');

			if(this.$nodeList.hasClass('collapse')){
				this.$nodeList.hide();
				this.$button.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
			}
			if ($.getCookie(this.$id) == 1) {
				this.$nodeList.addClass('collapse');
				this.$nodeList.hide();
				this.$button.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
			}else if ($.getCookie(this.$id) == 2) {
				this.$nodeList.removeClass('collapse');
				this.$nodeList.show();
				this.$button.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
			}
		},

		eclick: function(e)
		{
			e.preventDefault();
			if(!this.$nodeList.hasClass('collapse')){
				this.$nodeList.addClass('collapse');
				this.$nodeList.stop(true,true).xfSlideUp();
				this.$button.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
				$.setCookie(this.$id, '1');
			}else{
				this.$nodeList.removeClass('collapse');
				this.$nodeList.stop(true,true).xfSlideDown();
				this.$button.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
				$.setCookie(this.$id, '2');
			}
		}
	};
	XenForo.BRCNSToggleSearch = function($button) { this.__construct($button); };
	XenForo.BRCNSToggleSearch.prototype =
	{
		__construct: function($button)
		{
			this.$button = $button;
			this.$search = this.$button.closest('div').find('.brSearch');
			var $categoryIcon = $('.categoryIcon');

			if (XenForo.isRTL())
			{
				this.$button.css('left', '15px');
				this.$search.css('left', '45px');
				$categoryIcon.css('float', 'right');
				$categoryIcon.children().css('margin-right', '0px').css('margin-left', '10px');
			}
			else
			{
				this.$button.css('right', '15px');
				this.$search.css('right', '45px');
				$categoryIcon.css('float', 'left');
			}

			this.$search.css('display', 'none');
			this.$width = this.$search.width();
			this.$button.click($.context(this,'eclick'));
		},
		eclick: function(e)
		{
			e.preventDefault();
			this.$search.stop(true,true).animate({width: "toggle"},300);
		}
	};

	XenForo.BRCNSSearch = function($list) { this.__construct($list); };
	XenForo.BRCNSSearch.prototype =
	{
		__construct: function($list)
		{
			this.$list = $list;
			this.registerListItems();
			this.$form = this.$list.closest('li');

			if (this.activateFilterControls())
			{
				this.filter();
			}
		},

		/**
		 * Finds and activates the filter controls for the list
		 */
		activateFilterControls: function()
		{
			if (this.$form.length)
			{
				this.$filter = $('input[class="brSearch"]', this.$form)
					.keyup($.context(this, 'filterKeyUp'))
					.bind('search', $.context(this, 'instantSearch'))
					.keypress($.context(this, 'filterKeyPress'));
			}

			return false;
		},

		/**
		 * Create XenForo.FilterListItem objects for each list item
		 *
		 * @return array this.listItems
		 */
		registerListItems: function()
		{
			this.FilterListItems = [];

			this.$listItems = this.$list.find('.node');

			this.$listItems.each($.context(function(i)
			{
				this.FilterListItems.push(new XenForo.FilterListItem($(this.$listItems[i])));
			}, this));
		},

		/**
		 * A little speed-up for live typing
		 *
		 * @param event e
		 *
		 * @return
		 */
		filterKeyUp: function(e)
		{
			if (e.keyCode == 13)
			{
				// enter key - instant search
				this.instantSearch();
				return;
			}

			clearTimeout(this.timeOut);
			this.timeOut = setTimeout($.context(this, 'filter'), 250);
		},

		/**
		 * Filters key press events to make enter search instantly
		 *
		 * @param event e
		 */
		filterKeyPress: function(e)
		{
			if (e.keyCode == 13)
			{
				// enter - disable form submitting
				e.preventDefault();
			}
		},

		/**
		 * Instantly begins a search.
		 */
		instantSearch: function()
		{
			clearTimeout(this.timeOut);
			this.filter();
		},

		/**
		 * Filters the list of templates according to the filter and prefixmatch controls
		 *
		 * @param event e
		 */
		filter: function(e)
		{
			var val = this.$filter.data('XenForo.Prompt').val();

			if (this.$filter.hasClass('prompt') || val === '')
			{
				this.$listItems.show();
				this.applyFilter(this.FilterListItems);

				this.showHideNoResults(false);
				return;
			}

			console.log('Filtering on \'%s\'', val);

			var $groups,
				visible = this.applyFilter(this.FilterListItems);

			this.showHideNoResults(visible ? false : true);
		},

		applyFilter: function(items)
		{
			var i,
				visible = 0,
				filterRegex = new RegExp(
					''
					+ '(' + XenForo.regexQuote(this.$filter.data('XenForo.Prompt').val()) + ')', 'i');

			for (i = items.length - 1; i >= 0; i--) // much faster than .each(...)
			{
				visible += items[i].filter(filterRegex);
			}

			return visible;
		},

		showHideNoResults: function(show)
		{
			var $noRes = $('#noResults');

			if (show)
			{
				if (!$noRes.length)
				{
					$noRes = $('<li id="noResults" class="listNote" style="display:none" />')
						.text(XenForo.phrases.no_items_matched_your_filter || 'No items matched your filter.');

					this.$list.append($noRes);
				}

				$noRes.xfFadeIn(XenForo.speed.normal);
			}
			else
			{
				$noRes.xfHide();
			}
		}
	};
	XenForo.FilterListItem = function($item) { this.__construct($item); };
	XenForo.FilterListItem.prototype =
	{
		__construct: function($item)
		{
			this.$item = $item;
			this.$textContainer = this.$item.find('.nodeTitle:first a');
			this.text = this.$textContainer.text();
		},

		/**
		 * Show or hide the item based on whether its text matches the filterRegex
		 *
		 * @param regexp filterRegex
		 *
		 * @return integer 1 if matched, 0 if not
		 */
		filter: function(filterRegex)
		{
			if (this.text.match(filterRegex))
			{
				this.$textContainer.html(this.text.replace(filterRegex, '<strong>$1</strong>'));
				var display;
				if(typeof(this.$item.attr('data-display')) !== 'undefined'){
					display = this.$item.attr('data-display');
				}
				if(!display){
					display = 'block';
				}
				this.$item.css('display', display); // much faster in Opera

				return 1;
			}
			else
			{
				if(typeof(this.$item.attr('data-display')) !== 'undefined'){
					this.$item.attr('data-display', this.$item.css('display'));
				}
				this.$item.css('display', 'none'); // much faster in Opera
				return 0;
			}
		}
	};
	XenForo.BRCNSFixWidth = function($item) { this.__construct($item); };
	XenForo.BRCNSFixWidth.prototype =
	{
		__construct: function($item)
		{
			this.$item = $item;
			var width = this.$item.width(),
				minWidth = parseInt(this.$item.css('min-width').replace('px', ''));
			var $containerWidth = this.$item.closest('ol').width();

			if ($containerWidth < width) {
				this.$item.css('min-width', $containerWidth);
			}
			var $width = this.$item.width();
			if ($width == minWidth) {
				this.$item.css('width', '100%');
			}
		}
	};

	XenForo.register('.brcnsButton', 'XenForo.BRCNSToggle');
	XenForo.register('.brcnsSearch', 'XenForo.BRCNSToggleSearch');
	XenForo.register('.node .nodeList', 'XenForo.BRCNSSearch');
	XenForo.register('.node.level_2', 'XenForo.BRCNSFixWidth');
}(jQuery, this, document);