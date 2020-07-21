<?php

use BearFramework\App;

$app = App::get();
$providerID = $app->currentUser->provider;

$form->constraints
    ->setRequired('oldpassword')
    ->setRequired('newpassword')
    ->setMinLength('newpassword', 6)
    ->setMaxLength('newpassword', 100)
    ->setRequired('newpassword2')
    ->setMinLength('newpassword2', 6)
    ->setMaxLength('newpassword2', 100);

$form->onSubmit = function ($values) use ($app, $providerID,  $form) {
    $oldPassword = trim((string) $values['oldpassword']);
    $newPassword = trim((string) $values['newpassword']);
    $newPassword2 = trim((string) $values['newpassword2']);
    if ($app->currentUser->exists()) {
        $userID = $app->currentUser->id;

        if ($newPassword !== $newPassword2) {
            $form->throwError('The new passwords does not match!');
        }

        $userData = $app->users->getUserData($providerID, $userID);
        if ($userData === null) {
            $form->throwError('This user cannot be found!');
        }

        if (password_verify($oldPassword, $userData['p'])) {
            $userData['p'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $app->users->saveUserData($providerID, $userID, $userData);
        } else {
            $form->throwError('The current password is not valid!');
        }
    }
};

$onSubmitSuccess = 'clientPackages.get("users").then(function(users){users.openPreview("' . $app->currentUser->provider . '","' . $app->currentUser->id . '");});';
echo '<form onsubmitsuccess="' . htmlentities($onSubmitSuccess) . '">';
echo '<form-element-password name="oldpassword" label="' . htmlentities(__('ivopetkov.users.username.changepassword.Current password')) . '"/>';
echo '<form-element-password name="newpassword" label="' . htmlentities(__('ivopetkov.users.username.changepassword.New password')) . '"/>';
echo '<form-element-password name="newpassword2" label="' . htmlentities(__('ivopetkov.users.username.changepassword.Repeat new password')) . '"/>';
echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.username.changepassword.Save changes')) . '" waitingText="' . htmlentities(__('ivopetkov.users.username.changepassword.Saving ...')) . '" />';
echo '</form>';
