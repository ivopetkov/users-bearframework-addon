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
$context = $app->contexts->get(__DIR__);

$context->classes
    ->add('IvoPetkov\BearFrameworkAddons\Users', 'classes/Users.php')
    ->add('IvoPetkov\BearFrameworkAddons\Users\*', 'classes/Users/*.php');

$context->assets
    ->addDir('assets');

$app->shortcuts
    ->add('users', function () {
        return new IvoPetkov\BearFrameworkAddons\Users();
    })
    ->add('currentUser', function () {
        return new IvoPetkov\BearFrameworkAddons\Users\CurrentUser();
    });

$app->localization
    ->addDictionary('en', function () use ($context) {
        return include $context->dir . '/locales/en.php';
    })
    ->addDictionary('bg', function () use ($context) {
        return include $context->dir . '/locales/bg.php';
    })
    ->addDictionary('ru', function () use ($context) {
        return include $context->dir . '/locales/ru.php';
    });

$app->assets
    ->addEventListener('beforePrepare', function (\BearFramework\App\Assets\BeforePrepareEventDetails $eventDetails) use ($app, $context) {
        $matchingDir = $context->dir . '/assets/u/';
        if (strpos($eventDetails->filename, $matchingDir) === 0) {
            $newFilename = null;
            $parts = explode('/', $eventDetails->filename);
            $providerID = $parts[sizeof($parts) - 2];
            $provider = $app->users->getProvider($providerID);
            if ($provider !== null) {
                $userID = $parts[sizeof($parts) - 1];
                $user = $app->users->getUser($providerID, $userID);
                if (strlen($user->image) > 0) {
                    $cacheKey = floor(time() / ((int) $provider->imageMaxAge === 0 ? 60 : (int) $provider->imageMaxAge));
                    if (strpos($user->image, 'https://') === 0 || strpos($user->image, 'http://') === 0) {
                        $download = false;
                        $tempDataPrefix = '.temp/users/images/' . md5(md5($providerID) . md5($userID) . md5($user->image));
                        $tempImageDataKey = null;
                        $tempImageExtensionDataKey =  $tempDataPrefix . '-' . $cacheKey;
                        $extension = $app->data->getValue($tempImageExtensionDataKey);
                        if ($extension !== null) {
                            if (array_search($extension, ['jpg', 'png', 'gif']) !== false) {
                                $tempImageDataKey = $tempDataPrefix . '.' . $extension;
                                if (!$app->data->exists($tempImageDataKey)) {
                                    $download = true;
                                }
                            }
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
                                    $tempImageDataKey = $tempDataPrefix . '.' . $extension;
                                    $app->data->setValue($tempImageDataKey, $response);
                                    $app->data->setValue($tempImageExtensionDataKey, $extension);
                                    $isValid = true;
                                }
                            }
                            curl_close($ch);
                            if (!$isValid) {
                                $app->data->setValue($tempImageExtensionDataKey, 'invalid');
                            }
                        }
                        if ($tempImageDataKey !== null && $app->data->exists($tempImageDataKey)) {
                            $newFilename = $app->data->getFilename($tempImageDataKey);
                        }
                    } else {
                        if (is_file($user->image)) {
                            $newFilename = $user->image;
                        }
                    }
                }
            }
            if ($newFilename === null) {
                $newFilename = $context->dir . '/assets/profile.png';
            }
            $eventDetails->filename = $newFilename;
        }
    });

$cookieKey = 'ip-users-cuk-' . md5($app->request->base);

$localCache = [];
$getCurrentCookieUserData = function () use ($app, $cookieKey, &$localCache): ?array {
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

$getCurrentUserCookieData = function () use ($app): ?array {
    if ($app->currentUser->exists()) {
        return [$app->currentUser->provider, $app->currentUser->id];
    }
    return null;
};

$app->serverRequests
    ->add('ivopetkov-users-login', function ($data) use ($app, $context) {
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
    ->add('ivopetkov-users-logout', function () use ($app) {
        $app->currentUser->logout();
        return json_encode(['status' => '1']);
    })
    ->add('ivopetkov-users-screen-window', function ($data) use ($app) {
        $provider = isset($data['provider']) ? $app->users->getProvider((string) $data['provider']) : null;
        $screenID = isset($data['id']) ? (string) $data['id'] : null;
        $template = '<html><head>
        <style>
            .ivopetkov-users-screen-form [data-form-element-type="textbox"] [data-form-element-component="input"],
            .ivopetkov-users-screen-form [data-form-element-type="password"] [data-form-element-component="input"],
            .ivopetkov-users-screen-form [data-form-element-type="textarea"] [data-form-element-component="textarea"]{
                width:250px;
                font-size:15px;
                padding:0 23px;
                line-height:50px;
                font-family:Arial,Helvetica,sans-serif;
                background-color:#eee;
                border-radius:2px;
                color:#000;
                box-sizing: border-box;
                display:block;
                margin-bottom: 21px;
                border:0;
            }
            .ivopetkov-users-screen-form [data-form-element-type="textarea"] [data-form-element-component="textarea"]{
                padding:12px 23px;
                line-height:28px;
            }
            .ivopetkov-users-screen-form [data-form-element-type="textarea"] [data-form-element-component="textarea"]{
                height:100px;
            }
            .ivopetkov-users-screen-form [data-form-element-type] [data-form-element-component="label"]{
                font-family:Arial,Helvetica,sans-serif;
                font-size:15px;
                color:#fff;
                padding-bottom: 9px;
                cursor: default;
                display:block;
            }
            .ivopetkov-users-screen-form [data-form-element-type="image"] [data-form-element-component="button"]{
                width:250px;
                height:250px;
                border-radius:2px;
                background-color:#fff;
                color:#000;
                font-family:Arial,Helvetica,sans-serif;
                font-size:15px;
                margin-bottom: 21px;
            }
            .ivopetkov-users-screen-form [data-form-element-type="submit-button"] [data-form-element-component="button"]{
                box-sizing: border-box;
                width:250px;
                font-family:Arial,Helvetica,sans-serif;
                background-color:#fff;
                font-size:15px;
                border-radius:2px;
                padding:0 23px;
                line-height:50px;
                color:#000;
                margin-top:25px;
                display:block;
                text-align:center;
            }
            .ivopetkov-users-screen-form [data-form-element-type="submit-button"] [data-form-element-component="button"][disabled]{
                background-color:#ddd;
            }
            .ivopetkov-users-screen-form [data-form-element-type="submit-button"] [data-form-element-component="button"]:not([disabled]):hover{
                background-color:#f5f5f5;
            }
            .ivopetkov-users-screen-form [data-form-element-type="submit-button"] [data-form-element-component="button"]:not([disabled]):active{
                background-color:#eeeeee;
            }
        </style>
    </head><body><div class="ivopetkov-users-screen-form"></div></body></html>';
        $dom = new HTML5DOMDocument();
        $dom->loadHTML($template);
        $html = $provider->getScreenContent($screenID);
        $formDOM = new HTML5DOMDocument();
        $formDOM->loadHTML($html, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
        // $onSubmitSuccess = 'clientPackages.get("users").then(function(users){users.openPreview("' . $app->currentUser->provider . '","' . $app->currentUser->id . '");users._updateBadge();});';
        // $formDOM->querySelector('form')->setAttribute('onsubmitsuccess', $onSubmitSuccess);
        $dom->querySelector('div')->appendChild($dom->createInsertTarget('form-content'));
        $dom->insertHTML($formDOM->saveHTML(), 'form-content');
        return json_encode(['html' => $dom->saveHTML()]);
    })
    ->add('ivopetkov-users-badge', function () use ($app, $context) {
        $html = $app->components->process('<component src="file:' . $context->dir . '/components/user-badge.php"/>');
        return json_encode(['html' => $html]);
    })
    ->add('ivopetkov-users-login-screen', function () use ($app, $context) {
        $html = $app->components->process('<component src="file:' . $context->dir . '/components/login-screen.php"/>');
        return json_encode(['html' => $html]);
    })
    ->add('ivopetkov-users-preview-window', function ($data) use ($app, $context) {
        $provider = isset($data['provider']) ? (string) $data['provider'] : '';
        $id = isset($data['id']) ? (string) $data['id'] : '';
        $html = $app->components->process('<component src="file:' . $context->dir . '/components/user-preview.php" provider="' . htmlentities($provider) . '" id="' . htmlentities($id) . '"/>');
        return json_encode(['html' => $html]);
    })
    ->add('ivopetkov-users-currentuser-exists', function () use ($app) {
        return json_encode(['status' => '1', 'exists' => $app->currentUser->exists() ? '1' : '0']);
    });

$app
    ->addEventListener('beforeSendResponse', function (\BearFramework\App\BeforeSendResponseEventDetails $details) use ($app, $getCurrentCookieUserData, $getCurrentUserCookieData, $cookieKey) {
        $response = $details->response;
        if ($app->currentUser->exists()) {
            $currentCookieUserData = $getCurrentCookieUserData();
            $currentUserCookieData = $getCurrentUserCookieData();
            if (strpos((string) $app->request->path, $app->assets->pathPrefix) !== 0) { // not an asset request
                if ($currentUserCookieData !== null && md5(serialize($currentCookieUserData)) !== md5(serialize($currentUserCookieData))) {
                    $generateCookieKeyValue = function () use ($app) {
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

$app->clientPackages
    ->add('users', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package) use ($context) {
        $package->addJSCode(file_get_contents(__DIR__ . '/assets/users.js'));
        //$package->addJSFile($context->assets->getURL('assets/users.min.js', ['cacheMaxAge' => 999999999, 'version' => 8, 'robotsNoIndex' => true]));
        $package->addJSFile($context->assets->getURL('assets/HTML5DOMDocument.min.js', ['cacheMaxAge' => 999999999, 'version' => 1, 'robotsNoIndex' => true]));
        $package->embedPackage('lightbox');
        $package->get = 'return ivoPetkov.bearFrameworkAddons.users;';
    });
