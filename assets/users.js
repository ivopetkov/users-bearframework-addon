/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

/* global clientPackages, html5DOMDocument */

var ivoPetkov = ivoPetkov || {};
ivoPetkov.bearFrameworkAddons = ivoPetkov.bearFrameworkAddons || {};
ivoPetkov.bearFrameworkAddons.users = ivoPetkov.bearFrameworkAddons.users || (function () {

    var hasCurrentUser = null;

    var makeEvent = function (name) {
        if (typeof Event === 'function') {
            return new Event(name);
        } else {
            var event = document.createEvent('Event');
            event.initEvent(name, false, false);
            return event;
        }
    };

    var onCurrentUserChange = function () {
        currentUserEventTarget.dispatchEvent(makeEvent('change'));
    };

    var logout = function () {
        clientPackages.get('lightbox').then(function (lightbox) {
            var context = lightbox.make();
            clientPackages.get('serverRequests').then(function (serverRequests) {
                serverRequests.send('ivopetkov-users-logout').then(function (responseText) {
                    var result = JSON.parse(responseText);
                    if (result.status === '1') {
                        // hasCurrentUser = false;
                        // removeBadge();
                        // context.close();
                        // onCurrentUserChange();
                        window.location.reload();
                    }
                });
            });
        });
    };

    var login = function (providerID) {
        clientPackages.get('lightbox').then(function (lightbox) {
            var context = lightbox.make();
            clientPackages.get('serverRequests').then(function (serverRequests) {
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
                            addBadge(result.badgeHTML);
                            context.close();
                            onCurrentUserChange();
                        }
                    }
                });
            });
        });
    };

    var removeBadge = function () {
        var badgeElement = document.querySelector('.ivopetkov-users-badge');
        if (badgeElement) {
            badgeElement.parentNode.removeChild(badgeElement);
        }
    };

    var addBadge = function (html) {
        html5DOMDocument.insert(html);
    };

    var updateBadge = function () {
        clientPackages.get('serverRequests').then(function (serverRequests) {
            serverRequests.send('ivopetkov-users-badge').then(function (responseText) {
                var result = JSON.parse(responseText);
                if (typeof result.html !== 'undefined') {
                    removeBadge();
                    if (result.html !== '') {
                        addBadge(result.html);
                    }
                }
            });
        });
    };

    // var openSettings = function () {
    //     clientPackages.get('lightbox').then(function (lightbox) {
    //         var context = lightbox.make();
    //         clientPackages.get('serverRequests').then(function (serverRequests) {
    //             serverRequests.send('ivopetkov-users-settings-window').then(function (responseText) {
    //                 var result = JSON.parse(responseText);
    //                 if (typeof result.html !== 'undefined') {
    //                     context.open(result.html);
    //                 }
    //             });
    //         });
    //     });
    // };

    var openLogin = function () {
        clientPackages.get('lightbox').then(function (lightbox) {
            var context = lightbox.make();
            clientPackages.get('serverRequests').then(function (serverRequests) {
                serverRequests.send('ivopetkov-users-login-screen').then(function (responseText) {
                    var result = JSON.parse(responseText);
                    if (typeof result.html !== 'undefined') {
                        context.open(result.html);
                    }
                });
            });
        });
    };

    var openPreview = function (provider, id) {
        if (typeof provider === "undefined" || typeof id === "undefined") {
            return;
        }
        clientPackages.get('lightbox').then(function (lightbox) {
            var context = lightbox.make();
            var data = {
                'provider': provider,
                'id': id
            };
            clientPackages.get('serverRequests')
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
    };

    var openProviderLogin = function (provider) {
        openProviderScreen(provider, 'login');
    };

    var openProviderSignup = function (provider) {
        openProviderScreen(provider, 'signup');
    };

    var openProviderScreen = function (provider, id) {
        clientPackages.get('lightbox').then(function (lightbox) {
            var context = lightbox.make();
            clientPackages.get('serverRequests').then(function (serverRequests) {
                serverRequests.send('ivopetkov-users-screen-window', { 'provider': provider, 'id': id }).then(function (responseText) {
                    var result = JSON.parse(responseText);
                    if (typeof result.html !== 'undefined') {
                        context.open(result.html);
                    }
                });
            });
        });
    };

    var EventTarget = function () { // window.EventTarget || // Edge does not support the constructor
        var listeners = {};
        this.addEventListener = function (type, callback) {
            if (typeof listeners[type] === 'undefined') {
                listeners[type] = [];
            }
            listeners[type].push(callback);
        };
        this.removeEventListener = function (type, callback) {
            if (typeof listeners[type] === 'undefined') {
                return;
            }
            var callbacks = listeners[type];
            for (var i = 0; i < callbacks.length; i++) {
                if (callbacks[i] === callback) {
                    callbacks.splice(i, 1);
                    return;
                }
            }
        };
        this.dispatchEvent = function (event) {
            if (typeof listeners[event.type] === 'undefined') {
                return true;
            }
            var callbacks = listeners[event.type];
            for (var i = 0; i < callbacks.length; i++) {
                callbacks[i].call(this, event);
            }
            return !event.defaultPrevented;
        };
    };

    var currentUserEventTarget = new EventTarget();

    Promise = window.Promise || function (callback) {
        var thenCallbacks = [];
        var catchCallback = null;
        this.then = function (f) {
            thenCallbacks.push(f);
            return this;
        };
        this.catch = function (f) {
            if (catchCallback === null) {
                catchCallback = f;
            }
            return this;
        };
        var resolve = function () {
            for (var i in thenCallbacks) {
                thenCallbacks[i].apply(null, arguments);
            }
        };
        var reject = function () {
            if (catchCallback !== null) {
                catchCallback.apply(null, arguments);
            }
        };
        window.setTimeout(function () {
            callback(resolve, reject);
        }, 16);
    };

    return {
        'currentUser': {
            'exists': function () {
                return new Promise(function (resolve, reject) {
                    if (hasCurrentUser === null) {
                        clientPackages.get('serverRequests').then(function (serverRequests) {
                            serverRequests.send('ivopetkov-users-currentuser-exists').then(function (responseText) {
                                var result = JSON.parse(responseText);
                                if (result.status === '1') {
                                    hasCurrentUser = result.exists === '1';
                                    onCurrentUserChange();
                                    resolve(hasCurrentUser);
                                }
                            });
                        });
                    } else {
                        resolve(hasCurrentUser);
                    }
                });
            },
            'addEventListener': function (type, listener) {
                currentUserEventTarget.addEventListener(type, listener);
            },
            'removeEventListener': function (type, listener) {
                currentUserEventTarget.removeEventListener(type, listener);
            }
        },
        'login': login,
        'logout': logout,
        'openLogin': openLogin,
        //'openSettings': openSettings,
        'openPreview': openPreview,
        'openProviderScreen': openProviderScreen,
        'openProviderLogin': openProviderLogin,
        'openProviderSignup': openProviderSignup,
        '_updateBadge': updateBadge
    };

}());