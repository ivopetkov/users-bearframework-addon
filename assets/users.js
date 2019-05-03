/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

var ivoPetkov = ivoPetkov || {};
ivoPetkov.bearFrameworkAddons = ivoPetkov.bearFrameworkAddons || {};
ivoPetkov.bearFrameworkAddons.users = ivoPetkov.bearFrameworkAddons.users || (function () {

    var hasCurrentUser = false;

    var initialize = function (currentUserExists) {
        hasCurrentUser = typeof currentUserExists !== 'undefined' ? currentUserExists > 0 : false;
    };

    var logout = function () {
        clientShortcuts.get('lightbox').then(function (lightbox) {
            lightbox.wait(function (context) {
                clientShortcuts.get('serverRequests').then(function (serverRequests) {
                    serverRequests.send('ivopetkov-users-logout').then(function (responseText) {
                        var result = JSON.parse(responseText);
                        if (result.status === '1') {
                            hasCurrentUser = false;
                            var badgeElement = document.querySelector('.ivopetkov-users-badge');
                            if (badgeElement) {
                                badgeElement.parentNode.removeChild(badgeElement);
                            }
                            context.close();
                        }
                    });
                });
            });
        });
    };

    var login = function (providerID) {
        clientShortcuts.get('lightbox').then(function (lightbox) {
            lightbox.wait(function (context) {
                clientShortcuts.get('serverRequests').then(function (serverRequests) {
                    var data = {
                        'provider': providerID,
                        'location': window.location.toString()
                    };
                    serverRequests.send('ivopetkov-users-login', data).then(function (responseText) {
                        var result = JSON.parse(responseText);
                        if (result.status === '1') {
                            hasCurrentUser = true;
                            if (typeof result.jsCode !== 'undefined') {
                                (new Function(result.jsCode))();
                            }
                            if (typeof result.redirectUrl !== 'undefined') {
                                window.location = result.redirectUrl;
                            } else {
                                html5DOMDocument.insert(result.badgeHTML);
                                context.close();
                            }
                        }
                    });
                });
            });
        });
    };

    var openSettings = function () {
        clientShortcuts.get('lightbox').then(function (lightbox) {
            lightbox.wait(function (context) {
                clientShortcuts.get('serverRequests').then(function (serverRequests) {
                    serverRequests.send('ivopetkov-users-settings-window').then(function (responseText) {
                        var result = JSON.parse(responseText);
                        if (typeof result.html !== 'undefined') {
                            context.open(result.html);
                        }
                    });
                });
            });
        });
    };

    var openLogin = function () {
        clientShortcuts.get('lightbox').then(function (lightbox) {
            lightbox.wait(function (context) {
                clientShortcuts.get('serverRequests').then(function (serverRequests) {
                    serverRequests.send('ivopetkov-users-login-screen').then(function (responseText) {
                        var result = JSON.parse(responseText);
                        if (typeof result.html !== 'undefined') {
                            context.open(result.html);
                        }
                    });
                });
            });
        });
    };

    var openPreview = function (provider, id) {
        if (typeof provider === "undefined" || typeof id === "undefined") {
            return;
        }
        clientShortcuts.get('lightbox').then(function (lightbox) {
            lightbox.wait(function (context) {
                var data = {
                    'provider': provider,
                    'id': id
                };
                clientShortcuts.get('serverRequests')
                        .then(function (serverRequests) {
                            serverRequests.send('ivopetkov-users-preview-window', data)
                                    .then(function (responseText) {
                                        var result = JSON.parse(responseText);
                                        if (typeof result.html !== 'undefined') {
                                            context.open(result.html);
                                        }
                                    })
                                    .catch(function () {
                                        context.close();
                                    });
                        });

            });
        });
    };

    return {
        'currentUser': {
            'exists': function () {
                return hasCurrentUser;
            }
        },
        'initialize': initialize,
        'login': login,
        'logout': logout,
        'openLogin': openLogin,
        'openSettings': openSettings,
        'openPreview': openPreview
    };

}());