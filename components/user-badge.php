<?php

use BearFramework\App;

$app = App::get();

echo '<html><head>';
echo '<link rel="client-shortcuts">';
echo '<style>.ivopetkov-users-badge{cursor:pointer;width:48px;height:48px;position:fixed;z-index:1000000;top:14px;right:14px;border-radius:2px;background-color:black;box-shadow:0 1px 2px 0px rgba(0,0,0,0.2);background-size:cover;background-position:center center;}</style>';
echo '</head><body>';
$styles = 'background-image:url(' . $app->currentUser->getImageUrl(100) . ');';
$onClick = 'clientShortcuts.get("users").then(function(users){users.openPreview("' . $app->currentUser->provider . '","' . $app->currentUser->id . '");});';
echo '<div class="ivopetkov-users-badge" style="' . htmlentities($styles) . '" onclick="' . htmlentities($onClick) . '"></div>';
echo '</body></html>';
