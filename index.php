<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;

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

$cookieKeySuffix = base_convert(substr(md5((string)$app->request->base), 0, 10), 16, 36);
$cookie1Key = 'ipu1-' . $cookieKeySuffix; // httponly
$cookie2Key = 'ipu2-' . $cookieKeySuffix;

$sessionKeyPart1Length = (int)floor(Utilities::$sessionKeyLength / 2);
$sessionKeyPart2Length = (int)(Utilities::$sessionKeyLength - $sessionKeyPart1Length);

$combineSessionKey = function (string $part1, string $part2) use ($sessionKeyPart1Length, $sessionKeyPart2Length): ?string {
    if (strlen($part1) === $sessionKeyPart1Length && strlen($part2) === $sessionKeyPart2Length) {
        return $part1 . $part2;
    }
    return null;
};

$splitSessionKey = function (string $sessionKey) use ($sessionKeyPart1Length, $sessionKeyPart2Length): array {
    return [
        substr($sessionKey, 0, $sessionKeyPart1Length),
        substr($sessionKey, $sessionKeyPart1Length)
    ];
};

$sessionDataCache = [];
$getCookieUserData = function () use ($app, &$sessionDataCache, $cookie1Key, $cookie2Key, $combineSessionKey): ?array {
    $cookie1Value = $app->request->cookies->getValue($cookie1Key);
    $cookie2Value = $app->request->cookies->getValue($cookie2Key);
    if ($cookie1Value !== null && strlen($cookie1Value) > 0 && $cookie2Value !== null && strlen($cookie2Value) > 0) {
        $sessionKey = $combineSessionKey($cookie1Value, $cookie2Value);
        if ($sessionKey !== null) {
            if (isset($sessionDataCache[$sessionKey])) {
                return $sessionDataCache[$sessionKey];
            }
            $sessionData = Utilities::getSessionData($sessionKey);
            if (is_array($sessionData)) {
                $sessionDataCache[$sessionKey] = $sessionData;
                return $sessionData;
            }
        }
    }
    return null;
};

$cookieUserData = $getCookieUserData();
if ($cookieUserData !== null) {
    $app->currentUser->set($cookieUserData[0], $cookieUserData[1]);
}

$getCurrentUserCookieData = function () use ($app): ?array {
    if ($app->currentUser->exists()) {
        return [$app->currentUser->provider, $app->currentUser->id];
    }
    return null;
};

$app->routes
    ->add(Utilities::$providerRoutePrefix . '*', function (App\Request $request) {
        $path = (string)$request->path;
        $path = substr($path, strlen(Utilities::$providerRoutePrefix));
        return Utilities::handleCallbackRequest($path);
    });

$app->serverRequests
    ->add('ivopetkov-users-login', function ($data) use ($app, $context) {
        $providerID = isset($data['provider']) && is_string($data['provider']) ? $data['provider'] : null;
        if ($providerID === null || !$app->users->providerExists($providerID)) {
            return;
        }
        $location = isset($data['location']) && is_string($data['location']) ? $data['location'] : null;

        $provider = $app->users->getProvider($providerID);
        $loginContext = new \IvoPetkov\BearFrameworkAddons\Users\LoginContext();
        $loginContext->providerID = $providerID;
        $loginContext->locationURL = $location;
        $loginResponse = $provider->login($loginContext);
        $result = [
            'status' => '1'
        ];
        if ($loginResponse->jsCode !== null && strlen($loginResponse->jsCode) > 0) {
            $result['jsCode'] = $loginResponse->jsCode;
        }
        if ($loginResponse->redirectURL !== null && strlen($loginResponse->redirectURL) > 0) {
            $result['redirectURL'] = $loginResponse->redirectURL;
        }
        if ($app->currentUser->exists()) {
            $result['exists'] = true;
            $result['badgeHTML'] = Utilities::getBadgeHTML();
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
    })
    ->add('ivopetkov-users-currentuser-details', function ($data) use ($app) {
        if ($app->currentUser->exists()) {
            $details = isset($data['details']) ? json_decode($data['details'], true) : null;
            if (is_array($details)) {
                $result = [];
                if (isset($details['properties']) && is_array($details['properties'])) {
                    foreach ($details['properties'] as $property) {
                        if (is_string($property)) {
                            $result[$property] = $app->currentUser->getProfileData($property);
                        }
                    }
                }
                if (isset($details['images']) && is_array($details['images'])) {
                    foreach ($details['images'] as $imageSize) {
                        if (is_numeric($imageSize)) {
                            $imageSize = (int)$imageSize;
                            if (!isset($result['images'])) {
                                $result['images'] = [];
                            }
                            $result['images'][$imageSize] = $app->currentUser->getImageURL($imageSize);
                        }
                    }
                }
            } else {
                $result['name'] = $app->currentUser->name;
                $result['image'] = $app->currentUser->getImageURL($details !== null ? (int)$details : 100);
            }
        } else {
            $result = null;
        }
        return json_encode(['status' => '1', 'details' => $result]);
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
        if (isset($data['current'])) {
            if (!$app->currentUser->exists()) {
                return;
            }
            $providerID = $app->currentUser->provider;
            $id = $app->currentUser->id;
        } else {
            $providerID = isset($data['provider']) && is_string($data['provider']) ? $data['provider'] : '';
            $id = isset($data['id']) && is_string($data['id']) ? $data['id'] : '';
        }
        $content = '<component src="file:' . $context->dir . '/components/user-preview.php" provider="' . htmlentities($providerID) . '" id="' . htmlentities($id) . '"/>';
        $content = $app->components->process($content);
        $content = $app->clientPackages->process($content);
        return [
            'content' => $content,
            'width' => '300px'
        ];
    })
    ->add('ivopetkov-users-settings-window', function () use ($app, $context) {
        if (!$app->currentUser->exists()) {
            return;
        }
        $content = '<component src="file:' . $context->dir . '/components/user-settings.php" />';
        $content = $app->components->process($content);
        $content = $app->clientPackages->process($content);
        return [
            'title' => __('ivopetkov.users.settings'),
            'content' => $content,
            'width' => '300px'
        ];
    })
    ->add('ivopetkov-users-screen-window', function ($data) use ($app, $context) {
        $provider = isset($data['provider']) && is_string($data['provider']) ? $app->users->getProvider($data['provider']) : null;
        $screenID = isset($data['id']) && is_string($data['id']) ? $data['id'] : null;
        $title = '';
        $width = '400px';
        $content = '';
        if ($provider !== null && $screenID !== null) {
            if ($screenID === 'user-profile-settings') {
                $content = $app->components->process('<component src="form" filename="' . $context->dir . '/components/user-profile-settings-form.php"/>');
                $title = __('ivopetkov.users.profileSettingsButton');
                $width = '300px';
            } else {
                $content = $provider->getScreenContent($screenID, isset($data['data']) && is_array($data['data']) ? $data['data'] : []);
            }
            if (is_array($content)) {
                $title = $content['title'];
                $width = $content['width'];
                $content = $content['content'];
            }
            $content = $app->components->process($content);
            $content = $app->clientPackages->process($content);
        }
        return [
            'title' => $title,
            'content' => $content,
            'width' => $width
        ];
    });

$app
    ->addEventListener('beforeSendResponse', function (\BearFramework\App\BeforeSendResponseEventDetails $details) use ($app, $getCookieUserData, $getCurrentUserCookieData, $cookie1Key, $cookie2Key, $splitSessionKey) {
        $response = $details->response;
        if ($app->currentUser->exists()) {
            if (strpos((string) $app->request->path, $app->assets->pathPrefix) !== 0) { // not an asset request
                $currentCookieUserData = $getCookieUserData();
                $currentUserCookieData = $getCurrentUserCookieData();
                if ($currentUserCookieData !== null) {
                    if (md5(serialize($currentCookieUserData)) !== md5(serialize($currentUserCookieData))) {
                        $sessionKey = Utilities::generateSessionKey();
                        Utilities::setSessionData($sessionKey, $currentUserCookieData);

                        $sessionKeyParts = $splitSessionKey($sessionKey);

                        $cookie = $response->cookies->make($cookie1Key, $sessionKeyParts[0]);
                        $cookie->httpOnly = true;
                        $cookie->secure = true;
                        $cookie->path = '/';
                        if (Utilities::$currentUserCookieAction === 'login-remember') {
                            $cookie->expire = time() + 86400 * 90;
                        }
                        $response->cookies->set($cookie);

                        $cookie = $response->cookies->make($cookie2Key, $sessionKeyParts[1]);
                        $cookie->secure = true;
                        $cookie->path = '/';
                        if (Utilities::$currentUserCookieAction === 'login-remember') {
                            $cookie->expire = time() + 86400 * 90;
                        }
                        $response->cookies->set($cookie);
                    }
                }
            }
        } else {
            if ($app->request->cookies->exists($cookie1Key)) {
                $cookie = $response->cookies->make($cookie1Key, '');
                $cookie->httpOnly = true;
                $cookie->secure = true;
                $cookie->path = '/';
                $cookie->expire = 0;
                $response->cookies->set($cookie);
            }
            if ($app->request->cookies->exists($cookie2Key)) {
                $cookie = $response->cookies->make($cookie2Key, '');
                $cookie->secure = true;
                $cookie->path = '/';
                $cookie->expire = 0;
                $response->cookies->set($cookie);
            }
        }
    });

$app->clientPackages
    ->add('users', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package) use ($context) {
        //$package->addJSCode(file_get_contents(__DIR__ . '/assets/users.js'));
        $package->addJSFile($context->assets->getURL('assets/users.min.js', ['cacheMaxAge' => 999999999, 'version' => 23, 'robotsNoIndex' => true]));
        $package->embedPackage('modalWindows');
        $package->embedPackage('html5DOMDocument');
        $package->get = 'return ivoPetkov.bearFrameworkAddons.users;';
    });
