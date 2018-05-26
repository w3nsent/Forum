/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined) {
	XenForo.VietXfAdvStats_SectionTrigger = function($trigger) { this.__construct($trigger); };
	XenForo.VietXfAdvStats_SectionTrigger.prototype = {
		__construct: function($trigger) {
			if ($trigger.length != 1) return;
			
			var tagName = $trigger[0].tagName;
			if (!tagName) return;
			
			tagName = tagName.toLowerCase();
			
			if (tagName == 'select') {
				this.mode = 'select';
			} else {
				this.mode = 'tab';
			}
			this.$trigger = $trigger;
			this.$panes = $($trigger.data('panes'));
			this.$root = $trigger.parents('.VietXfAdvStats');
		
			switch (this.mode) {
				case 'select':
					this.$options = $trigger.find('option');
					
					this.$trigger.tabs(this.$panes, {
						tabs: 'option',
						current: 'active',
						history: false,
						onBeforeClick: $.context(this, 'onBeforeClick')
					});
					this.api = this.$trigger.data('tabs');
					this.$trigger.change($.context(this, 'selectChanged'))
					break;
				case 'tab':
					$trigger.tabs(this.$panes, {
						current: 'active',
						history: false,
						onBeforeClick: $.context(this, 'onBeforeClick')
					});
					this.api = $trigger.data('tabs');
					break;
			}
		},
		
		selectChanged: function() {
			var selected = this.$trigger.val();
			var index = -1;
			
			this.$options.each(function(i, e) {
				if (e.value && e.value == selected) {
					index = i;
				}
			});
			
			if (index > -1) {
				this.click(index);
			}
		},

		getCurrentTab: function() {
			return this.api.getIndex();
		},

		click: function(index) {
			this.api.click(index);
		},

		onBeforeClick: function(e, index) {
			console.log(index);
			this.$trigger.children().each(function(i) {
				if (index == i) {
					$(this).addClass('active');
				} else {
					$(this).removeClass('active');
				}
			});

			var $pane = $(this.$panes.get(index)),
				loadurl = $pane.data('loadurl');

			if (loadurl) {
				$pane.data('loadurl', '');
				var realThis = this;
				
				data = {};
				
				var rootController = this.$root.data('VietXfAdvStats_Controller');
				if (rootController) {
					data.itemLimit = rootController.getItemLimit();
				}

				XenForo.ajax(
					loadurl,
					data,
					function(ajaxData) {
						if (XenForo.hasTemplateHtml(ajaxData) || XenForo.hasTemplateHtml(ajaxData, 'message')) {
							new XenForo.ExtLoader(ajaxData, function(ajaxData) {
								var $html;
	
								if (ajaxData.templateHtml) {
									$html = $(ajaxData.templateHtml);
								} else if (ajaxData.message) {
									$html = $('<div />').html(ajaxData.message);
								}
	
								$pane.html('');
								if ($html) {
									$html.xfInsert('appendTo', $pane, 'xfFadeIn', 0);
									
									realThis.click(index);
								}
							});
						} else if (XenForo.hasResponseError(ajaxData)) {
							return false;
						}
					},
					{
						type: 'GET'
					}
				);
				
				e.preventDefault();
			}
		}
	};
	
	// *********************************************************************
	
	XenForo.VietXfAdvStats_Root = function($element) { this.__construct($element); };
	XenForo.VietXfAdvStats_Root.prototype = {
		__construct: function($element) {
			$element.data('VietXfAdvStats_Controller', this);
			
			this.bulkUpdateUrl = $element.data('bulkupdateurl');
			this.$itemLimit = $('.VietXfAdvStats_ItemLimit');
			this.$updateInterval = $('.VietXfAdvStats_updateInterval');
			this.queue = {};
			
			// activate updater
			this.active = this.activate();
			
			// register listeners
			$(document).bind('XenForoWindowFocus', $.context(this, 'focus'));
			this.$itemLimit.change($.context(this, 'update'))
			this.$updateInterval.change($.context(this, 'toggerInterval'))
		},
		
		enqueue: function(sectionId, typeMajor, type, action, encodedParams, $container) {
			this.queue[sectionId] = {
				'sectionId': sectionId,
				'typeMajor': typeMajor,
				'type': type,
				'action': action,
				'encodedParams': encodedParams,
				'$container': $container
			};
		},
		
		update: function(force) {
			if (!this.bulkUpdateUrl) {
				return false;
			}
			
			if (!XenForo._hasFocus && !force) {
				return this.deactivate();
			}

			if ($.browser.msie && $.browser.version <= 6) {
				return;
			}
			
			console.count('XenForo.VietXfAdvStats_Root.update()');
			
			data = {};
			for (var sectionId in this.queue) {
				var section = this.queue[sectionId];
				for (var key in section) {
					if (typeof section[key] == 'string') {
						data['sections[' + sectionId + '][' + key + ']'] = section[key];
					}
				}
			}
			
			data['itemLimit'] = this.getItemLimit();
			data['updateInterval'] = this.getUpdateInterval();
			
			XenForo.ajax(
				this.bulkUpdateUrl,
				data, 
				$.context(this, 'loadSuccess')
			);
		},
		
		loadSuccess: function(ajaxData, textStatus) {
			if (ajaxData.rendered) {
				for (var sectionId in ajaxData.rendered) {
					var $target = $('#' + sectionId);
					var $html = $(ajaxData.rendered[sectionId]);

					$target.html('');
					$html.xfInsert('appendTo', $target, 'xfFadeIn', 0);
				}
			}
		},
		
		focus: function(e) {
			if (!this.active) {
				this.activate(true);
			}
		},
		
		activate: function(instant) {
			if (instant) {
				this.update();
			}

			var updateInterval = this.$updateInterval.val();
			if (updateInterval > 0) {
				console.log('updateInterval ' + updateInterval);
				return this.active = window.setInterval($.context(this, 'update'), updateInterval * 1000);
			}
		},

		deactivate: function() {
			window.clearInterval(this.active);
			return this.active = false;
		},
		
		toggerInterval: function() {
			this.deactivate();
			this.activate();
		},
		
		getItemLimit: function() {
			return this.$itemLimit.val();
		},
		
		getUpdateInterval: function() {
			return this.$updateInterval.val();
		}
	};
	
	XenForo.VietXfAdvStats_Section = function($section) { this.__construct($section); };
	XenForo.VietXfAdvStats_Section.prototype = {
		__construct: function($section) {
			var $container = $section.parents('.VietXfAdvStats_BlockContent');
			var $root = $section.parents('.VietXfAdvStats');
			
			if ($container.length > 0 && $root.length > 0) {
				var sectionId = $container.attr('id');
				var rootController = $root.data('VietXfAdvStats_Controller');
				
				if (sectionId && rootController) {
					rootController.enqueue(
						sectionId,
						$section.data('typemajor'),
						$section.data('type'),
						$section.data('requestedaction'),
						$section.data('requestedparams'),
						$container
					);
				}
			}
		}		
	};
	
	// *********************************************************************

	XenForo.register('.VietXfAdvStats', 'XenForo.VietXfAdvStats_Root');
	XenForo.register('.VietXfAdvStats_SectionTrigger', 'XenForo.VietXfAdvStats_SectionTrigger');
	XenForo.register('.VietXfAdvStats_Section', 'XenForo.VietXfAdvStats_Section');
}
(jQuery, this, document);