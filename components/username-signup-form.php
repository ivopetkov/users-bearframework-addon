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
    ->setMaxLength('password', 100)
    ->setRequired('password2')
    ->setMinLength('password2', 6)
    ->setMaxLength('password2', 100);

$form->onSubmit = function ($values) use ($app, $providerID,  $form) {
    $username = strtolower(trim((string) $values['username']));
    $password = trim((string) $values['password']);
    $password2 = trim((string) $values['password2']);
    $userID = md5($username);

    if ($password !== $password2) {
        $form->throwElementError('password2', 'The passwords does not match!');
    }

    if ($app->users->getUserData($providerID, $userID) !== null) {
        $form->throwElementError('username', 'This username is taken!');
    }

    $app->users->saveUserData($providerID, $userID, [
        'u' => $username,
        'd' => time(),
        'p' => password_hash($password, PASSWORD_DEFAULT)
    ]);
    $app->currentUser->login($providerID, $userID);
    $provider = $app->users->getProvider($providerID);
    if (isset($provider->options['onSignup'])) {
        $onSignup = call_user_func($provider->options['onSignup']);
        if (isset($onSignup['redirectURL'])) {
            return $onSignup['redirectURL'];
        }
    }
};

echo '<form onsubmitsuccess="var r=event.result;if(r.length>0){window.location=r;}">';
echo '<form-element-textbox name="username" label="' . htmlentities(__('ivopetkov.users.username.signup.Username')) . '" autocomplete="off" />';
echo '<form-element-password name="password" label="' . htmlentities(__('ivopetkov.users.username.signup.Password')) . '"/>';
echo '<form-element-password name="password2" label="' . htmlentities(__('ivopetkov.users.username.signup.Repeat password')) . '"/>';
echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.username.signup.Sign up')) . '" waitingText="' . htmlentities(__('ivopetkov.users.username.signup.Signing up ...')) . '" />';
echo '</form>';
