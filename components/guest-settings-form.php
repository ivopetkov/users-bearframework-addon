<?php

use BearFramework\App;

$app = App::get();
$providerID = $app->currentUser->provider;
$userID = $app->currentUser->id;

$getUserData = function () use ($app, $providerID, $userID) {
    $data = $app->users->getUserData($providerID, $userID);
    if (empty($data)) {
        $data = [];
    }
    if (!isset($data['image'])) {
        $data['image'] = '';
    }
    if (!isset($data['name'])) {
        $data['name'] = '';
    }
    if (!isset($data['website'])) {
        $data['website'] = '';
    }
    if (!isset($data['description'])) {
        $data['description'] = '';
    }
    return $data;
};

$form->onSubmit = function ($values) use ($app, $providerID, $userID, $getUserData, $form) {
    $data = $getUserData();
    $data['name'] = isset($values['name']) ? trim((string) $values['name']) : '';
    $data['website'] = isset($values['website']) ? trim((string) $values['website']) : '';
    $data['description'] = isset($values['description']) ? trim((string) $values['description']) : '';

    $removeOldImageIfExists = isset($values['image']) && strlen($values['image']) === 0;

    $newImageKey = null;
    if (isset($values['image']) && $values['image'] !== 'img') {
        $files = json_decode($values['image'], true);
        if (isset($files[0])) {
            $extension = strtolower(pathinfo($files[0]['value'], PATHINFO_EXTENSION));
            if (array_search($extension, ['png', 'gif', 'jpg', 'jpeg']) === false) {
                $form->throwError(__('ivopetkov.users.guest.settings.invalidImageFormat'));
            }
            $newImageKey = $app->users->saveUserFile($providerID, $files[0]['filename'], $extension);
            $removeOldImageIfExists = true;
        }
    }
    if ($removeOldImageIfExists && strlen($data['image']) > 0) {
        $app->users->deleteUserFile($providerID, $data['image']);
        $data['image'] = '';
    }
    if ($newImageKey !== null) {
        $data['image'] = $newImageKey;
    }

    $app->users->saveUserData($providerID, $userID, $data);
};

$data = $getUserData();

$onSubmitSuccess = 'clientPackages.get("modalWindows").then(function(modalWindows){modalWindows.closeAll();});';
echo '<form onsubmitsuccess="' . htmlentities($onSubmitSuccess) . '">';
echo '<form-element-image name="image" label="' . htmlentities(__('ivopetkov.users.guest.settings.image')) . '" value="' . htmlentities(strlen($data['image']) > 0 ? 'img' : '') . '" valuePreviewUrl="' . htmlentities(strlen($data['image']) > 0 ? $app->currentUser->getImageUrl(500) : '') . '" />';
echo '<form-element-textbox name="name" label="' . htmlentities(__('ivopetkov.users.guest.settings.name')) . '" value="' . htmlentities($data['name']) . '" />';
echo '<form-element-textbox name="website" label="' . htmlentities(__('ivopetkov.users.guest.settings.website')) . '" value="' . htmlentities($data['website']) . '" />';
echo '<form-element-textarea name="description" label="' . htmlentities(__('ivopetkov.users.guest.settings.description')) . '" value="' . htmlentities($data['description']) . '" />';
echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.guest.settings.save')) . '" waitingText="' . htmlentities(__('ivopetkov.users.guest.settings.saving')) . '" />';
echo '</form>';
