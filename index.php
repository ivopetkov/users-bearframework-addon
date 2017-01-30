<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();
$context = $app->context->get(__FILE__);

$options = $app->addons->get('ivopetkov/users-bearframework-addon')->options;
define('_INTERNAL_IVOPETKOV_USERS_BEARFRAMEWORK_ADDON_LANGUAGE', isset($options['language']) ? strtolower($options['language']) : 'en');

$context->classes
        ->add('IvoPetkov\BearFrameworkAddons\CurrentUser', 'classes/CurrentUser.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users', 'classes/Users.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\AnonymousLoginProvider', 'classes/Users/AnonymousLoginProvider.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\ILoginProvider', 'classes/Users/ILoginProvider.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\LoginContext', 'classes/Users/LoginContext.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\LoginResponse', 'classes/Users/LoginResponse.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\User', 'classes/Users/User.php');

$context->assets
        ->addDir('assets');

$app->shortcuts
        ->add('users', function() {
            return new IvoPetkov\BearFrameworkAddons\Users();
        })
        ->add('currentUser', function() {
            return new IvoPetkov\BearFrameworkAddons\CurrentUser();
        });

$app->hooks
        ->add('assetPrepare', function($data) use ($app, $context) {
            $matchingDir = $context->dir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR;
            if (strpos($data->filename, $matchingDir) === 0) {
                $parts = explode(DIRECTORY_SEPARATOR, $data->filename);
                $providerID = $parts[sizeof($parts) - 2];
                $userID = $parts[sizeof($parts) - 1];
                if (!$app->users->providerExists($providerID)) {
                    return;
                }
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
                            if ((int) $tempFileData['lastUpdateTime'] + 3600 < time()) { // is expired
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
                    $filename = $context->dir . '/assets/anonymous.png';
                }
                $data->filename = $filename;
            }
        });

$app->hooks
        ->add('initialized', function() use ($app, $context) {

            $app->users->addProvider('anonymous', 'IvoPetkov\BearFrameworkAddons\Users\AnonymousLoginProvider');

            $providers = $app->users->getProviders();

            $providersPublicData = [];
            foreach ($providers as $providerData) {
                $provider = $app->users->getProvider($providerData['id']);
                $providersPublicData[] = [
                    'id' => $providerData['id'],
                    'buttonText' => $provider->getLoginButtonText()
                ];
            }

            $getCurrentCookieUserData = function() use ($app): ?array {
                $cookieKey = $app->request->cookies->getValue('ip-users-cuk');
                if (strlen($cookieKey) > 0) {
                    $result = $app->data->getValue('.temp/users/keys/' . md5($cookieKey));
                    if ($result !== null) {
                        $value = json_decode($result, true);
                        if (is_array($value)) {
                            return $value;
                        }
                    }
                }
                return null;
            };

            $getCurrentUserCookieData = function() use ($app): ?array {
                if ($app->currentUser->exists()) {
                    $data = [];
                    $data['provider'] = $app->currentUser->provider;
                    $data['id'] = $app->currentUser->id;
                    $data['name'] = $app->currentUser->name;
                    $data['url'] = $app->currentUser->url;
                    $data['image'] = $app->currentUser->image;
                    return $data;
                }
                return null;
            };

            $getCurrentUserPublicData = function() use ($app): ?array {
                if ($app->currentUser->exists()) {
                    $provider = $app->users->getProvider($app->currentUser->provider);
                    return [
                        'imageLarge' => (string) $app->currentUser->getImageUrl(500),
                        'name' => (string) $app->currentUser->name,
                        'descriptionHTML' => (string) $provider->getDescriptionHTML(),
                        'hasLogout' => (int) $provider->hasLogout(),
                    ];
                }
                return null;
            };

            $currentCookieUserData = $getCurrentCookieUserData();
            if ($currentCookieUserData !== null) {
                $app->currentUser->provider = $currentCookieUserData['provider'];
                $app->currentUser->id = $currentCookieUserData['id'];
                $app->currentUser->name = $currentCookieUserData['name'];
                $app->currentUser->url = $currentCookieUserData['url'];
                $app->currentUser->image = $currentCookieUserData['image'];
            }

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
                if (strlen($loginResponse->redirectUrl) > 0) {
                    $result['redirectUrl'] = $loginResponse->redirectUrl;
                } else {
                    $result['badgeHTML'] = $app->components->process('<component src="file:' . $context->dir . '/components/userBadge.php"/>');
                    $result['currentUser'] = $getCurrentUserPublicData();
                }
                return json_encode($result);
            });

            $app->serverRequests->add('ivopetkov-users-logout', function() use ($app) {
                $app->currentUser->clear();
                $result = ['status' => '1'];
                return json_encode($result);
            });

            $app->hooks->add('responseCreated', function($response) use ($app, $context, $getCurrentUserPublicData, $providersPublicData, $getCurrentCookieUserData, $getCurrentUserCookieData) {
                if ($app->currentUser->exists()) {
                    $currentCookieUserData = $getCurrentCookieUserData();
                    $currentUserCookieData = $getCurrentUserCookieData();
                    if ($currentUserCookieData !== null && md5(serialize($currentCookieUserData)) !== md5(serialize($currentUserCookieData))) {
                        $generateCookieKey = function() use ($app) {
                            for ($i = 0; $i < 100; $i++) {
                                $cookieKey = md5(uniqid() . $app->request->base . 'salt');
                                $result = $app->data->getValue('.temp/users/keys/' . md5($cookieKey));
                                if ($result === null) {
                                    return $cookieKey;
                                }
                            }
                            throw new Exception('Too many retries');
                        };
                        $cookieKey = $generateCookieKey();
                        $app->data->set($app->data->make('.temp/users/keys/' . md5($cookieKey), json_encode($currentUserCookieData)));
                        $response->cookies->set($response->cookies->make('ip-users-cuk', $cookieKey));
                    }
                } else {
                    if ($app->request->cookies->exists('ip-users-cuk')) {
                        $cookie = $response->cookies->make('ip-users-cuk', '');
                        $cookie->expire = 0;
                        $response->cookies->set($cookie);
                    }
                }
                if (!isset($response->enableIvoPetkovUsersUI)) {
                    return;
                }
                if ($response instanceof App\Response\HTML) {
                    if (_INTERNAL_IVOPETKOV_USERS_BEARFRAMEWORK_ADDON_LANGUAGE === 'bg') {
                        $logoutButtonText = 'Изход';
                        $pleaseWaitText = 'Моля, изчакайте ...';
                    } else {
                        $logoutButtonText = 'Log out';
                        $pleaseWaitText = 'Please wait ...';
                    }
                    $initializeData = [
                        'currentUser' => $getCurrentUserPublicData(),
                        'providers' => $providersPublicData,
                        'pleaseWaitText' => $pleaseWaitText,
                        'logoutButtonText' => $logoutButtonText
                    ];
                    $html = '<html>'
                            . '<head>'
                            . '<style>'
                            . '.ivopetkov-users-badge{cursor:pointer;width:48px;height:48px;position:fixed;top:14px;right:14px;border-radius:2px;background-color:black;box-shadow:0 1px 2px 0px rgba(0,0,0,0.2);background-size:cover;background-position:center center;}'
                            . '.ivopetkov-users-window{text-align:center;}'
                            . '.ivopetkov-users-login-option-button{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#000;background-color:#fff;border-radius:2px;margin-bottom:15px;padding:16px 14px;display:block;cursor:pointer;min-width:200px;text-align:center;}'
                            . '.ivopetkov-users-login-option-button:hover{background-color:#f5f5f5}'
                            . '.ivopetkov-users-login-option-button:active{background-color:#eeeeee}'
                            . '.ivopetkov-users-loading{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#fff;}'
                            //. '.ivopetkov-users-account-image{}'
                            . '.ivopetkov-users-account-name{font-family:Arial,Helvetica,sans-serif;font-size:25px;color:#fff;margin-top:15px;}'
                            . '.ivopetkov-users-account-description{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#fff;margin-top:15px;}'
                            . '.ivopetkov-users-account-logout-button{cursor:pointer;font-family:Arial,Helvetica,sans-serif;font-size:15px;border-radius:2px;padding:13px 15px;color:#fff;margin-top:25px;display:inline-block;}'
                            . '.ivopetkov-users-account-logout-button:hover{background-color:rgba(255,255,255,0.1);};'
                            . '.ivopetkov-users-account-logout-button:active{background-color:rgba(255,255,255,0.2);};'
                            . '<style>'
                            . '</head>'
                            . '<body>'
                            . '<component src="js-lightbox"/>'
                            . '<script src="' . $context->assets->getUrl('assets/users.js', ['cacheMaxAge' => 9999999, 'robotsNoIndex' => true]) . '"/>'
                            . '<script>ivoPetkov.bearFrameworkAddons.users.initialize(' . json_encode($initializeData) . ');</script>';

                    if ($app->currentUser->exists()) {
                        $html .= '<component src="file:' . $context->dir . '/components/userBadge.php"/>';
                    }

                    $html .= '</body>'
                            . '</html>';
                    $dom = new IvoPetkov\HTML5DOMDocument();
                    $dom->loadHTML($response->content);
                    $dom->insertHTML($app->components->process($html));
                    $response->content = $dom->saveHTML();
                }
            });
        });
