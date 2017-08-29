<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Options;

$app = App::get();
$context = $app->context->get(__FILE__);
$options = $app->addons->get('ivopetkov/users-bearframework-addon')->options;

$context->classes
        ->add('IvoPetkov\BearFrameworkAddons\CurrentUser', 'classes/CurrentUser.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users', 'classes/Users.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\Internal\Options', 'classes/Users/Internal/Options.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\GuestLoginProvider', 'classes/Users/GuestLoginProvider.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\ILoginProvider', 'classes/Users/ILoginProvider.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\LoginContext', 'classes/Users/LoginContext.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\LoginResponse', 'classes/Users/LoginResponse.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\User', 'classes/Users/User.php');

Options::set($options);

$context->assets
        ->addDir('assets');

$app->shortcuts
        ->add('users', function() {
            return new IvoPetkov\BearFrameworkAddons\Users();
        })
        ->add('currentUser', function() {
            return new IvoPetkov\BearFrameworkAddons\CurrentUser();
        });

$app->localization
        ->addDictionary('en', function() use ($context) {
            return include $context->dir . '/locales/en.php';
        })
        ->addDictionary('bg', function() use ($context) {
            return include $context->dir . '/locales/bg.php';
        })
        ->addDictionary('ru', function() use ($context) {
            return include $context->dir . '/locales/ru.php';
        });

$app->hooks
        ->add('assetPrepare', function($filename, $options, &$returnValue, &$preventDefault) use ($app, $context) {
            $matchingDir = $context->dir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR;
            if (strpos($filename, $matchingDir) === 0) {
                $preventDefault = true;
                $parts = explode(DIRECTORY_SEPARATOR, $filename);
                $providerID = $parts[sizeof($parts) - 2];
                $userID = $parts[sizeof($parts) - 1];
                $user = $app->users->getUser($providerID, $userID);
                $filename = null;
                if (strlen($user->image) > 0) {
                    if (strpos($user->image, 'https://') === 0 || strpos($user->image, 'http://') === 0) {
                        $download = false;
                        $tempFileDataKey = '.temp/users/images/' . md5($user->image); // here is stored information about the last download
                        $tempFileData = $app->data->getValue($tempFileDataKey);
                        $tempFilename = null;
                        if ($tempFileData !== null) {
                            $tempFileData = json_decode($tempFileData, true);
                            if ((int) $tempFileData['lastUpdateTime'] + 86400 < time()) { // is expired
                                $download = true;
                            } else {
                                if ($tempFileData['status'] !== 'empty') { // the url returns a valid image
                                    $tempFilename = $app->data->getFilename('.temp/users/images/' . md5($user->image) . '.' . $tempFileData['extension']); // file down not exists
                                    if (!is_file($tempFilename)) {
                                        $download = true;
                                    }
                                }
                            }
                            unset($tempFileData);
                        } else {
                            $download = true;
                        }
                        if ($download) {
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $user->image);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            $response = curl_exec($ch);
                            $isValid = false;
                            if ((int) curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
                                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                                $extension = null;
                                if ($contentType === 'image/jpeg') {
                                    $extension = 'jpg';
                                } elseif ($contentType === 'image/png') {
                                    $extension = 'png';
                                } elseif ($contentType === 'image/gif') {
                                    $extension = 'gif';
                                }
                                if ($extension !== null && strlen($response) > 0) {
                                    $app->data->set($app->data->make($tempFileDataKey, json_encode([
                                                'extension' => $extension,
                                                'lastUpdateTime' => time(),
                                                'status' => 'ok'
                                    ])));
                                    $tempFileKey = '.temp/users/images/' . md5($user->image) . '.' . $extension;
                                    $app->data->set($app->data->make($tempFileKey, $response));
                                    $tempFilename = $app->data->getFilename($tempFileKey);
                                    $isValid = true;
                                }
                            }
                            curl_close($ch);
                            if (!$isValid) {
                                $app->data->set($app->data->make($tempFileDataKey, json_encode([
                                            'extension' => '',
                                            'lastUpdateTime' => time(),
                                            'status' => 'empty'
                                ])));
                            }
                        }
                        if ($tempFilename !== null && is_file($tempFilename)) {
                            $filename = $tempFilename;
                        }
                    } else {
                        $tempFilename = realpath($user->image);
                        if ($tempFilename !== false) {
                            $filename = $tempFilename;
                        }
                    }
                }
                if ($filename === null) {
                    $filename = $context->dir . '/assets/profile.png';
                }
                $returnValue = $filename;
            }
        });

$cookieKey = 'ip-users-cuk-' . md5($app->request->base);

$localCache = [];
$getCurrentCookieUserData = function() use ($app, $cookieKey, &$localCache): ?array {
    $cookieValue = $app->request->cookies->getValue($cookieKey);
    if (strlen($cookieValue) > 0) {
        if (isset($localCache[$cookieValue])) {
            return $localCache[$cookieValue];
        }
        $cookieValueMD5 = md5($cookieValue);
        $result = $app->data->getValue('.temp/users/keys/' . substr(md5($cookieValueMD5), 0, 2) . '/' . substr(md5($cookieValueMD5), 2, 2) . '/' . substr(md5($cookieValueMD5), 4));
        if ($result !== null) {
            $value = json_decode($result, true);
            if (is_array($value)) {
                $localCache[$cookieValue] = $value;
                return $value;
            }
        }
    }
    return null;
};

$app->users
        ->addProvider('guest', 'IvoPetkov\BearFrameworkAddons\Users\GuestLoginProvider');

$currentCookieUserData = $getCurrentCookieUserData();
if ($currentCookieUserData !== null) {
    $app->currentUser->login($currentCookieUserData[0], $currentCookieUserData[1]);
}

$app->hooks
        ->add('initialized', function() use ($app, $context, $getCurrentCookieUserData, $cookieKey) {
            $providers = $app->users->getProviders();

            $providersPublicData = [];
            foreach ($providers as $providerData) {
                $provider = $app->users->getProvider($providerData['id']);
                $providersPublicData[] = [
                    'id' => $providerData['id'],
                    'hasLoginButton' => $provider->hasLoginButton(),
                    'loginButtonText' => $provider->getLoginButtonText()
                ];
            }

            $getCurrentUserCookieData = function() use ($app): ?array {
                if ($app->currentUser->exists()) {
                    return [$app->currentUser->provider, $app->currentUser->id];
                }
                return null;
            };

            $getCurrentUserPublicData = function() use ($app): ?array {
                if ($app->currentUser->exists()) {
                    $provider = $app->users->getProvider($app->currentUser->provider);
                    return [
                        'image' => (string) $app->currentUser->getImageUrl(500),
                        'name' => (string) $app->currentUser->name,
                        'description' => (string) $app->currentUser->description,
                        'url' => (string) $app->currentUser->url,
                        'hasLogoutButton' => (int) $provider->hasLogoutButton(),
                        'hasSettingsButton' => $provider instanceof \IvoPetkov\BearFrameworkAddons\Users\GuestLoginProvider,
                    ];
                }
                return null;
            };

            $app->serverRequests->add('ivopetkov-users-login', function($data) use ($app, $context, $getCurrentUserPublicData) {
                $providerID = isset($data['type']) ? $data['type'] : null;
                if (!$app->users->providerExists($providerID)) {
                    return;
                }
                $location = isset($data['location']) ? $data['location'] : null;

                $provider = $app->users->getProvider($providerID);
                $loginContext = new \IvoPetkov\BearFrameworkAddons\Users\LoginContext();
                $loginContext->locationUrl = $location;
                $loginResponse = $provider->login($loginContext);
                $result = [
                    'status' => '1'
                ];
                if (strlen($loginResponse->jsCode) > 0) {
                    $result['jsCode'] = $loginResponse->jsCode;
                }
                if (strlen($loginResponse->redirectUrl) > 0) {
                    $result['redirectUrl'] = $loginResponse->redirectUrl;
                } else {
                    $result['badgeHTML'] = $app->components->process('<component src="file:' . $context->dir . '/components/userBadge.php"/>');
                    $result['currentUser'] = $getCurrentUserPublicData();
                }
                return json_encode($result);
            });

            $app->serverRequests->add('ivopetkov-users-logout', function() use ($app) {
                $app->currentUser->logout();
                $result = ['status' => '1'];
                return json_encode($result);
            });

            $app->serverRequests->add('ivopetkov-guest-settings-form', function() use ($app, $context) {
                $html = $app->components->process('<component src="form" filename="' . $context->dir . '/components/guestSettingsForm.php"/>');
                $result = ['html' => $html];
                return json_encode($result);
            });

            $app->hooks->add('responseCreated', function($response) use ($app, $context, $getCurrentUserPublicData, $providersPublicData, $getCurrentCookieUserData, $getCurrentUserCookieData, $cookieKey) {
                if ($app->currentUser->exists()) {
                    $currentCookieUserData = $getCurrentCookieUserData();
                    $currentUserCookieData = $getCurrentUserCookieData();
                    if ($currentUserCookieData !== null && md5(serialize($currentCookieUserData)) !== md5(serialize($currentUserCookieData))) {
                        $generateCookieKeyValue = function() use ($app) {
                            for ($i = 0; $i < 100; $i++) {
                                $cookieValue = md5(uniqid() . $app->request->base . 'salt');
                                $cookieValueMD5 = md5($cookieValue);
                                $dataKey = '.temp/users/keys/' . substr(md5($cookieValueMD5), 0, 2) . '/' . substr(md5($cookieValueMD5), 2, 2) . '/' . substr(md5($cookieValueMD5), 4);
                                $result = $app->data->getValue($dataKey);
                                if ($result === null) {
                                    return $cookieValue;
                                }
                            }
                            throw new Exception('Too many retries');
                        };
                        $cookieKeyValue = $generateCookieKeyValue();
                        $cookieKeyValueMD5 = md5($cookieKeyValue);
                        $dataKey = '.temp/users/keys/' . substr(md5($cookieKeyValueMD5), 0, 2) . '/' . substr(md5($cookieKeyValueMD5), 2, 2) . '/' . substr(md5($cookieKeyValueMD5), 4);
                        $app->data->set($app->data->make($dataKey, json_encode($currentUserCookieData)));
                        $cookie = $response->cookies->make($cookieKey, $cookieKeyValue);
                        $cookie->httpOnly = true;
                        $response->cookies->set($cookie);
                    }
                } else {
                    if ($app->request->cookies->exists($cookieKey)) {
                        $cookie = $response->cookies->make($cookieKey, '');
                        $cookie->expire = 0;
                        $cookie->httpOnly = true;
                        $response->cookies->set($cookie);
                    }
                }
                if (!isset($response->enableIvoPetkovUsersUI)) {
                    return;
                }
                if ($response instanceof App\Response\HTML) {
                    $initializeData = [
                        'currentUser' => $getCurrentUserPublicData(),
                        'providers' => $providersPublicData,
                        'pleaseWaitText' => __('ivopetkov.users.pleaseWait'),
                        'logoutButtonText' => __('ivopetkov.users.logoutButton'),
                        'profileSettingsText' => __('ivopetkov.users.profileSettings')
                    ];
                    $html = '<html>'
                            . '<head>'
                            . '<style>'
                            . '.ivopetkov-users-badge{cursor:pointer;width:48px;height:48px;position:fixed;z-index:1000000;top:14px;right:14px;border-radius:2px;background-color:black;box-shadow:0 1px 2px 0px rgba(0,0,0,0.2);background-size:cover;background-position:center center;}'
                            . '.ivopetkov-users-window{text-align:center;height:100%;overflow:auto;padding:0 10px;display:flex;align-items:center;}'
                            . '.ivopetkov-users-login-option-button{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#000;background-color:#fff;border-radius:2px;margin-bottom:15px;padding:16px 14px;display:block;cursor:pointer;min-width:200px;text-align:center;}'
                            . '.ivopetkov-users-login-option-button:hover{background-color:#f5f5f5}'
                            . '.ivopetkov-users-login-option-button:active{background-color:#eeeeee}'
                            . '.ivopetkov-users-loading{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#fff;}'
                            . '.ivopetkov-users-account-image{border-radius:2px;background-color:#000;width:250px;height:250px;background-size:cover;background-repeat:no-repeat;background-position:center center;display:inline-block;}'
                            . '.ivopetkov-users-account-name{font-family:Arial,Helvetica,sans-serif;font-size:25px;color:#fff;margin-top:15px;max-width:350px;word-break:break-all;}'
                            . '.ivopetkov-users-account-description{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#fff;margin-top:15px;max-width:350px;word-break:break-all;}'
                            . '.ivopetkov-users-account-url{margin-top:15px;max-width:350px;word-break:break-all;}'
                            . '.ivopetkov-users-account-url a{font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#fff;}'
                            . '.ivopetkov-users-account-logout-button, .ivopetkov-guest-settings-button{cursor:pointer;font-family:Arial,Helvetica,sans-serif;font-size:15px;border-radius:2px;padding:13px 15px;color:#fff;margin-top:25px;display:inline-block;}'
                            . '.ivopetkov-users-account-logout-button:hover, .ivopetkov-guest-settings-button:hover{color:#000;background-color:#f5f5f5;};'
                            . '.ivopetkov-users-account-logout-button:active, .ivopetkov-guest-settings-button:active{color:#000;background-color:#eeeeee;};'
                            . '<style>'
                            . '</head>'
                            . '<body>'
                            . '<script src="' . $context->assets->getUrl('assets/users.min.js', ['cacheMaxAge' => 999999999, 'robotsNoIndex' => true]) . '" async/>'
                            . '<script>'
                            . 'var checkAndExecute=function(b,c){if(b())c();else{var a=function(){b()?(window.clearTimeout(a),c()):window.setTimeout(a,16)};window.setTimeout(a,16)}};'
                            . 'checkAndExecute(function(){return typeof ivoPetkov!=="undefined" && typeof ivoPetkov.bearFrameworkAddons!=="undefined" && typeof ivoPetkov.bearFrameworkAddons.users!=="undefined"},function(){ivoPetkov.bearFrameworkAddons.users.initialize(' . json_encode($initializeData) . ');});'
                            . '</script>';

                    if ($app->currentUser->exists()) {
                        $html .= '<component src="file:' . $context->dir . '/components/userBadge.php"/>';
                    }

                    $html .= '</body>'
                            . '</html>';
                    $dom = new IvoPetkov\HTML5DOMDocument();
                    $dom->loadHTML($response->content);
                    $htmlToInsert = [
                        ['source' => $app->components->process('<component src="js-lightbox"/>')], // must have process()
                        ['source' => $html]
                    ];
                    $dom->insertHTMLMulti($htmlToInsert);
                    $response->content = $dom->saveHTML();
                }
            });
        });
