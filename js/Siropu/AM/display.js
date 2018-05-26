! function($, window, document, _undefined) {
    $(function() {
         if (window.samSupportUs !== undefined && window.samDetected === undefined) {
              var $samSupportUs = $('.samSupportUs').detach();
              if (window.samSupportUs == 'replace' || window.samSupportUs == 'backup') {
                   $('.samCodeUnit .SamLink, .samBannerUnit .SamLink').each(function() {
                        if (!$(this).height()) {
                           if (window.samSupportUs == 'backup') {
                                var backupAd = $(this).find('.samBackup');
                                backupAd.find('img').each(function() {
                                     $(this).attr('src', $(this).data('src'));
                                });
                                backupAd.fadeIn();
                           } else {
                                $samSupportUs.clone().appendTo($(this)).fadeIn();
                           }
                        }
                  });
             } else {
                  var adUnits = $('.samCodeUnit .SamLink, .samBannerUnit .SamLink');
                  if (adUnits.length) {
                       var adsBlocked = 0;
                       adUnits.each(function() {
                            if (!$(this).height()) {
                                 adsBlocked += 1;
                            };
                      });
                      if (adsBlocked) {
                           XenForo.createOverlay(null, $samSupportUs.css('display', 'block'), {
                               noCache: true,
                               fixed: true,
                               className: 'samSupportUsOverlay'
                           }).load();
                           $('head').append('<style>body::-webkit-scrollbar{display: none;}</style>');
                           $('.samSupportUsOverlay').find('.close').remove();
                           $('#exposeMask').css('background-color', 'black').fadeTo('slow', 0.9);
                           $('div, a, span').unbind();
                           $(window).scroll(function() {
                                $(this).scrollTop(0);
                           });
                      }
                  }
             }
         }
        if ($('.SamRotator').length) {
            $('.SamRotator').each(function() {
                var _this = $(this);
                var adList = _this.find('li');
                var SamLink = _this.find('.SamLink');
                var adCount = adList.length;
                setInterval(function() {
                    adList.each(function() {
                        var index = adList.index($(this));
                        if ($(this).is(':visible')) {
                            $(this).hide();
                            if (index == (adCount - 1)) {
                                adList.eq(0).fadeIn();
                                actionCountView(SamLink.eq(0));
                            } else {
                                adList.eq(index + 1).fadeIn();
                                actionCountView(SamLink.eq(index + 1));
                            }
                            return false;
                        }
                    });
                    if (!_this.find('li:visible').length) {
                        adList.eq(0).fadeIn();
                    }
                }, _this.data('interval') * 1000);
            });
        }
        if ($('.SamTimer').length) {
            $('.SamTimer').each(function() {
                var _this = $(this);
                var display = _this.data('display');
                var hide = _this.data('hide');
                if (display) {
                    setTimeout(function() {
                        _this.fadeIn();
                    }, display * 1000);
                }
                if (hide) {
                    setTimeout(function() {
                        _this.fadeOut();
                    }, hide * 1000);
                }
            });
        }
        var isOverSamLink = false;
        $('.SamLink iframe').mouseover(function() {
            isOverSamLink = $(this).parents('.SamLink');
        }).mouseout(function() {
            isOverSamLink = false;
        });
        $(window).blur(function() {
            if (isOverSamLink) {
                actionCountClick(isOverSamLink);
            }
        });
        $('.video-js, #samMediaClose').click(function() {
            $('.samMediaContainer').fadeOut();
        });
        if (!$('.samMediaFull').length) {
            var mediaContainerWidth = $('.samMediaContainer').width();
            var mediaAdUnitWidth = $('.samMediaContainer > ul li').outerWidth();
            var marginRight = ((mediaContainerWidth - mediaAdUnitWidth) / 2);
            $('#samMediaClose').css('margin-right', marginRight ? marginRight : 10);
        }
    });
    var ajaxPath = 'index.php?ajax/ad-action';

    function isScrolledIntoView(elem) {
        var docViewTop = $(window).scrollTop();
        var docViewBottom = docViewTop + $(window).height();
        var elemTop = $(elem).offset().top;
        var elemBottom = elemTop + $(elem).height();
        return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
    }
    var impressions = [];

    function actionCountView($item) {
        $item.each(function() {
            if (!$item.data('cv') || $item.attr('data-viewed')) {
                return false;
            }
            var id = $item.data('id');
            var position = $item.data('pos') ? $item.data('pos') : $item.parents('ul').data('pos');
            if (samViewCountMethod == 'view' && $item.is(':visible') && isScrolledIntoView($item)) {
                XenForo.ajax(ajaxPath, {
                    action: 'view',
                    id: id,
                    position: position
                }, function(ajaxData) {
                    if ($item.data('ga') && window.ga !== undefined && ajaxData.adName !== undefined) {
                        ga('send', 'event', 'Ads', 'View', ajaxData.adName + ' (' + ajaxData.posName + ')');
                    }
                }, {
                    global: false,
                    error: false
                });
                if (position === undefined) {
                    $('.SamLink[data-id="' + id + '"]').attr('data-viewed', 1);
                } else {
                    $item.attr('data-viewed', 1);
                }
            } else if (samViewCountMethod == 'impression') {
                impressions.push([id, position]);
                if (position === undefined) {
                    $('.SamLink[data-id="' + id + '"]').attr('data-viewed', 1);
                } else {
                    $item.attr('data-viewed', 1);
                }
            }
        });
    }
    setTimeout(function() {
        if (impressions.length) {
            XenForo.ajax('index.php?ajax/count-impressions', {
                impressions: impressions
            }, function(ajaxData) {
                if (ajaxData.gaData !== undefined && window.ga !== undefined) {
                    for (var adId in ajaxData.gaData) {
                        if (ajaxData.gaData[adId]['positions'] !== undefined) {
                            for (var hook in ajaxData.gaData[adId]['positions']) {
                                ga('send', 'event', 'Ads', 'View', ajaxData.gaData[adId]['name'] + ' (' + ajaxData.gaData[adId]['positions'][hook] + ')');
                            }
                        }
                    }
                }
            }, {
                global: false,
                error: false
            });
        }
    }, 1500);

    function actionCountClick($item) {
        if ($item.data('cc') && !$item.attr('data-clicked')) {
            var id = $item.data('id');
            var position = $item.data('pos') ? $item.data('pos') : $item.parents('ul').data('pos');
            XenForo.ajax(ajaxPath, {
                action: 'click',
                id: id,
                position: position,
                page_url: window.location.href
            }, function(ajaxData) {
                $item.attr('data-clicked', 1);
                if (ajaxData.adName != undefined && window.ga != undefined) {
                    ga('send', 'event', 'Ads', 'Click', ajaxData.adName + ' (' + ajaxData.posName + ')');
                }
            }, {
                global: false,
                error: false
            });
        }
    }
    XenForo.SamLink = function($item) {
        if (!window.location.hash || samViewCountMethod == 'impression') {
            actionCountView($item);
        }
        $(window).scroll(function() {
            actionCountView($item);
        });
        $item.click(function() {
            actionCountClick($item);
        });
        if ($item.find('object').length || $item.find('embed').length) {
            $item.mousedown(function() {
                setTimeout(function() {
                    actionCountClick($item);
                }, 1000);
            });
        }
    }
    XenForo.register('.SamLink', 'XenForo.SamLink');
}
(jQuery, this, document);
