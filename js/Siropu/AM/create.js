! function($, window, document, _undefined) {
    $(function() {
        var SamCreate = $('.SamCreate');
        var purchase = $('input[name="purchase"]');
        var selectDiscount = $('input[name="discount"]');
        var discountDisplay = $('#discountDisplay');
        var forumList = $('select[name="forumList"]');
        var threadList = $('select[name="threadList"]');
        var currentSticky = $('#currentSticky');
        var selectKeyword = $('input[name="selectKeyword"]');
        var keywordList = $('textarea[name="items"]');
        var categoryList = $('select[name="categoryList"]');
        var resourceList = $('select[name="resourceList"]');
        var currentResource = $('#currentResource');

        function updateCost() {
            var costAmount = SamCreate.data('cost-amount');
            var purchaseVal = purchase.val() ? purchase.val() : SamCreate.data('max-purchase');
            if (forumList.length) {
                var forumCost = forumList.find(':selected').data('cost');
                forumCost = forumCost ? forumCost : 0;
                if (currentSticky.length && !forumCost) {
                    forumList.find('option').each(function() {
                        if ($(this).val() == currentSticky.data('forum-id')) {
                            forumCost += Number($(this).data('cost'));
                        }
                    });
                }
                if (forumCost) {
                    costAmount = forumCost;
                }
            }
            if (keywordList.length) {
                var keywordCost = 0;
                var keywordListArray = keywordList.val().trim().split("\n");
                if (selectKeyword.length) {
                    var premiumCount = 0;
                    var commonCount = 0;
                    selectKeyword.prop('checked', false);
                    for (i = 0; i < keywordListArray.length; i++) {
                        if (kwd = keywordListArray[i]) {
                            selectKeyword.each(function() {
                                if (kwd.match(new RegExp('\\b' + $(this).val() + '\\b', 'i'))) {
                                    keywordCost += Number($(this).data('cost'));
                                    $(this).prop('checked', true);
                                    premiumCount++;
                                }
                            });
                        }
                    }
                    if (commonCount = (keywordListArray.length - premiumCount)) {
                        keywordCost += Number(commonCount * costAmount);
                    }
                } else {
                    keywordCost += Number(costAmount * keywordListArray.length);
                }
                if (keywordCost) {
                    costAmount = keywordCost;
                }
            }
            if (categoryList.length) {
                var categoryCost = categoryList.find(':selected').data('cost');
                categoryCost = categoryCost ? categoryCost : 0;
                if (currentResource.length && !categoryCost) {
                    categoryList.find('option').each(function() {
                        if ($(this).val() == currentResource.data('category-id')) {
                            categoryCost += Number($(this).data('cost'));
                        }
                    });
                }
                if (categoryCost) {
                    costAmount = categoryCost;
                }
            }
            var cost = Number(costAmount * ((SamCreate.data('cost-per') == 'CPM') ? (purchaseVal / 1000) : purchaseVal));
            var discountPercent = 0;
            var discountAmount = 0;
            var selected = 0;
            selectDiscount.each(function() {
                if (Number(purchaseVal) >= Number($(this).val())) {
                    discountPercent = $(this).data('discount');
                }
                if ($(this).is(':checked')) {
                    selected = $(this).val();
                }
            });
            if (selected != purchaseVal) {
                selectDiscount.prop('checked', false);
            }
            var decimal = 2;
            switch (SamCreate.data('cost-currency')) {
                case 'TWD':
                case 'HUF':
                case 'JPY':
                case 'IRR':
                    decimal = 0;
                    break;
            }
            if (discountPercent) {
                discountAmount = Number(((cost * discountPercent) / 100));
                cost = Number(cost - discountAmount);
                discountDisplay.fadeIn().find('dd').text(discountAmount.toFixed(decimal) + ' ' + SamCreate.data('cost-currency-alt') + ' (' + discountPercent + '%)');
            } else if (discountDisplay.is(':visible')) {
                discountDisplay.hide();
            }
            $('#totalCost span').text(cost.toFixed(decimal));
        }
        updateCost();
        selectDiscount.click(function() {
            purchase.val($(this).val()).focus();
        });
        purchase.on('focus keyup', function() {
            updateCost();
        });
        forumList.change(function() {
            updateCost();
        });
        categoryList.change(function() {
            updateCost();
        });
        selectKeyword.click(function() {
            var checked = [];
            var all = [];
            if ($(this).is(':checked')) {
                checked.push($(this).val());
            }
            all.push($(this).val());
            var keywordListArray = keywordList.val().split("\n");
            for (i = 0; i < keywordListArray.length; i++) {
                if (keywordListArray[i].match(new RegExp('\\b' + $(this).val() + '\\b', 'i'))) {
                    delete keywordListArray[i];
                }
            }
            var keywordListArrayNew = [];
            for (i = 0; i < keywordListArray.length; i++) {
                if (keywordListArray[i] != undefined) {
                    keywordListArrayNew.push(keywordListArray[i].trim());
                }
            }
            keywordList.val(keywordListArrayNew.concat(checked).join("\n").trim()).focus();
        });
        keywordList.on('focus blur', function() {
            updateCost();
        });
        $('#guidelines > a').click(function(e) {
            e.preventDefault();
            $('#guidelines > div').toggle();
        });
        $(document).on('change', 'input[name="banner_extra[]"]', function() {
            $('input[name="banner_extra[]"]').each(function(e) {
                if (!$(this).val() && e) {
                    $(this).parent().remove();
                }
            });
            var lastFile = $('input[name="banner_extra[]"]').last();
            if ($.trim(lastFile.val())) {
                lastFile.parent().after('<li><input type="file" name="banner_extra[]"></li>');
            }
        });
        $('.deleteBanner').click(function(e) {
            e.preventDefault();
            var _this = $(this);
            XenForo.ajax(_this.attr('href'), {}, function(response) {
                if (response) {
                    _this.parent().fadeOut();
                }
            }, {
                error: false
            });
        });
    });
    var ajaxPath = 'index.php?ajax/';
    XenForo.forumList = function($item) {
        $item.change(function() {
            if ($($item).val() == 0) {
                $('#userThreadList').html('');
            }
            XenForo.ajax(ajaxPath + 'getUserForumThreadList', {
                forum_id: $item.val()
            }, function(response) {
                if (response.threadList != undefined) {
                    $('#userThreadList').html(response.threadList);
                }
            }, {
                error: false
            });
        });
    }
    XenForo.register('select[name="forumList"]', 'XenForo.forumList');
    XenForo.keywordList = function($item) {
        var warning = $('#keywordUniquenessWarning');
        $item.on('blur', function() {
            warning.fadeOut();
            var keywords = $item.val().trim();
            if (keywords) {
                XenForo.ajax(ajaxPath + 'checkKeywordUniqueness', {
                    keywords: keywords,
                    adId: $('.SamCreate').data('ad-id')
                }, function(response) {
                    if (response.notUnique != undefined) {
                        warning.html(response.notUnique).fadeIn();
                    }
                }, {
                    error: false
                });
            }
        });
    }
    XenForo.register('textarea[name="items"]', 'XenForo.keywordList');
    XenForo.categoryList = function($item) {
        $item.change(function() {
            if ($($item).val() == 0) {
                $('#userThreadList').html('');
            }
            XenForo.ajax(ajaxPath + 'getUserResourceList', {
                category_id: $item.val()
            }, function(response) {
                if (response.resourceList != undefined) {
                    $('#userResourceList').html(response.resourceList);
                }
            }, {
                error: false
            });
        });
    }
    XenForo.register('select[name="categoryList"]', 'XenForo.categoryList');
}
(jQuery, this, document);
