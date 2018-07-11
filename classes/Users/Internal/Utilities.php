<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users\Internal;

use BearFramework\App;

class Utilities
{

    static function getCurrentUserPublicData(): ?array
    {
        $app = App::get();
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
    }

}
