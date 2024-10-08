<?php

use BearFramework\App;

$app = App::get();

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
            $onClick = 'clientPackages.get("users").then(function(u){u.openProviderScreen("' . $providerID . '","' . $screenID . '");});';
            echo '<form-element-button text="' . htmlentities($screen['name']) . '" onclick="' . htmlentities($onClick) . '"/>';
        }
    }
    $links = $provider->links;
    foreach ($links as $link) {
        if (isset($link['showInSettings']) && $link['showInSettings']) {
            echo '<form-element-button text="' . htmlentities($link['name']) . '" onclick="' . htmlentities($link['onClick']) . '"/>';
        }
    }
    $onClick = 'clientPackages.get("users").then(function(u){u._closeCurrentWindow();});';
    echo '<form-element-button text="' . __('ivopetkov.users.ok') . '" onclick="' . htmlentities($onClick) . '"/>';

    echo '</body></html>';
}
