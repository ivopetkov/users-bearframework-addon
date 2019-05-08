<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;
use IvoPetkov\HTML5DOMDocument;

$app = App::get();
$context = $app->contexts->get(__FILE__);

$context->classes
        ->add('IvoPetkov\BearFrameworkAddons\Users', 'classes/Users.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\*', 'classes/Users/*.php');

$context->assets
        ->addDir('assets');

$app->shortcuts
        ->add('users', function() {
            return new IvoPetkov\BearFrameworkAddons\Users();
        })
        ->add('currentUser', function() {
            return new IvoPetkov\BearFrameworkAddons\Users\CurrentUser();
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

$app->assets
        ->addEventListener('beforePrepare', function(\BearFramework\App\Assets\BeforePrepareEventDetails $eventDetails) use ($app, $context) {
            $matchingDir = $context->dir . '/assets/u/';
            if (strpos($eventDetails->filename, $matchingDir) === 0) {
                $parts = explode('/', $eventDetails->filename);
                $providerID = $parts[sizeof($parts) - 2];
                $userID = $parts[sizeof($parts) - 1];
                $user = $app->users->getUser($providerID, $userID);
                $newFilename = null;
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
                            $newFilename = $tempFilename;
                        }
                    } else {
                        if (is_file($user->image)) {
                            $newFilename = $user->image;
                        }
                    }
                }
                if ($newFilename === null) {
                    $newFilename = $context->dir . '/assets/profile.png';
                }
                $eventDetails->filename = $newFilename;
            }
        });

$app->users
        ->addProvider('guest', 'IvoPetkov\BearFrameworkAddons\Users\GuestLoginProvider');

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

$currentCookieUserData = $getCurrentCookieUserData();
if ($currentCookieUserData !== null) {
    $app->currentUser->login($currentCookieUserData[0], $currentCookieUserData[1]);
}

$getCurrentUserCookieData = function() use ($app): ?array {
    if ($app->currentUser->exists()) {
        return [$app->currentUser->provider, $app->currentUser->id];
    }
    return null;
};

$app->serverRequests
        ->add('ivopetkov-users-login', function($data) use ($app, $context) {
            $providerID = isset($data['provider']) ? $data['provider'] : null;
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
                $result['badgeHTML'] = $app->components->process('<component src="file:' . $context->dir . '/components/user-badge.php"/>');
            }
            return json_encode($result);
        })
        ->add('ivopetkov-users-logout', function() use ($app) {
            $app->currentUser->logout();
            return json_encode(['status' => '1']);
        })
        ->add('ivopetkov-users-settings-window', function() use ($app) {
            if ($app->currentUser->exists()) {
                $provider = $app->users->getProvider($app->currentUser->provider);
                if ($provider !== null) {
                    if ($provider->hasSettings) {
                        $template = '<html><head>
        <style>
            .ivopetkov-users-settings-form .ivopetkov-form-elements-textbox-element-input, .ivopetkov-users-settings-form .ivopetkov-form-elements-textarea-element-textarea{
                width:250px;
                font-size:15px;
                padding:13px 15px;
                font-family:Arial,Helvetica,sans-serif;
                background-color:#eee;
                border-radius:2px;
                color:#000;
                box-sizing: border-box;
                display:block;
                margin-bottom: 21px;
                border:0;
            }
            .ivopetkov-users-settings-form .ivopetkov-form-elements-textarea-element-textarea{
                height:100px;
            }
            .ivopetkov-users-settings-form .ivopetkov-form-elements-element-label{
                font-family:Arial,Helvetica,sans-serif;
                font-size:15px;
                color:#fff;
                padding-bottom: 9px;
                cursor: default;
                display:block;
            }
            .ivopetkov-users-settings-form .ivopetkov-form-elements-image-element-button{
                width:250px;
                height:250px;
                border-radius:2px;
                background-color:#fff;
                color:#000;
                font-family:Arial,Helvetica,sans-serif;
                font-size:15px;
                margin-bottom: 21px;
            }
            .ivopetkov-users-settings-form .ivopetkov-form-elements-submit-button-element-button{
                box-sizing: border-box;
                width:250px;
                font-family:Arial,Helvetica,sans-serif;
                background-color:#fff;
                font-size:15px;
                border-radius:2px;
                padding:13px 15px;
                color:#000;
                margin-top:25px;
                display:block;
                text-align:center;
            }
            .ivopetkov-users-settings-form .ivopetkov-form-elements-submit-button-element-button[disabled]{
                background-color:#ddd;
            }
            .ivopetkov-users-settings-form .ivopetkov-form-elements-submit-button-element-button:not([disabled]):hover{
                background-color:#f5f5f5;
            }
            .ivopetkov-users-settings-form .ivopetkov-form-elements-submit-button-element-button:not([disabled]):active{
                background-color:#eeeeee;
            }
        </style>
    </head><body><div class="ivopetkov-users-settings-form"></div></body></html>';
                        $dom = new HTML5DOMDocument();
                        $dom->loadHTML($template);
                        $html = $provider->getSettingsForm();
                        $formDom = new HTML5DOMDocument();
                        $formDom->loadHTML($html, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
                        $onSubmitSuccess = 'clientShortcuts.get("users").then(function(users){users.openPreview("' . $app->currentUser->provider . '","' . $app->currentUser->id . '");users._updateBadge();});';
                        $formDom->querySelector('form')->setAttribute('onsubmitsuccess', $onSubmitSuccess);
                        $dom->querySelector('div')->appendChild($dom->createInsertTarget('form-content'));
                        $dom->insertHTML($formDom->saveHTML(), 'form-content');
                        return json_encode(['html' => $dom->saveHTML()]);
                    }
                }
            }
            return '0';
        })
        ->add('ivopetkov-users-badge', function() use ($app, $context) {
            $html = $app->components->process('<component src="file:' . $context->dir . '/components/user-badge.php"/>');
            return json_encode(['html' => $html]);
        })
        ->add('ivopetkov-users-login-screen', function() use ($app, $context) {
            $html = $app->components->process('<component src="file:' . $context->dir . '/components/login-screen.php"/>');
            return json_encode(['html' => $html]);
        })
        ->add('ivopetkov-users-preview-window', function($data) use ($app, $context) {
            $provider = isset($data['provider']) ? (string) $data['provider'] : '';
            $id = isset($data['id']) ? (string) $data['id'] : '';
            $html = $app->components->process('<component src="file:' . $context->dir . '/components/user-preview.php" provider="' . htmlentities($provider) . '" id="' . htmlentities($id) . '"/>');
            return json_encode(['html' => $html]);
        });

$app
        ->addEventListener('beforeSendResponse', function(\BearFramework\App\BeforeSendResponseEventDetails $details) use ($app, $getCurrentCookieUserData, $getCurrentUserCookieData, $cookieKey) {
            $response = $details->response;
            if ($app->currentUser->exists()) {
                $currentCookieUserData = $getCurrentCookieUserData();
                $currentUserCookieData = $getCurrentUserCookieData();
                if (strpos((string) $app->request->path, $app->assets->pathPrefix) !== 0) {
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
                }
            } else {
                if ($app->request->cookies->exists($cookieKey)) {
                    $cookie = $response->cookies->make($cookieKey, '');
                    $cookie->expire = 0;
                    $cookie->httpOnly = true;
                    $response->cookies->set($cookie);
                }
            }
        });

$app->clientShortcuts
        ->add('users', function(IvoPetkov\BearFrameworkAddons\ClientShortcut $shortcut) use ($app, $context) {
            $shortcut->requirements[] = [
                'type' => 'file',
                'url' => $context->assets->getURL('assets/users.min.js', ['cacheMaxAge' => 999999999, 'version' => 6, 'robotsNoIndex' => true]),
                'mimeType' => 'text/javascript'
            ];
            $shortcut->requirements[] = [
                'type' => 'file',
                'url' => $context->assets->getURL('assets/HTML5DOMDocument.min.js', ['cacheMaxAge' => 999999999, 'version' => 1, 'robotsNoIndex' => true]),
                'mimeType' => 'text/javascript'
            ];
            $shortcut->init = 'ivoPetkov.bearFrameworkAddons.users.initialize(' . (int) $app->currentUser->exists() . ');';
            $shortcut->get = 'return ivoPetkov.bearFrameworkAddons.users;';
        });
