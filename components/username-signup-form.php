<?php

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;
use IvoPetkov\BearFrameworkAddons\Users\UsernameProvider;

$app = App::get();
$providerID = $component->providerID;

$termsURL = null;
$provider = $app->users->getProvider($providerID);
if (isset($provider->options['getTermsURL'])) {
    $termsURL = call_user_func($provider->options['getTermsURL']);
}
$hasTermsURL = $termsURL !== null;

$form->transformers
    ->addToLowerCase('username')
    ->addTrim('username')
    ->addTrim('password')
    ->addTrim('password2');

$form->constraints
    ->setRequired('username')
    ->setMaxLength('username', 100)
    ->setRequired('password')
    ->setMinLength('password', 6)
    ->setMaxLength('password', 100)
    ->setRequired('password2')
    ->setMinLength('password2', 6)
    ->setMaxLength('password2', 100);

if ($hasTermsURL) {
    $form->constraints
        ->setRequired('terms');
}

$form->onSubmit = function ($values) use ($app, $providerID, $form) {
    $username = $values['username'];
    if (preg_match('/^[a-z0-9]*$/', $username) !== 1) {
        $form->throwElementError('username', __('ivopetkov.users.username.signUp.usernameInvalid'));
    }
    $password = $values['password'];
    $password2 = $values['password2'];

    if ($password !== $password2) {
        $form->throwElementError('password2', __('ivopetkov.users.username.signUp.passwordsDontMatch'));
    }

    if (!$app->rateLimiter->logIP('ivopetkov-users-username-signup-form', ['10/m', '50/h'])) {
        $form->throwError(__('ivopetkov.users.tryAgainLater'));
    }

    if (UsernameProvider::usernameExists($providerID, $username)) {
        $form->throwElementError('username', __('ivopetkov.users.username.signUp.usernameTaken'));
    }

    $userID = UsernameProvider::create($providerID, $username, $password);
    $app->users->dispatchSignupEvent($providerID, $userID);

    $app->currentUser->login($providerID, $userID);
    $app->users->dispatchLoginEvent($providerID, $userID);

    $provider = $app->users->getProvider($providerID);
    if (isset($provider->options['getOnSignupURL'])) {
        $url = call_user_func($provider->options['getOnSignupURL']);
    } else {
        $url = $app->urls->get('/');
    }
    return Utilities::getFormSubmitResult(['redirectURL' => $url]);
};

echo '<html><head><style>';
echo '[data-user-username-signup-form-component="already-loggedin-message"]{text-align:center;padding-bottom:60px;}';
echo '</style></head></html>';
if ($app->currentUser->exists()) {
    $getOnLoginURL = function () use ($app, $providerID) {
        $provider = $app->users->getProvider($providerID);
        if (isset($provider->options['getOnLoginURL'])) {
            return call_user_func($provider->options['getOnLoginURL']);
        }
        return $app->urls->get('/');
    };
    echo '<div data-user-username-signup-form-component="already-loggedin-message">' . __('ivopetkov.users.alreadyLoggedIn') . '</div>';
    $onClick = 'clientPackages.get("users").then(function(u){u._openURL("' . $getOnLoginURL() . '",true);});';
    echo '<form-element-button text="' . __('ivopetkov.users.continue') . '" onclick="' . htmlentities($onClick) . '"/>';
} else {
    echo '<form onsubmitsuccess="' . Utilities::getFormSubmitResultHandlerJsCode() . '">';
    echo '<form-element-textbox name="username" label="' . htmlentities(__('ivopetkov.users.username.signUp.username')) . '" autocomplete="off" />';
    echo '<form-element-password name="password" label="' . htmlentities(__('ivopetkov.users.username.signUp.password')) . '"/>';
    echo '<form-element-password name="password2" label="' . htmlentities(__('ivopetkov.users.username.signUp.repeatPassword')) . '"/>';
    if ($hasTermsURL) {
        echo '<form-element-checkbox name="terms" labelHTML="' . htmlentities(sprintf(__('ivopetkov.users.username.signUp.acceptTerms'), $termsURL)) . '" style="display:inline-block;"/>';
    }
    echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.username.signUp.signUp')) . '" waitingText="' . htmlentities(__('ivopetkov.users.username.signUp.signUpWaiting')) . '" />';
    echo '</form>';
}
