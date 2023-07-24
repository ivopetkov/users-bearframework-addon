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

$form->onSubmit = function ($values) use ($providerID, $form) {
    $email = $values['email'];
    $password = $values['password'];

    if (EmailProvider::getUserID($providerID, $email) !== null) {
        $form->throwError(__('ivopetkov.users.email.signUp.emailTaken'));
    }

    $key = EmailProvider::generateSignupKey($providerID, $email, $password);
    EmailProvider::sendSignupConfirmEmail($providerID, $email, $key);

    return Utilities::getFormSubmitResult(['jsCode' => 'clientPackages.get("users").then(function(users){users._closeAllWindows();users.openProviderScreen("' . $providerID . '","signup-email-sent",{"email":"' . $email . '"});});']);
};

echo '<form onsubmitsuccess="' . Utilities::getFormSubmitResultHandlerJsCode() . '">';
echo '<form-element-textbox name="email" label="' . htmlentities(__('ivopetkov.users.email.signUp.email')) . '" hintAfter="' . htmlentities(__('ivopetkov.users.email.signUp.emailHint')) . '" autocomplete="off" />';
echo '<form-element-password name="password" label="' . htmlentities(__('ivopetkov.users.email.signUp.password')) . '"/>';
if ($hasTermsURL) {
    echo '<form-element-checkbox name="terms" labelHTML="' . htmlentities(sprintf(__('ivopetkov.users.email.signUp.acceptTerms'), $termsURL)) . '" style="display:inline-block;"/>';
}
echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.email.signUp.signUp')) . '" waitingText="' . htmlentities(__('ivopetkov.users.email.signUp.signUpWaiting')) . '" />';
echo '</form>';
