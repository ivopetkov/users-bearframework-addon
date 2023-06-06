<?php

use BearFramework\App;

$app = App::get();

$providers = $app->users->getProviders();

$html = '';
foreach ($providers as $providerData) {
    $provider = $app->users->getProvider($providerData['id']);
    if ($provider->hasLogin) {
        $onClick = 'clientPackages.get("users").then(function(users){users.login("' . $providerData['id'] . '");});';
        $html .= '<form-element-button text="' . htmlentities($provider->loginText) . '" onclick="' . htmlentities($onClick) . '"/>';
    }
}
echo '<html>';
echo '<body>';
echo $html;
echo '</body>';
echo '</html>';
