<?php

use BearFramework\App;

$app = App::get();

$providers = $app->users->getProviders();

$html = '';
foreach ($providers as $providerData) {
    $provider = $app->users->getProvider($providerData['id']);
    if ($provider->hasLogin) {
        $onClick = 'clientPackages.get("users").then(function(users){users.login("'.$providerData['id'].'");});';
        $html .= '<div><a class="ivopetkov-users-login-option-button" onclick="'. htmlentities($onClick).'">' . htmlspecialchars($provider->loginText) . '</a></div>';
    }
}
?><html><head>
        <style>
            .ivopetkov-users-login-option-button{
                box-sizing: border-box;
                width:250px;
                font-family:Arial,Helvetica,sans-serif;
                background-color:#fff;
                font-size:15px;
                border-radius:2px;
                padding:13px 15px;
                color:#000;
                margin-top:25px;
                display:block;
                text-align:center;
                cursor:pointer;
            }
            .ivopetkov-users-login-option-button:not([disabled]):hover{
                background-color:#f5f5f5;
            }
            .ivopetkov-users-login-option-button:not([disabled]):active{
                background-color:#eeeeee;
            }
        </style>
    </head>
    <body><?= $html; ?></body></html>
