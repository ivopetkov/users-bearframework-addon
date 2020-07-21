<?php

use BearFramework\App;

$app = App::get();
$providerID = $component->providerID;

$form->constraints
    ->setRequired('username')
    ->setMinLength('username', 6)
    ->setMaxLength('username', 100)
    ->setRequired('password')
    ->setMinLength('password', 6)
    ->setMaxLength('password', 100);

$form->onSubmit = function ($values) use ($app, $providerID,  $form) {
    $username = strtolower(trim((string) $values['username']));
    $password = trim((string) $values['password']);
    $userID = md5($username);

    $userData = $app->users->getUserData($providerID, $userID);
    if ($userData !== null && $userData['u'] === $username) {
        if (password_verify($password, $userData['p'])) {
            $app->currentUser->login($providerID, $userID);
            $provider = $app->users->getProvider($providerID);
            if (isset($provider->options['onLogin'])) {
                $onLogin = call_user_func($provider->options['onLogin']);
                if (isset($onLogin['redirectURL'])) {
                    return $onLogin['redirectURL'];
                }
            }
            return '';
        }
    }
    $form->throwElementError('password', __('ivopetkov.users.username.login.The password provided is not valid!'));
};

echo '<form onsubmitsuccess="var r=event.result;if(r.length>0){window.location=r;}">';
echo '<form-element-textbox name="username" label="' . htmlentities(__('ivopetkov.users.username.login.Username')) . '" autocomplete="off" />';
echo '<form-element-password name="password" label="' . htmlentities(__('ivopetkov.users.username.login.Password')) . '"/>';
echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.username.login.Login')) . '" waitingText="' . htmlentities(__('ivopetkov.users.username.login.Login ...')) . '" />';
echo '</form>';
