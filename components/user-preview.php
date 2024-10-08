<?php

use BearFramework\App;

$app = App::get();

$providerID = (string) $component->provider;
$userID = (string) $component->id;

$provider = $app->users->getProvider($providerID);
if ($provider !== null) {

    $user = $app->users->getUser($providerID, $userID);
    echo '<html><head><style>';
    echo '.ivopetkov-users-profile-preview-image{background-color:#000;width:calc(100% + 2*21px);margin-top:-63px;margin-left:-21px;border-top-left-radius:6px;border-top-right-radius:6px;aspect-ratio:1/1;background-size:cover;background-repeat:no-repeat;background-position:center center;display:block;}';
    echo '.ivopetkov-users-profile-preview-name{font-weight:bold;margin-top:15px;word-break:break-all;text-align:center;color:#000;margin-top:25px;}';
    echo '.ivopetkov-users-profile-preview-description{margin-top:15px;word-break:break-all;text-align:center;color:#000;}';
    echo '.ivopetkov-users-profile-preview-url{margin-top:15px;word-break:break-all;text-align:center;}';
    echo '.ivopetkov-users-profile-preview-url a{text-decoration:underline;color:#000;}';
    echo '.ivopetkov-users-profile-preview-buttons{margin-top:30px;}';
    echo '[data-modal-window-component="window"]:has(.ivopetkov-users-profile-preview-image) [data-modal-window-component="header-button-close"]{background-color:rgba(0,0,0,0.5);}';
    echo '[data-modal-window-component="window"]:has(.ivopetkov-users-profile-preview-image) [data-modal-window-component="header-button-close"]:hover{background-color:rgba(0,0,0,0.6);}';
    echo '[data-modal-window-component="window"]:has(.ivopetkov-users-profile-preview-image) [data-modal-window-component="header-button-close"]:focus{background-color:rgba(0,0,0,0.6);}';
    echo '[data-modal-window-component="window"]:has(.ivopetkov-users-profile-preview-image) [data-modal-window-component="header-button-close"]:active{background-color:rgba(0,0,0,0.7);}';
    echo '</style></head><body>';
    echo '<div class="ivopetkov-users-profile-preview-image" style="background-image:url(' . $user->getImageURL(500) . ');"></div>';
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
    $buttonsHTML = [];
    if ($app->currentUser->exists() && $app->currentUser->provider === $user->provider && $app->currentUser->id === $user->id) {
        $screens = $provider->screens;
        if (isset($provider->options['profileFields'])) {
            array_unshift($screens, ['id' => 'user-profile-settings', 'name' => __('ivopetkov.users.profileSettingsButton'), 'showInProfile' => true]);
        }
        $hasShowInSettingsScreens = false;
        foreach ($screens as $screen) {
            if (isset($screen['showInProfile']) && $screen['showInProfile']) {
                $screenID = $screen['id'];
                $onClick = 'clientPackages.get("users").then(function(u){u.openProviderScreen("' . $providerID . '","' . $screenID . '");});';
                $buttonsHTML[] = '<form-element-button text="' . htmlentities($screen['name']) . '" onclick="' . htmlentities($onClick) . '"/>';
            }
            if (isset($screen['showInSettings']) && $screen['showInSettings']) {
                $hasShowInSettingsScreens = true;
            }
        }
        $links = $provider->links;
        foreach ($links as $link) {
            if (isset($link['showInProfile']) && $link['showInProfile']) {
                $buttonsHTML[] = '<form-element-button text="' . htmlentities($link['name']) . '" onclick="' . htmlentities($link['onClick']) . '"/>';
            }
            if (isset($link['showInSettings']) && $link['showInSettings']) {
                $hasShowInSettingsScreens = true;
            }
        }
        if ($hasShowInSettingsScreens) {
            $onClick = 'clientPackages.get("users").then(function(u){u.currentUser.openSettings();});';
            $buttonsHTML[] = '<form-element-button text="' . htmlentities(__('ivopetkov.users.settingsButton')) . '" onclick="' . htmlentities($onClick) . '"/>';
        }
        if ($provider !== null && $provider->hasLogout) {
            $logoutConfirmText = (string)$provider->logoutConfirmText;
            if ($logoutConfirmText === '') {
                $logoutConfirmText = __('ivopetkov.users.logoutConfirm');
            }
            $onClick = 'if(confirm(' . json_encode($logoutConfirmText) . ')){clientPackages.get("users").then(function(u){u.logout();});};';
            $buttonsHTML[] = '<form-element-button text="' . htmlentities(__('ivopetkov.users.logoutButton')) . '" onclick="' . htmlentities($onClick) . '"/>';
        }
    }
    if (empty($buttonsHTML)) {
        $onClick = 'clientPackages.get("users").then(function(u){u._closeAllWindows();});';
        $buttonsHTML[] = '<form-element-button text="' . __('ivopetkov.users.ok') . '" onclick="' . htmlentities($onClick) . '"/>';
    }
    echo '<div class="ivopetkov-users-profile-preview-buttons">';
    echo implode('', $buttonsHTML);
    echo '</div>';
    echo '</body></html>';
}
