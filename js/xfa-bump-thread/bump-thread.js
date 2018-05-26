/**
 * Any link that contains a RequestApproval is actually an AJAX call, bind to that now
 */
!function($, window, document, _undefined) {

	XenForo.Bump = function($link) {
		var callback = function(ajaxData, textStatus) {
			if (ajaxData.error) {
				XenForo.hasResponseError(ajaxData, textStatus);
			} else {
				XenForo.alert(ajaxData._redirectMessage, '', 2000, function() {
					document.location.href = ajaxData._redirectTarget;
				});	
			}
		};
		
		$link.click(function(e) {
			e.preventDefault();
			XenForo.ajax($link.attr('href'), {}, callback);
		});		
	};
	
	XenForo.register('a.Bump', 'XenForo.Bump');

}(jQuery, this, document);