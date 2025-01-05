<?php

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\EmailProvider;

$app = App::get();
$providerID = $component->providerID;
$passwordResetKey = (string)$component->key;

$form->transformers
    ->addTrim('newpassword')
    ->addTrim('newpassword2');

$form->constraints
    ->setRequired('newpassword')
    ->setMinLength('newpassword', 6)
    ->setMaxLength('newpassword', 100)
    ->setRequired('newpassword2')
    ->setMinLength('newpassword2', 6)
    ->setMaxLength('newpassword2', 100);

$form->onSubmit = function ($values) use ($app, $providerID, $form, $passwordResetKey): void {
    $newPassword = $values['newpassword'];
    $newPassword2 = $values['newpassword2'];

    $userID = EmailProvider::validatePasswordResetKey($providerID, $passwordResetKey);

    if ($userID !== null) {
        if ($newPassword !== $newPassword2) {
            $form->throwElementError('newpassword2', __('ivopetkov.users.email.lostPasswordNew.passwordsDontMatch'));
        }
        if (!EmailProvider::exists($providerID, $userID)) {
            $form->throwError();
        }

        EmailProvider::setPassword($providerID, $userID, $newPassword);
        EmailProvider::deletePasswordResetKey($providerID, $passwordResetKey);
    }
};

$onSuccess = 'clientPackages.get("users").then(function(u){u._closeAllWindows({expectOpen:true}).then(function(){u.openProviderScreen("' . $providerID . '","lost-password-new-result");})});';
echo '<form onsubmitsuccess="' . htmlentities($onSuccess) . '">';
echo '<form-element-password name="newpassword" label="' . htmlentities(__('ivopetkov.users.email.lostPasswordNew.newPassword')) . '"/>';
echo '<form-element-password name="newpassword2" label="' . htmlentities(__('ivopetkov.users.email.lostPasswordNew.repeatNewPassword')) . '"/>';
echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.email.lostPasswordNew.save')) . '" waitingText="' . htmlentities(__('ivopetkov.users.email.lostPasswordNew.saveWaiting')) . '" />';
echo '</form>';
