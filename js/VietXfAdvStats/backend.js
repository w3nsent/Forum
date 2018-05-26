/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined) {
	
	XenForo.VietXfAdvStats_OptionSectionsListener = function($element) { this.__construct($element); };
	XenForo.VietXfAdvStats_OptionSectionsListener.prototype = {
		__construct: function($element) {			
			$element.find('.VietXfAdvStats_OptionSectionsSelect').one('change', $.context(this, 'createChoice'));

			this.$element = $element;
			if (!this.$base) {
				this.$base = $element.clone();
			}
		},

		createChoice: function() {
			var $new = this.$base.clone(),
				nextCounter = this.$element.parent().children().length;

			$new.find('*[name]').each(function() {
				var $this = $(this);
				$this.attr('name', $this.attr('name').replace(/\[(\d+)\]/, '[' + nextCounter + ']'));
			});
			
			$new.find('*[id]').each(function() {
				var $this = $(this);
				$this.removeAttr('id');
				XenForo.uniqueId($this);

				if (XenForo.formCtrl) {
					XenForo.formCtrl.clean($this);
				}
			});

			$new.xfInsert('insertAfter', this.$element);

			this.__construct($new);
		}
	};

	// *********************************************************************
	
	XenForo.VietXfAdvStats_OptionSectionsSingle = function($element) { this.__construct($element); };
	XenForo.VietXfAdvStats_OptionSectionsSingle.prototype = {
		__construct: function($element) {
			this.$typeSelect = $element.find('.VietXfAdvStats_OptionSectionsSelect');
			this.$forumSelect = $element.find('.VietXfAdvStats_OptionSections_ForumSelect');
			this.$forumSelectInput = this.$forumSelect.find('select');
			
			this.selectedType = 'x';
			this.isInitCall = true;
			
			this.$typeSelect.bind('change', $.context(this, 'change'));
			
			this.change();
		},

		change: function() {
			var newType = this.$typeSelect.val();
			
			if (newType == this.selectedType) return;
			
			var speed = XenForo.speed.fast;
			if (this.isInitCall) {
				speed = 0;
			}
			
			if (newType.indexOf('custom_forum') > -1) {
				// show the forum select
				this.$forumSelect.xfFadeDown(speed, null);
				
				this.$forumSelectInput
					.removeAttr('disabled')
					.removeClass('disabled');
			} else {
				// hide the forum select
				this.$forumSelect.xfFadeUp(speed, null, XenForo.speed.fast, 'easeInBack');
				
				this.$forumSelectInput
					.attr('disabled', true)
					.addClass('disabled');
			}
			
			this.selectedType = newType;
			this.isInitCall = false;
		}
	};

	// *********************************************************************
	
	XenForo.VietXfAdvStats_OptionNumbersListener = function($element) { this.__construct($element); };
	XenForo.VietXfAdvStats_OptionNumbersListener.prototype = {
		__construct: function($element) {			
			$element.find('input').one('keypress', $.context(this, 'createChoice'));

			this.$element = $element;
			if (!this.$base) {
				this.$base = $element.clone();
			}
		},

		createChoice: function() {
			var $new = this.$base.clone(),
				nextCounter = this.$element.parent().children().length;

			$new.find('*[name]').each(function() {
				var $this = $(this);
				$this.attr('name', $this.attr('name').replace(/\[(\d+)\]/, '[' + nextCounter + ']'));
			});
			
			$new.find('*[id]').each(function() {
				var $this = $(this);
				$this.removeAttr('id');
				XenForo.uniqueId($this);

				if (XenForo.formCtrl) {
					XenForo.formCtrl.clean($this);
				}
			});

			$new.xfInsert('insertAfter', this.$element);

			this.__construct($new);
		}
	};
	
	// *********************************************************************

	XenForo.register('li.VietXfAdvStats_OptionSectionsListener', 'XenForo.VietXfAdvStats_OptionSectionsListener');
	XenForo.register('div.VietXfAdvStats_OptionSectionsSingle', 'XenForo.VietXfAdvStats_OptionSectionsSingle');
	
	XenForo.register('li.VietXfAdvStats_OptionNumbersListener', 'XenForo.VietXfAdvStats_OptionNumbersListener');
	
}
(jQuery, this, document);