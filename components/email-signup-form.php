<?php

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\EmailProvider;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;

$app = App::get();
$providerID = $component->providerID;

$termsURL = null;
$provider = $app->users->getProvider($providerID);
if (isset($provider->options['getTermsURL'])) {
    $termsURL = call_user_func($provider->options['getTermsURL']);
}
$hasTermsURL = $termsURL !== null;

$form->transformers
    ->addToLowerCase('email')
    ->addTrim('email')
    ->addTrim('password');

$form->constraints
    ->setRequired('email')
    ->setMaxLength('email', 200)
    ->setEmail('email')
    ->setRequired('password')
    ->setMinLength('password', 6)
    ->setMaxLength('password', 100);

if ($hasTermsURL) {
    $form->constraints
        ->setRequired('terms');
}

$form->onSubmit = function ($values) use ($app, $providerID, $form) {
    $email = $values['email'];
    $password = $values['password'];

    if (!$app->rateLimiter->logIP('ivopetkov-users-email-signup-form', ['10/m', '50/h'])) {
        $form->throwError(__('ivopetkov.users.tryAgainLater'));
    }

    if (EmailProvider::emailExists($providerID, $email)) {
        $form->throwElementError('email', __('ivopetkov.users.email.signUp.emailTaken'));
    }

    if ($app->rateLimiter->log('ivopetkov-users-email-signup-send-email', $email, ['1/h'])) {
        $key = EmailProvider::generateSignupKey($providerID, $email, $password);
        EmailProvider::sendSignupConfirmEmail($providerID, $email, $key);
    }

    return Utilities::getFormSubmitResult(['jsCode' => 'clientPackages.get("users").then(function(u){u._closeAllWindows({expectOpen:true}).then(function(){u.openProviderScreen("' . $providerID . '","signup-email-sent",{"email":"' . $email . '"});})});']);
};

echo '<html><head><style>';
echo '[data-user-email-signup-form-component="already-loggedin-message"]{text-align:center;padding-bottom:60px;}';
echo '</style></head></html>';
if ($app->currentUser->exists()) {
    $getOnLoginURL = function () use ($app, $providerID) {
        $provider = $app->users->getProvider($providerID);
        if (isset($provider->options['getOnLoginURL'])) {
            return call_user_func($provider->options['getOnLoginURL']);
        }
        return $app->urls->get('/');
    };
    echo '<div data-user-email-signup-form-component="already-loggedin-message">' . __('ivopetkov.users.alreadyLoggedIn') . '</div>';
    $onClick = 'clientPackages.get("users").then(function(u){u._openURL("' . $getOnLoginURL() . '",true);});';
    echo '<form-element-button text="' . __('ivopetkov.users.continue') . '" onclick="' . htmlentities($onClick) . '"/>';
} else {
    echo '<form onsubmitsuccess="' . Utilities::getFormSubmitResultHandlerJsCode() . '">';
    echo '<form-element-textbox name="email" label="' . htmlentities(__('ivopetkov.users.email.signUp.email')) . '" hintAfter="' . htmlentities(__('ivopetkov.users.email.signUp.emailHint')) . '" autocomplete="off" inputType="email" />';
    echo '<form-element-password name="password" label="' . htmlentities(__('ivopetkov.users.email.signUp.password')) . '"/>';
    if ($hasTermsURL) {
        echo '<form-element-checkbox name="terms" labelHTML="' . htmlentities(sprintf(__('ivopetkov.users.email.signUp.acceptTerms'), $termsURL)) . '" style="display:inline-block;"/>';
    }
    echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.email.signUp.signUp')) . '" waitingText="' . htmlentities(__('ivopetkov.users.email.signUp.signUpWaiting')) . '" />';
    echo '</form>';
}
