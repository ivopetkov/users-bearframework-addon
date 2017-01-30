/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

var ivoPetkov = ivoPetkov || {};
ivoPetkov.bearFrameworkAddons = ivoPetkov.bearFrameworkAddons || {};

ivoPetkov.bearFrameworkAddons.users = (function () {

    var jsLightbox = null;
    var currentUser = null;
    var providers = [];
    var pleaseWaitText = '';
    var logoutButtonText = '';

    var initialize = function (data) {
        currentUser = data.currentUser;
        providers = data.providers;
        pleaseWaitText = data.pleaseWaitText;
        logoutButtonText = data.logoutButtonText;
    };

    var logoutClick = function () {
        showLoading();
        ivoPetkov.bearFrameworkAddons.serverRequests.send('ivopetkov-users-logout', {}, function (responseText) {
            var result = JSON.parse(responseText);
            if (result.status === '1') {
                var badgeElement = document.querySelector('.ivopetkov-users-badge');
                if (badgeElement) {
                    currentUser = null;
                    badgeElement.parentNode.removeChild(badgeElement);
                    closeWindow();
                }
            }
        });
    };

    var attachClickHandler = function (code, handler) {
        var element = document.querySelector('.ivopetkov-users-window').querySelector('[data-ivopetkov-users-type="' + code + '"]');
        if (element !== null) {
            element.addEventListener('click', handler);
        }
    };

    var providerClick = function (providerID) {
        var data = {
            'type': providerID,
            'location': window.location.toString()
        };
        showLoading();
        ivoPetkov.bearFrameworkAddons.serverRequests.send('ivopetkov-users-login', data, function (responseText) {
            var result = JSON.parse(responseText);
            if (result.status === '1') {
                if (typeof result.redirectUrl !== 'undefined') {
                    window.location = result.redirectUrl;
                } else {
                    html5DOMDocument.insert(result.badgeHTML);
                    currentUser = result.currentUser;
                    closeWindow();
                }
            }
        });
    };

    var openWindow = function (html) {
        closeWindow();
        jsLightbox = new ivoPetkov.bearFrameworkAddons.jsLightbox({
            'images': [
                {'html': '<div class="ivopetkov-users-window">' + html + '</div>'}
            ]
        });
        jsLightbox.open(0);
        for (var i in providers) {
            var provider = providers[i];
            attachClickHandler(provider.id, (function (providerID) {
                return function () {
                    providerClick(providerID);
                }
            })(provider.id));
        }
        attachClickHandler('logout', logoutClick);
    };

    var showLoading = function () {
        var html = '';
        html += '<div class="ivopetkov-users-loading">' + pleaseWaitText + '</div>';
        openWindow(html);
    };

    var closeWindow = function () {
        if (jsLightbox !== null) {
            jsLightbox.close();
            jsLightbox = null;
        }
    };

    var showLogin = function () {
        var html = '';
        for (var i in providers) {
            var provider = providers[i];
            html += '<div><a class="ivopetkov-users-login-option-button" data-ivopetkov-users-type="' + provider.id + '">' + provider.buttonText + '</a></div>';
        }
        openWindow(html);
    };

    var showAccount = function () {
        if (currentUser === null) {
            return;
        }
        var html = '';
//        if (currentUser.imageLarge.length > 0) {
//            html += '<div><div class="ivopetkov-users-account-image" style="background-image:url(' + currentUser.imageLarge + ');"></div></div>';
//        }
        if (currentUser.name.length > 0) {
            html += '<div><div class="ivopetkov-users-account-name">' + currentUser.name + '</div></div>';
        }
        if (currentUser.descriptionHTML.length > 0) {
            html += '<div><div class="ivopetkov-users-account-description">' + currentUser.descriptionHTML + '</div></div>';
        }
        if (currentUser.hasLogout > 0) {
            html += '<div><a class="ivopetkov-users-account-logout-button" data-ivopetkov-users-type="logout">' + logoutButtonText + '</a></div>';
        }
        openWindow(html);
    };

    var currentUserExists = function () {
        return currentUser !== null;
    };

    return {
        'currentUser': {
            'exists': currentUserExists
        },
        'initialize': initialize,
        'showLogin': showLogin,
        'showAccount': showAccount
    };

}());