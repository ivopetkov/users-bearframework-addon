<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;

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
                $userImage = (string)$user->image;
                if (strlen($userImage) > 0) {
                    $cacheKey = floor(time() / ((int) $provider->imageMaxAge === 0 ? 60 : (int) $provider->imageMaxAge));
                    if (strpos($userImage, 'https://') === 0 || strpos($userImage, 'http://') === 0) {
                        $download = false;
                        $tempDataPrefix = '.temp/users/images/' . md5(md5($providerID) . md5($userID) . md5($userImage));
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
                            curl_setopt($ch, CURLOPT_URL, $userImage);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            $response = (string)curl_exec($ch);
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
                        if (is_file($userImage)) {
                            $newFilename = $userImage;
                        }
                    }
                }
            }
            if ($newFilename === null) {
                $newFilename = $context->dir . '/assets/profile2.png';
            }
            $eventDetails->filename = $newFilename;
        }
    });

$cookieKey = 'ip-users-cuk-' . md5((string)$app->request->base);

$localCache = [];
$getCurrentCookieUserData = function () use ($app, $cookieKey, &$localCache): ?array {
    $cookieValue = $app->request->cookies->getValue($cookieKey);
    if ($cookieValue !== null && strlen($cookieValue) > 0) {
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
        if ($loginResponse->jsCode !== null && strlen($loginResponse->jsCode) > 0) {
            $result['jsCode'] = $loginResponse->jsCode;
        }
        if ($loginResponse->redirectUrl !== null && strlen($loginResponse->redirectUrl) > 0) {
            $result['redirectUrl'] = $loginResponse->redirectUrl;
        } else {
            $result['badgeHTML'] = $app->components->process('<component src="file:' . $context->dir . '/components/user-badge.php"/>');
        }
        return json_encode($result);
    })
    ->add('ivopetkov-users-logout', function () use ($app) {
        if ($app->currentUser->exists()) {
            $app->users->dispatchLogoutEvent($app->currentUser->provider, $app->currentUser->id);
            $app->currentUser->logout();
        }
        return json_encode(['status' => '1']);
    })
    ->add('ivopetkov-users-currentuser-exists', function () use ($app) {
        return json_encode(['status' => '1', 'exists' => $app->currentUser->exists() ? '1' : '0']);
    });

$app->modalWindows
    ->add('ivopetkov-users-login-screen', function () use ($app, $context) {
        $content = '<component src="file:' . $context->dir . '/components/login-screen.php"/>';
        $content = $app->components->process($content);
        $content = $app->clientPackages->process($content);
        return [
            'title' => __('ivopetkov.users.login'),
            'content' => $content,
            'width' => '400px'
        ];
    })
    ->add('ivopetkov-users-preview-window', function ($data) use ($app, $context) {
        $provider = isset($data['provider']) ? (string) $data['provider'] : '';
        $id = isset($data['id']) ? (string) $data['id'] : '';
        $content = '<component src="file:' . $context->dir . '/components/user-preview.php" provider="' . htmlentities($provider) . '" id="' . htmlentities($id) . '"/>';
        $content = $app->components->process($content);
        $content = $app->clientPackages->process($content);
        return [
            'content' => $content,
            'width' => '300px'
        ];
    })
    ->add('ivopetkov-users-screen-window', function ($data) use ($app) {
        $provider = isset($data['provider']) ? $app->users->getProvider((string) $data['provider']) : null;
        $screenID = isset($data['id']) ? (string) $data['id'] : null;
        $content = $provider->getScreenContent($screenID);
        $title = '';
        $width = '400px';
        if (is_array($content)) {
            $title = $content['title'];
            $width = $content['width'];
            $content = $content['content'];
        }
        $content = $app->components->process($content);
        $content = $app->clientPackages->process($content);
        return [
            'title' => $title,
            'content' => $content,
            'width' => $width
        ];
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
        //$package->addJSCode(file_get_contents(__DIR__ . '/assets/users.js'));
        $package->addJSFile($context->assets->getURL('assets/users.min.js', ['cacheMaxAge' => 999999999, 'version' => 10, 'robotsNoIndex' => true]));
        $package->embedPackage('modalWindows');
        $package->embedPackage('html5DOMDocument');
        $package->get = 'return ivoPetkov.bearFrameworkAddons.users;';
    });
