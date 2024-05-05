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

    var logout = function (options) {
        if (typeof options === 'undefined') {
            options = {};
        }
        if (typeof options.reloadWindow === 'undefined') {
            options.reloadWindow = true;
        }
        return new Promise(function (resolve, reject) {
            var finalizeLogout = function () {
                hasCurrentUser = false;
                var cookies = getCookies();
                for (var cookieKey in cookies) {
                    if (cookieKey.indexOf('ipu2-') === 0) {
                        document.cookie = cookieKey + '=;path=/;expires=Thu, 01 Jan 1970 00:00:01 GMT;domain=' + location.host + ';';
                    }
                }
                if (options.reloadWindow) {
                    window.location.reload();
                }
            };
            var handleError = function () {
                finalizeLogout();
                resolve();
            };
            clientPackages.get('modalWindows').then(function (modalWindows) {
                modalWindows.closeAll({ expectShowLoading: true }).then(function () {
                    modalWindows.showLoading({ closeOnEscKey: false }).then(function () {
                        clientPackages.get('serverRequests').then(function (serverRequests) {
                            serverRequests.send('ivopetkov-users-logout').then(function (responseText) {
                                try {
                                    var result = JSON.parse(responseText);
                                } catch (e) {
                                    handleError();
                                    return;
                                }
                                if (typeof result.status !== "undefined" && result.status === '1') {
                                    // hasCurrentUser = false;
                                    // removeBadge();
                                    // context.close();
                                    // onCurrentUserChange();
                                    finalizeLogout();
                                    resolve();
                                } else {
                                    handleError();
                                }
                            }).catch(handleError);
                        }).catch(handleError);
                    }).catch(handleError);
                }).catch(handleError);
            }).catch(handleError);
        });
    };

    var login = function (providerID) {
        return new Promise(function (resolve, reject) {
            var handleError = function () {
                reject();
            };
            clientPackages.get('modalWindows').then(function (modalWindows) {
                modalWindows.closeAll({ expectShowLoading: true }).then(function () {
                    modalWindows.showLoading({ closeOnEscKey: false }).then(function () {
                        clientPackages.get('serverRequests').then(function (serverRequests) {
                            var data = {
                                'provider': providerID,
                                'location': window.location.toString()
                            };
                            serverRequests.send('ivopetkov-users-login', data).then(function (responseText) {
                                try {
                                    var result = JSON.parse(responseText);
                                } catch (e) {
                                    handleError();
                                    return;
                                }
                                if (typeof result.status !== "undefined" && result.status === '1') {
                                    if (typeof result.exists !== 'undefined') {
                                        hasCurrentUser = result.exists;
                                    }
                                    if (typeof result.jsCode !== 'undefined') {
                                        (new Function(result.jsCode))();
                                    }
                                    if (typeof result.redirectURL !== 'undefined') {
                                        window.location = result.redirectURL;
                                    } else {
                                        if (typeof result.badgeHTML !== 'undefined') {
                                            html5DOMDocument.insert(result.badgeHTML);
                                        }
                                        modalWindows.hideLoading().then(function () {
                                            modalWindows.closeAll();
                                        });
                                        onCurrentUserChange();
                                    }
                                    resolve();
                                } else {
                                    handleError();
                                }
                            }).catch(handleError);
                        }).catch(handleError);
                    }).catch(handleError);
                }).catch(handleError);
            }).catch(handleError);
        });
    };

    var removeBadge = function () {
        var badgeElement = document.querySelector('.ivopetkov-users-badge');
        if (badgeElement) {
            badgeElement.parentNode.removeChild(badgeElement);
            return true;
        }
        return false;
    };

    var updateBadge = function (badgeHTML) {
        if (removeBadge()) {
            html5DOMDocument.insert(badgeHTML);
        }
    };

    var dispatchProfileChange = function () {
        onCurrentUserChange();
    };

    var openLogin = function () {
        clientPackages.get('modalWindows').then(function (modalWindows) {
            modalWindows.open('ivopetkov-users-login-screen', {}, { showErrors: true });
        });
    };

    var openPreview = function (provider, id) {
        if (typeof provider === "undefined" || typeof id === "undefined") {
            return;
        }
        clientPackages.get('modalWindows').then(function (modalWindows) {
            modalWindows.open('ivopetkov-users-preview-window', { 'provider': provider, 'id': id }, { showErrors: true });
        });
    };

    var openProviderLogin = function (provider) {
        openProviderScreen(provider, 'login');
    };

    var openProviderSignup = function (provider) {
        openProviderScreen(provider, 'signup');
    };

    var openProviderScreen = function (provider, id, data) {
        if (typeof data === 'undefined') {
            data = {};
        }
        clientPackages.get('modalWindows').then(function (modalWindows) {
            modalWindows.open('ivopetkov-users-screen-window', { 'provider': provider, 'id': id, 'data': data }, { showErrors: true });
        });
    };

    var closeCurrentWindow = function () { // Available options: expectOpen and expectShowLoading
        if (typeof options === "undefined") {
            options = {};
        }
        var modalOptions = {};
        modalOptions.expectOpen = typeof options.expectOpen !== "undefined" ? options.expectOpen : false;
        modalOptions.expectShowLoading = typeof options.expectShowLoading !== "undefined" ? options.expectShowLoading : false;
        return new Promise(function (resolve, reject) {
            clientPackages.get('modalWindows').then(function (modalWindows) {
                modalWindows.closeCurrent(modalOptions);
                resolve();
            });
        });
    };

    var closeAllWindows = function (options) { // Available options: expectOpen and expectShowLoading
        if (typeof options === "undefined") {
            options = {};
        }
        var modalOptions = {};
        modalOptions.expectOpen = typeof options.expectOpen !== "undefined" ? options.expectOpen : false;
        modalOptions.expectShowLoading = typeof options.expectShowLoading !== "undefined" ? options.expectShowLoading : false;
        return new Promise(function (resolve, reject) {
            clientPackages.get('modalWindows').then(function (modalWindows) {
                modalWindows.closeAll(modalOptions)
                    .then(resolve)
                    .catch(reject);
            });
        });
    };

    var showLoading = function () {
        return new Promise(function (resolve, reject) {
            clientPackages.get('modalWindows').then(function (modalWindows) {
                modalWindows.showLoading()
                    .then(resolve)
                    .catch(reject);
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

    var checkHash = function () {
        var hash = window.location.hash;
        var prefix = '#/-u/';
        if (hash.indexOf(prefix) === 0) {
            history.replaceState('', '', window.location.href.replace(hash, ''));
            var data = hash.replace(prefix, '');
            var dataParts = data.split('/');
            openProviderScreen(dataParts[0], '-hash-callback', { 'path': data.substring(dataParts[0].length + 1) });
        }
    };

    var getCookies = function () {
        return document.cookie.split(';').reduce(function (result, value) {
            var parts = value.split('=');
            try {
                result[parts[0].trim()] = decodeURIComponent(parts[1]);
            } catch (e) {
                result[parts[0].trim()] = parts[1];
            }
            return result;
        }, {});
    };

    document.addEventListener('readystatechange', () => { // interactive or complete
        checkHash();
    });
    if (document.readyState === 'complete') {
        checkHash();
    }

    return {
        'currentUser': {
            'exists': function () {
                return new Promise(function (resolve, reject) {
                    if (hasCurrentUser === null) {
                        clientPackages.get('serverRequests').then(function (serverRequests) {
                            serverRequests.send('ivopetkov-users-currentuser-exists')
                                .then(function (responseText) {
                                    try {
                                        var result = JSON.parse(responseText);
                                    } catch (e) {
                                        reject();
                                        return;
                                    }
                                    if (typeof result.status !== "undefined" && result.status === '1') {
                                        hasCurrentUser = result.exists === '1';
                                        onCurrentUserChange();
                                        resolve(hasCurrentUser);
                                    } else {
                                        reject();
                                    }
                                })
                                .catch(function () {
                                    reject();
                                });
                        });
                    } else {
                        resolve(hasCurrentUser);
                    }
                });
            },
            'openPreview': function () {
                clientPackages.get('modalWindows').then(function (modalWindows) {
                    modalWindows.open('ivopetkov-users-preview-window', { 'current': true }, { showErrors: true });
                });
            },
            'getProfileDetails': function (imageSizeOrDetailsList) { // { properties:[name, email], images:[100,200] }
                return new Promise(function (resolve, reject) {
                    clientPackages.get('serverRequests').then(function (serverRequests) {
                        serverRequests.send('ivopetkov-users-currentuser-details', { details: JSON.stringify(imageSizeOrDetailsList) })
                            .then(function (responseText) {
                                try {
                                    var result = JSON.parse(responseText);
                                } catch (e) {
                                    reject();
                                    return;
                                }
                                if (typeof result.status !== "undefined" && result.status === '1') {
                                    resolve(result.details);
                                } else {
                                    resolve(null);
                                }
                            }).catch(function () {
                                reject();
                            });
                    });
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
        'openPreview': openPreview,
        'openProviderScreen': openProviderScreen,
        'openProviderLogin': openProviderLogin,
        'openProviderSignup': openProviderSignup,
        '_closeCurrentWindow': closeCurrentWindow,
        '_closeAllWindows': closeAllWindows,
        '_showLoading': showLoading,
        '_updateBadge': updateBadge,
        '_dispatchProfileChange': dispatchProfileChange
    };

}());