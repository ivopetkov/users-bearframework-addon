<?php

use BearFramework\App;

$app = App::get();

if ($app->currentUser->exists()) {
    $providerID = $app->currentUser->provider;
    $userID = $app->currentUser->id;

    $provider = $app->users->getProvider($providerID);
    if ($provider !== null) {

        $user = $app->users->getUser($providerID, $userID);
        echo '<html><body>';

        $screens = $provider->screens;
        foreach ($screens as $screen) {
            if (isset($screen['showInSettings']) && $screen['showInSettings']) {
                $screenID = $screen['id'];
                $onClick = 'clientPackages.get("users").then(function(users){users.openProviderScreen("' . $providerID . '","' . $screenID . '");});';
                echo '<form-element-button text="' . htmlentities($screen['name']) . '" onclick="' . htmlentities($onClick) . '"/>';
            }
        }
        $onClick = 'clientPackages.get("users").then(function(users){users._closeCurrentWindow();});';
        echo '<form-element-button text="OK" onclick="' . htmlentities($onClick) . '"/>';

        echo '</body></html>';
    }
}
