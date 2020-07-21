<?php

use BearFramework\App;

$app = App::get();

$providerID = (string) $component->provider;
$userID = (string) $component->id;

$provider = $app->users->getProvider($providerID);
if ($provider !== null) {

    $user = $app->users->getUser($providerID, $userID);
    echo '<html><head><style>';
    echo '.ivopetkov-users-login-option-button{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#000;background-color:#fff;border-radius:2px;margin-bottom:15px;padding:16px 14px;display:block;cursor:pointer;min-width:200px;text-align:center;}';
    echo '.ivopetkov-users-login-option-button:hover{background-color:#f5f5f5}';
    echo '.ivopetkov-users-login-option-button:active{background-color:#eeeeee}';
    echo '.ivopetkov-users-loading{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#fff;}';
    echo '.ivopetkov-users-profile-preview-image{border-radius:2px;background-color:#000;width:250px;height:250px;background-size:cover;background-repeat:no-repeat;background-position:center center;display:inline-block;}';
    echo '.ivopetkov-users-profile-preview-name{font-family:Arial,Helvetica,sans-serif;font-size:25px;color:#fff;margin-top:15px;max-width:350px;word-break:break-all;}';
    echo '.ivopetkov-users-profile-preview-description{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#fff;margin-top:15px;max-width:350px;word-break:break-all;}';
    echo '.ivopetkov-users-profile-preview-url{margin-top:15px;max-width:350px;word-break:break-all;}';
    echo '.ivopetkov-users-profile-preview-url a{font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#fff;}';
    echo '.ivopetkov-users-profile-preview-buttons{margin-top:20px;}';
    echo '.ivopetkov-users-profile-preview-button{cursor:pointer;font-family:Arial,Helvetica,sans-serif;font-size:15px;border-radius:2px;padding:0 23px;line-height:50px;color:#fff;display:inline-block;}';
    echo '.ivopetkov-users-profile-preview-button:hover{color:#000;background-color:#f5f5f5;};';
    echo '.ivopetkov-users-profile-preview-button:active{color:#000;background-color:#eeeeee;};';
    echo '</style></head><body>';
    echo '<div style="text-align:center;">';
    echo '<div><div class="ivopetkov-users-profile-preview-image" style="background-image:url(' . $user->getImageUrl(500) . ');"></div></div>';
    echo '<div><div class="ivopetkov-users-profile-preview-name">' . htmlspecialchars($user->name) . '</div></div>';
    if (strlen($user->description) > 0) {
        echo '<div><div class="ivopetkov-users-profile-preview-description">' . nl2br(htmlspecialchars($user->description)) . '</div></div>';
    }
    if (strlen($user->url) > 0) {
        $url = $user->url;
        if (strpos($url, 'http') !== 0) {
            $url = 'http://' . $url;
        }
        echo '<div><div class="ivopetkov-users-profile-preview-url"><a href="' . htmlentities($url) . '" target="_blank" rel="noopener">' . htmlspecialchars($user->url) . '</a></div></div>';
    }
    if ($app->currentUser->exists()) {
        if ($app->currentUser->provider === $user->provider && $app->currentUser->id === $user->id) {
            echo '<div class="ivopetkov-users-profile-preview-buttons">';
            foreach ($provider->screens as $screen) {
                if (isset($screen['showInProfile']) && $screen['showInProfile']) {
                    $screenID = $screen['id'];
                    $onClick = 'clientPackages.get("users").then(function(users){users.openProviderScreen("' . $providerID . '","' . $screenID . '");});';
                    echo '<div><a class="ivopetkov-users-profile-preview-button" onclick="' . htmlspecialchars($onClick) . '">' . $screen['name'] . '</a></div>';
                }
            }
            if ($provider !== null && $provider->hasLogout) {
                $onClick = 'clientPackages.get("users").then(function(users){users.logout();});';
                echo '<div><a class="ivopetkov-users-profile-preview-button" onclick="' . htmlspecialchars($onClick) . '">' . __('ivopetkov.users.logoutButton') . '</a></div>';
            }
            echo '</div>';
        }
    }
    echo '</div>';
    echo '</body></html>';
}
