<?php

use BearFramework\App;

$app = App::get();

$providerID = (string) $component->provider;
$userID = (string) $component->id;

$provider = $app->users->getProvider($providerID);
if ($provider !== null) {

    $user = $app->users->getUser($providerID, $userID);
    echo '<html><head><style>';
    echo '.ivopetkov-users-profile-preview-image{border-radius:4px;background-color:#000;width:100%;aspect-ratio:1/1;background-size:cover;background-repeat:no-repeat;background-position:center center;display:inline-block;}';
    echo '.ivopetkov-users-profile-preview-name{font-weight:bold;margin-top:15px;word-break:break-all;text-align:center;}';
    echo '.ivopetkov-users-profile-preview-description{margin-top:15px;word-break:break-all;text-align:center;}';
    echo '.ivopetkov-users-profile-preview-url{margin-top:15px;word-break:break-all;text-align:center;}';
    echo '.ivopetkov-users-profile-preview-url a{text-decoration:underline;color:#000;}';
    echo '.ivopetkov-users-profile-preview-buttons{margin-top:20px;}';
    echo '</style></head><body>';
    echo '<div class="ivopetkov-users-profile-preview-image" style="background-image:url(' . $user->getImageUrl(500) . ');"></div>';
    echo '<div class="ivopetkov-users-profile-preview-name">' . htmlspecialchars($user->name) . '</div>';
    $userDescription = (string)$user->description;
    if (strlen($userDescription) > 0) {
        echo '<div class="ivopetkov-users-profile-preview-description">' . nl2br(htmlspecialchars($userDescription)) . '</div>';
    }
    $userURL = (string)$user->url;
    if (strlen($userURL) > 0) {
        $url = $userURL;
        if (strpos($url, 'http') !== 0) {
            $url = 'http://' . $url;
        }
        echo '<div class="ivopetkov-users-profile-preview-url"><a href="' . htmlentities($url) . '" target="_blank" rel="noopener">' . htmlspecialchars($userURL) . '</a></div>';
    }
    if ($app->currentUser->exists()) {
        if ($app->currentUser->provider === $user->provider && $app->currentUser->id === $user->id) {
            echo '<div class="ivopetkov-users-profile-preview-buttons">';
            foreach ($provider->screens as $screen) {
                if (isset($screen['showInProfile']) && $screen['showInProfile']) {
                    $screenID = $screen['id'];
                    $onClick = 'clientPackages.get("users").then(function(users){users.openProviderScreen("' . $providerID . '","' . $screenID . '");});';
                    echo '<form-element-button text="' . htmlentities($screen['name']) . '" onclick="' . htmlentities($onClick) . '"/>';
                }
            }
            if ($provider !== null && $provider->hasLogout) {
                $logoutConfirmText = (string)$provider->logoutConfirmText;
                if ($logoutConfirmText === '') {
                    $logoutConfirmText = __('ivopetkov.users.logoutConfirm');
                }
                $onClick = 'if(confirm(' . json_encode($logoutConfirmText) . ')){clientPackages.get("users").then(function(users){users.logout();});};';
                echo '<form-element-button text="' . htmlentities(__('ivopetkov.users.logoutButton')) . '" onclick="' . htmlentities($onClick) . '"/>';
            }
            echo '</div>';
        }
    }
    echo '</body></html>';
}
