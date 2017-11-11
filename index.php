<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Options;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;

$app = App::get();
$context = $app->context->get(__FILE__);
$options = $app->addons->get('ivopetkov/users-bearframework-addon')->options;

$context->classes
        ->add('IvoPetkov\BearFrameworkAddons\CurrentUser', 'classes/CurrentUser.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users', 'classes/Users.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\Internal\Options', 'classes/Users/Internal/Options.php')
        ->add('IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities', 'classes/Users/Internal/Utilities.php')
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
        ->add('assetPrepare', function(&$filename, $options) use ($app, $context) {
            $matchingDir = $context->dir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR;
            if (strpos($filename, $matchingDir) === 0) {
                $parts = explode(DIRECTORY_SEPARATOR, $filename);
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
                        $tempFilename = realpath($user->image);
                        if ($tempFilename !== false) {
                            $newFilename = $tempFilename;
                        }
                    }
                }
                if ($newFilename === null) {
                    $newFilename = $context->dir . '/assets/profile.png';
                }
                $filename = $newFilename;
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
                $result['currentUser'] = Utilities::getCurrentUserPublicData();
            }
            return json_encode($result);
        })
        ->add('ivopetkov-users-logout', function() use ($app) {
            $app->currentUser->logout();
            $result = ['status' => '1'];
            return json_encode($result);
        })
        ->add('ivopetkov-guest-settings-form', function() use ($app, $context) {
            $html = $app->components->process('<component src="form" filename="' . $context->dir . '/components/guestSettingsForm.php"/>');
            $result = ['html' => $html];
            return json_encode($result);
        });

$app->hooks->add('responseCreated', function($response) use ($app, $getCurrentCookieUserData, $getCurrentUserCookieData, $cookieKey) {
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
});
