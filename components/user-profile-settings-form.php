<?php

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;

$app = App::get();
$providerID = $app->currentUser->provider;
$userID = $app->currentUser->id;

$provider = $app->users->getProvider($providerID);
if (isset($provider->options['profileFields'])) {
    $profileFields = $provider->options['profileFields'];
} else {
    $profileFields = [];
}

$hasImage = array_search('image', $profileFields) !== false;
$hasName = array_search('name', $profileFields) !== false;
$hasWebsite = array_search('website', $profileFields) !== false;
$hasDescription = array_search('description', $profileFields) !== false;

$getUserData = function () use ($app, $providerID, $userID, $hasImage, $hasName, $hasWebsite, $hasDescription) {
    $data = $app->users->getUserData($providerID, $userID);
    if (empty($data)) {
        $data = [];
    }
    if ($hasImage && !isset($data['image'])) {
        $data['image'] = '';
    }
    if ($hasName && !isset($data['name'])) {
        $data['name'] = '';
    }
    if ($hasWebsite && !isset($data['website'])) {
        $data['website'] = '';
    }
    if ($hasDescription && !isset($data['description'])) {
        $data['description'] = '';
    }
    return $data;
};

$form->onSubmit = function ($values) use ($app, $providerID, $userID, $getUserData, $form, $hasImage, $hasName, $hasWebsite, $hasDescription) {
    $data = $getUserData();
    if ($hasName) {
        $data['name'] = isset($values['name']) ? trim((string) $values['name']) : '';
    }
    if ($hasWebsite) {
        $data['website'] = isset($values['website']) ? trim((string) $values['website']) : '';
    }
    if ($hasDescription) {
        $data['description'] = isset($values['description']) ? trim((string) $values['description']) : '';
    }
    if ($hasImage) {
        $removeOldImageIfExists = isset($values['image']) && strlen($values['image']) === 0;
        $newImageKey = null;
        if (isset($values['image']) && $values['image'] !== 'img') {
            $files = json_decode($values['image'], true);
            if (isset($files[0])) {
                $extension = strtolower(pathinfo($files[0]['value'], PATHINFO_EXTENSION));
                if (array_search($extension, ['png', 'gif', 'jpg', 'jpeg']) === false) {
                    $form->throwError(__('ivopetkov.users.profileSettings.invalidImageFormat'));
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
    }
    $app->users->saveUserData($providerID, $userID, $data);
    $app->currentUser->clearCache();

    return Utilities::getFormSubmitResult(['jsCode' => 'clientPackages.get("users").then(function(users){users._updateBadge(' . json_encode(Utilities::getBadgeHTML()) . ');users._closeAllWindows().then(function(){users._dispatchProfileChange();})});']);
};

$data = $getUserData();

echo '<form onsubmitsuccess="' . Utilities::getFormSubmitResultHandlerJsCode() . '">';
if ($hasImage) {
    echo '<form-element-image name="image" label="' . htmlentities(__('ivopetkov.users.profileSettings.image')) . '" value="' . htmlentities(strlen($data['image']) > 0 ? 'img' : '') . '" valuePreviewUrl="' . htmlentities(strlen($data['image']) > 0 ? $app->currentUser->getImageURL(500) : '') . '" />';
}
if ($hasName) {
    echo '<form-element-textbox name="name" label="' . htmlentities(__('ivopetkov.users.profileSettings.name')) . '" value="' . htmlentities($data['name']) . '" />';
}
if ($hasWebsite) {
    echo '<form-element-textbox name="website" label="' . htmlentities(__('ivopetkov.users.profileSettings.website')) . '" value="' . htmlentities($data['website']) . '" />';
}
if ($hasDescription) {
    echo '<form-element-textarea name="description" label="' . htmlentities(__('ivopetkov.users.profileSettings.description')) . '" value="' . htmlentities($data['description']) . '" />';
}
echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.profileSettings.save')) . '" waitingText="' . htmlentities(__('ivopetkov.users.profileSettings.saving')) . '" />';
echo '</form>';
