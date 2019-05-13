<?php

use BearFramework\App;

$app = App::get();

$providerID = (string) $component->provider;
$userID = (string) $component->id;

$provider = $app->users->getProvider($providerID);

$user = $app->users->getUser($providerID, $userID);
echo '<html><head><style>';
echo '.ivopetkov-users-login-option-button{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#000;background-color:#fff;border-radius:2px;margin-bottom:15px;padding:16px 14px;display:block;cursor:pointer;min-width:200px;text-align:center;}';
echo '.ivopetkov-users-login-option-button:hover{background-color:#f5f5f5}';
echo '.ivopetkov-users-login-option-button:active{background-color:#eeeeee}';
echo '.ivopetkov-users-loading{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#fff;}';
echo '.ivopetkov-users-account-image{border-radius:2px;background-color:#000;width:250px;height:250px;background-size:cover;background-repeat:no-repeat;background-position:center center;display:inline-block;}';
echo '.ivopetkov-users-account-name{font-family:Arial,Helvetica,sans-serif;font-size:25px;color:#fff;margin-top:15px;max-width:350px;word-break:break-all;}';
echo '.ivopetkov-users-account-description{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#fff;margin-top:15px;max-width:350px;word-break:break-all;}';
echo '.ivopetkov-users-account-url{margin-top:15px;max-width:350px;word-break:break-all;}';
echo '.ivopetkov-users-account-url a{font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#fff;}';
echo '.ivopetkov-users-account-logout-button, .ivopetkov-guest-settings-button{cursor:pointer;font-family:Arial,Helvetica,sans-serif;font-size:15px;border-radius:2px;padding:13px 15px;color:#fff;margin-top:25px;display:inline-block;}';
echo '.ivopetkov-users-account-logout-button:hover, .ivopetkov-guest-settings-button:hover{color:#000;background-color:#f5f5f5;};';
echo '.ivopetkov-users-account-logout-button:active, .ivopetkov-guest-settings-button:active{color:#000;background-color:#eeeeee;};';
echo '</style></head><body>';
echo '<div style="text-align:center;">';
echo '<div><div class="ivopetkov-users-account-image" style="background-image:url(' . $user->getImageUrl(500) . ');"></div></div>';
echo '<div><div class="ivopetkov-users-account-name">' . htmlspecialchars($user->name) . '</div></div>';
if (strlen($user->description) > 0) {
    echo '<div><div class="ivopetkov-users-account-description">' . nl2br(htmlspecialchars($user->description)) . '</div></div>';
}
if (strlen($user->url) > 0) {
    echo '<div><div class="ivopetkov-users-account-url"><a href="' . htmlentities($user->url) . '" target="_blank" rel="noopener">' . htmlspecialchars($user->url) . '</a></div></div>';
}
if ($app->currentUser->exists()) {
    if ($app->currentUser->provider === $user->provider && $app->currentUser->id === $user->id) {
        $hasSettingsButton = $provider !== null && $provider->hasSettings;
        if ($hasSettingsButton) {
            $onClick = 'clientPackages.get("users").then(function(users){users.openSettings();});';
            echo '<div><a class="ivopetkov-guest-settings-button" onclick="' . htmlspecialchars($onClick) . '">' . __('ivopetkov.users.profileSettings') . '</a></div>';
        }
        if ($provider !== null && $provider->hasLogout) {
            $onClick = 'clientPackages.get("users").then(function(users){users.logout();});';
            echo '<div><a class="ivopetkov-users-account-logout-button" onclick="' . htmlspecialchars($onClick) . '" ' . ($hasSettingsButton ? ' style="margin-top:0;"' : '') . '>' . __('ivopetkov.users.logoutButton') . '</a></div>';
        }
    }
}
echo '</div>';
echo '</body></html>';
