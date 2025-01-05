<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;

class EmailProvider extends Provider
{

    /**
     * 
     */
    public function __construct(string $id, array $options = [])
    {
        parent::__construct($id, $options);

        $this->hasLogin = true;
        $this->loginText = __('ivopetkov.users.email.buttons.loginWithEmail');
        $this->hasLogout = true;

        $this->screens[] = ['id' => 'change-email', 'name' => __('ivopetkov.users.email.buttons.changeEmail'), 'showInSettings' => true];
        $this->screens[] = ['id' => 'change-password', 'name' => __('ivopetkov.users.email.buttons.changePassword'), 'showInSettings' => true];
        if (isset($this->options['hasDelete']) ? (int)$this->options['hasDelete'] === 1 : true) {
            $this->screens[] = ['id' => 'delete', 'name' => __('ivopetkov.users.email.buttons.delete'), 'showInSettings' => true];
        }
        $this->screens[] = ['id' => 'signup', 'name' => __('ivopetkov.users.email.buttons.signUp')];
        $this->screens[] = ['id' => 'login', 'name' => __('ivopetkov.users.email.buttons.login')];
        $this->screens[] = ['id' => 'lost-password', 'name' => __('ivopetkov.users.email.login.lostPassword')];

        $this->imageMaxAge = 999999999;
    }

    /**
     * @param string $id
     * @param array $data 
     * @return string|array 'content' or ['title'=>'', 'content'=>'', 'width'=>'']
     * @throws \Exception
     */
    public function getScreenContent(string $id, array $data = [])
    {
        $app = App::get();
        $context = $app->contexts->get();

        $componentAttributes = [];

        if ($id === '-hash-callback') {
            if ($data['path']) {
                $path = $data['path'];
                if ($path === 's:1') {
                    $id = 'signup-result-ok';
                } elseif ($path === 's:0') {
                    $id = 'signup-result-error';
                } elseif (substr($path, 0, 2) === 'p:' && strlen($path) > 2) {
                    $id = 'lost-password-new';
                    $componentAttributes = ['key' => substr($path, 2)];
                } elseif ($path === 'p:') {
                    $id = 'lost-password-result-error';
                } else if ($path === 'e:1') {
                    $id = 'change-email-result-ok';
                } elseif ($path === 'e:0') {
                    $id = 'change-email-result-error';
                } else {
                    return '';
                }
            } else {
                return '';
            }
        }

        $hasUser = $app->currentUser->exists();

        $screens = [ // require logged in user, title, content (look for component if null), width
            'signup' => [false, $hasUser ? '' : __('ivopetkov.users.email.signUp.screenTitle'), null, '350px'],
            'signup-email-sent' => [false, '', sprintf(__('ivopetkov.users.email.signUpEmailSent.screenText'), isset($data['email']) ? $data['email'] : ''), '300px'],
            'signup-result-ok' => [false, '', __('ivopetkov.users.email.signUpResultOk.screenText'), '300px'],
            'signup-result-error' => [false, '', __('ivopetkov.users.email.signUpResultError.screenText'), '300px'],
            'login' => [false, $hasUser ? '' : __('ivopetkov.users.email.login.screenTitle'), null, '350px'],
            'lost-password' => [false, __('ivopetkov.users.email.lostPassword.screenTitle'), null, '350px'],
            'lost-password-email-sent' => [false, '', sprintf(__('ivopetkov.users.email.lostPasswordEmailSent.screenText'), isset($data['email']) ? $data['email'] : ''), '300px'],
            'lost-password-result-error' => [false, '', __('ivopetkov.users.email.lostPasswordResultError.screenText'), '300px'],
            'lost-password-new' => [false, __('ivopetkov.users.email.lostPasswordNew.screenTitle'), null, '350px'],
            'lost-password-new-result' => [false, '', __('ivopetkov.users.email.lostPasswordNewResult.screenText'), '300px'],
            'change-email' => [true, __('ivopetkov.users.email.changeEmail.screenTitle'), null, '350px'],
            'change-email-email-sent' => [true, '', sprintf(__('ivopetkov.users.email.changeEmailEmailSent.screenText'), isset($data['email']) ? $data['email'] : ''), '300px'],
            'change-email-result-ok' => [false, '', __('ivopetkov.users.email.changeEmailResultOk.screenText'), '300px'],
            'change-email-result-error' => [false, '', __('ivopetkov.users.email.changeEmailResultError.screenText'), '300px'],
            'change-password' => [true, __('ivopetkov.users.email.changePassword.screenTitle'), null, '350px'],
            'delete' => [true, __('ivopetkov.users.email.delete.screenTitle'), null, '300px']
        ];

        if (isset($screens[$id])) {
            $screenData = $screens[$id];
            if ($screenData[0]) {
                if ($hasUser && $app->currentUser->provider === $this->id) {
                    // has logged in user
                } else {
                    return '';
                }
            }
            if ($screenData[2] !== null) {
                $content = '<div style="text-align:center;padding-bottom:50px;">' . $screenData[2] . '</div>';
                $onOK = 'clientPackages.get("users").then(function(u){u._closeAllWindows();});';
                $content .= '<form-element-button text="' . __('ivopetkov.users.ok') . '" onclick="' . htmlentities($onOK) . '"/>';
            } else {
                $attributes = '';
                foreach ($componentAttributes as $name => $value) {
                    $attributes = ' ' . $name . '="' . htmlentities($value) . '"';
                }
                $content = $app->components->process('<component src="form" filename="' . $context->dir . '/components/email-' . $id . '-form.php" providerID="' . htmlentities($this->id) . '"' . $attributes . '/>');
            }
            return [
                'width' => $screenData[3],
                'title' => $screenData[1],
                'content' => $content
            ];
        }
        return '';
    }

    /**
     * 
     * @param \IvoPetkov\BearFrameworkAddons\Users\LoginContext $context
     * @return \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
     */
    public function login(\IvoPetkov\BearFrameworkAddons\Users\LoginContext $context): \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
    {
        $response = new \IvoPetkov\BearFrameworkAddons\Users\LoginResponse();
        $response->jsCode = "clientPackages.get('users').then(function(u){u.openProviderLogin('" . $context->providerID . "');});";
        return $response;
    }

    /**
     * 
     * @param string $id
     * @return array
     */
    public function getProfileData(string $id): array
    {
        $app = App::get();
        $userData = $app->users->getUserData($this->id, $id);
        $properties = Utilities::getProfileDataFromUserData($this, $userData);
        if (!isset($properties['name'])) {
            $properties['name'] = __('ivopetkov.users.anonymous'); // just in case it's missing
        }
        $properties['email'] = $userData['email'];
        return $properties;
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @param string $reason
     * @return array
     */
    static function canDelete(string $providerID, string $userID): array
    {
        $app = App::get();
        $provider = $app->users->getProvider($providerID);
        if (isset($provider->options['canDelete'])) {
            return call_user_func($provider->options['canDelete'], $userID);
        }
        return ['result' => true, 'reason' => ''];
    }

    /**
     * 
     * @param string $providerID
     * @param string $email
     * @return boolean
     */
    static function emailExists(string $providerID, string $email): bool
    {
        $userID = self::getUserID($providerID, $email);
        if ($userID === null) {
            return false;
        }
        return self::exists($providerID, $userID);
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @return boolean
     */
    static function exists(string $providerID, string $userID): bool
    {
        $app = App::get();
        return $app->users->getUserData($providerID, $userID) !== null;
    }

    /**
     * 
     * @param string $providerID
     * @param string $email
     * @param string $password
     * @param integer $maxAge
     * @return string
     */
    static function generateSignupKey(string $providerID, string $email, string $password, int $maxAge = 86400): string
    {
        if (self::getUserID($providerID, $email) !== null) {
            throw new \Exception('The email is used (' . $email . ')');
        }
        $app = App::get();
        for ($i = 0; $i < 100; $i++) {
            $key = 's' . Utilities::generateKey(45);
            if ($app->users->getTempData($providerID, $key) === null) {
                $data = ['email' => $email, 'password' => self::hashPassword($password), 'date' => time(), 'maxAge' => $maxAge];
                $app->users->saveTempData($providerID, $key, $data);
                return $key;
            }
        }
        throw new \Exception('Too many retries!');
    }

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return string|null
     */
    static function confirmSignupKey(string $providerID, string $key): ?string
    {
        if ($key[0] === 's') {
            $app = App::get();
            $data = $app->users->getTempData($providerID, $key);
            if (is_array($data)) {
                if ($data['date'] + $data['maxAge'] > time()) {
                    $email = $data['email'];
                    if (self::getUserID($providerID, $email) === null) {
                        return self::createUser($providerID, $email, $data['password']);
                    }
                } else {
                    self::deleteSignupKey($providerID, $key);
                }
            }
        }
        return null;
    }

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return void
     */
    static function deleteSignupKey(string $providerID, string $key): void
    {
        if ($key[0] === 's') {
            $app = App::get();
            $app->users->deleteTempData($providerID, $key);
        }
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @param integer $maxAge
     * @return string
     */
    static function generatePasswordResetKey(string $providerID, string $userID, int $maxAge = 86400): string
    {
        $app = App::get();
        for ($i = 0; $i < 100; $i++) {
            $key = 'p' . Utilities::generateKey(50);
            if ($app->users->getTempData($providerID, $key) === null) {
                $data = ['userID' => $userID, 'userVersion' => self::getUserDataVersion($providerID, $userID), 'date' => time(), 'maxAge' => $maxAge];
                $app->users->saveTempData($providerID, $key, $data);
                return $key;
            }
        }
        throw new \Exception('Too many retries!');
    }

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return string|null Returns the User ID of the key is valid
     */
    static function validatePasswordResetKey(string $providerID, string $key): ?string
    {
        if ($key[0] === 'p') {
            $app = App::get();
            $data = $app->users->getTempData($providerID, $key);
            if (is_array($data)) {
                if ($data['date'] + $data['maxAge'] > time() && $data['userVersion'] === self::getUserDataVersion($providerID, $data['userID'])) {
                    return $data['userID'];
                } else {
                    self::deletePasswordResetKey($providerID, $key);
                }
            }
        }
        return null;
    }

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return void
     */
    static function deletePasswordResetKey(string $providerID, string $key): void
    {
        if ($key[0] === 'p') {
            $app = App::get();
            $app->users->deleteTempData($providerID, $key);
        }
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @param string $email
     * @param integer $maxAge
     * @return string
     */
    static function generateChangeEmailKey(string $providerID, string $userID, string $email, int $maxAge = 86400): string
    {
        if (self::getUserID($providerID, $email) !== null) {
            throw new \Exception('The email is used (' . $email . ')');
        }
        $app = App::get();
        for ($i = 0; $i < 100; $i++) {
            $key = 'e' . Utilities::generateKey(46);
            if ($app->users->getTempData($providerID, $key) === null) {
                $data = ['userID' => $userID, 'userVersion' => self::getUserDataVersion($providerID, $userID), 'email' => $email, 'date' => time(), 'maxAge' => $maxAge];
                $app->users->saveTempData($providerID, $key, $data);
                return $key;
            }
        }
        throw new \Exception('Too many retries!');
    }

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return array|null
     */
    static function validateChangeEmailKey(string $providerID, string $key): ?array
    {
        if ($key[0] === 'e') {
            $app = App::get();
            $data = $app->users->getTempData($providerID, $key);
            if (is_array($data)) {
                if ($data['date'] + $data['maxAge'] > time() && $data['userVersion'] === self::getUserDataVersion($providerID, $data['userID'])) {
                    return [
                        'userID' => $data['userID'],
                        'email' => $data['email']
                    ];
                } else {
                    self::deleteChangeEmailKey($providerID, $key);
                }
            }
        }
        return null;
    }

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return void
     */
    static function deleteChangeEmailKey(string $providerID, string $key): void
    {
        if ($key[0] === 'e') {
            $app = App::get();
            $app->users->deleteTempData($providerID, $key);
        }
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @param string $email
     * @return void
     */
    static function setEmail(string $providerID, string $userID, string $email): void
    {
        $app = App::get();
        $userData = $app->users->getUserData($providerID, $userID);
        if ($userData !== null) {
            $userData['email'] = $email;
            $userData['version'] = Utilities::generateKey(15);
            $app->users->saveUserData($providerID, $userID, $userData);
            self::updateIndex($providerID, $userID);
        }
    }

    /**
     * s
     * @param string $providerID
     * @param string $email
     * @param string $password
     * @return string
     */
    static function create(string $providerID, string $email, string $password): string
    {
        return self::createUser($providerID, $email, self::hashPassword($password));
    }

    /**
     * 
     * @param string $providerID
     * @param string $email
     * @param string $passwordHash
     * @return string|null
     */
    static private function createUser(string $providerID, string $email, string $passwordHash): ?string
    {
        $app = App::get();
        if (self::getUserID($providerID, $email) !== null) {
            throw new \Exception('The email is used (' . $email . ')');
        }
        $userID = null;
        for ($i = 0; $i < 100; $i++) {
            $tempUserID = Utilities::generateKey(rand(10, 15));
            if ($app->users->getUserData($providerID, $tempUserID) === null) {
                $userID = $tempUserID;
                break;
            }
        }
        if ($userID === null) {
            throw new \Exception('Too many retries!');
        }
        $app->users->saveUserData($providerID, $userID, [
            'email' => $email,
            'registerDate' => time(),
            'password' => $passwordHash,
            'version' => Utilities::generateKey(15)
        ]);
        self::updateIndex($providerID, $userID);
        $app->users->dispatchSignupEvent($providerID, $userID);
        return $userID;
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @return string|null
     */
    static function getEmail(string $providerID, string $userID): ?string
    {
        $app = App::get();
        $userData = $app->users->getUserData($providerID, $userID);
        if ($userData !== null && isset($userData['email'])) {
            return $userData['email'];
        }
        return null;
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @return string|null
     */
    static function getUserDataVersion(string $providerID, string $userID): ?string
    {
        $app = App::get();
        $userData = $app->users->getUserData($providerID, $userID);
        if ($userData !== null && isset($userData['version'])) {
            return $userData['version'];
        }
        return null;
    }

    /**
     * 
     * @param string $providerID
     * @param string $email
     * @param string $password
     * @return string|null Returns the user ID if password is valid
     */
    static function checkEmailPassword(string $providerID, string $email, string $password): ?string
    {
        $userID = self::getUserID($providerID, $email);
        if ($userID === null) {
            return null;
        }
        if (self::checkPassword($providerID, $userID, $password)) {
            return $userID;
        }
        return null;
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @param string $password
     * @return string|null
     */
    static function checkPassword(string $providerID, string $userID, string $password): ?string
    {
        $app = App::get();
        $userData = $app->users->getUserData($providerID, $userID);
        if ($userData !== null && password_verify($password, $userData['password'])) {
            return $userID;
        }
        return null;
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @param string $password
     * @return void
     */
    static function setPassword(string $providerID, string $userID, string $password): void
    {
        $app = App::get();
        $userData = $app->users->getUserData($providerID, $userID);
        if ($userData !== null) {
            $userData['password'] = self::hashPassword($password);
            $userData['version'] = Utilities::generateKey(15);
            $app->users->saveUserData($providerID, $userID, $userData);
            self::updateIndex($providerID, $userID);
        }
    }

    /**
     * 
     * @param string $password
     * @return string
     */
    static private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 
     * @param string $providerID
     * @param string $email
     * @return string|null
     */
    static function getUserID(string $providerID, string $email): ?string
    {
        $app = App::get();
        $list = $app->dataIndex->getList(self::getDataIndexKey($providerID));
        foreach ($list as $item) {
            if (isset($item->e) && $email === $item->e) {
                return $item->__key;
            }
        }
        return null;
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @return void
     */
    static function delete(string $providerID, string $userID): void
    {
        $app = App::get();
        $app->users->deleteUserData($providerID, $userID);
        self::updateIndex($providerID, $userID);
    }

    /**
     * 
     * @param string $providerID
     * @return string
     */
    static private function getDataIndexKey(string $providerID): string
    {
        return 'ivopetkov-users-email-' . $providerID;
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @return void
     */
    static private function updateIndex(string $providerID, string $userID): void
    {
        $app = App::get();
        $dataIndexKey = self::getDataIndexKey($providerID);
        $userData = $app->users->getUserData($providerID, $userID);
        if ($userData !== null) {
            $app->dataIndex->set($dataIndexKey, $userID, ['e' => $userData['email']]);
        } else {
            $app->dataIndex->delete($dataIndexKey, $userID);
        }
    }

    /**
     * 
     * @param string $email
     * @param string $key
     * @return void
     */
    static function sendSignupConfirmEmail(string $providerID, string $email, string $key): void
    {
        $app = App::get();
        $url = Utilities::getCallbackURL($providerID, $key);
        $host = $app->request->host;
        $subject = sprintf(__('ivopetkov.users.email.emails.signUp.subject'), $host);
        $htmlContent = sprintf(__('ivopetkov.users.email.emails.signUp.content.html'), $url);
        $textContent = sprintf(__('ivopetkov.users.email.emails.signUp.content.text'), $url);
        self::sendEmail($providerID, $email, $subject, $htmlContent, $textContent);
    }

    /**
     * 
     * @param string $email
     * @param string $key
     * @return void
     */
    static function sendPasswordResetEmail(string $providerID, string $email, string $key): void
    {
        $app = App::get();
        $url = Utilities::getCallbackURL($providerID, $key);
        $host = $app->request->host;
        $subject = sprintf(__('ivopetkov.users.email.emails.passwordReset.subject'), $host);
        $htmlContent = sprintf(__('ivopetkov.users.email.emails.passwordReset.content.html'), $url);
        $textContent = sprintf(__('ivopetkov.users.email.emails.passwordReset.content.text'), $url);
        self::sendEmail($providerID, $email, $subject, $htmlContent, $textContent);
    }

    /**
     * 
     * @param string $email
     * @param string $key
     * @return void
     */
    static function sendChangeEmailEmail(string $providerID, string $email, string $key): void
    {
        $app = App::get();
        $url = Utilities::getCallbackURL($providerID, $key);
        $host = $app->request->host;
        $subject = sprintf(__('ivopetkov.users.email.emails.changeEmail.subject'), $host);
        $htmlContent = sprintf(__('ivopetkov.users.email.emails.changeEmail.content.html'), $url);
        $textContent = sprintf(__('ivopetkov.users.email.emails.changeEmail.content.text'), $url);
        self::sendEmail($providerID, $email, $subject, $htmlContent, $textContent);
    }

    /**
     * 
     * @param string $providerID
     * @param string $recipient
     * @param string $subject
     * @param string $htmlContent
     * @param string $textContent
     * @return void
     */
    static function sendEmail(string $providerID, string $recipient, string $subject, string $htmlContent, ?string $textContent = null): void
    {
        $app = App::get();

        $provider = $app->users->getProvider($providerID);

        $email = $app->emails->make();
        $email->sender->email = $provider->options['senderEmail'];
        $email->sender->name = $provider->options['senderName'];
        $email->subject = $subject;
        $email->content->add($htmlContent, 'text/html');
        $email->content->add($textContent !== null ? $textContent : strip_tags($htmlContent), 'text/plain', 'utf-8');
        $email->recipients->add($recipient);
        if (isset($provider->options['senderReturnPath'])) {
            $email->returnPath = $provider->options['senderReturnPath'];
        }
        $eventDetails = $app->users->dispatchBeforeSendEmailEvent($providerID, $email);
        if ($eventDetails->preventDefault) {
            return;
        }
        $app->emails->send($email);
        $app->users->dispatchSendEmailEvent($providerID, $email);
    }

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return array|null
     */
    public function handleCallback(string $providerID, string $key): ?array
    {
        $app = App::get();

        $provider = $app->users->getProvider($providerID);
        if (isset($provider->options['getCallbackURL'])) {
            $url = call_user_func($provider->options['getCallbackURL']);
        } else {
            $url = $app->urls->get('/');
        }
        $url .= '#' . Utilities::$providerRoutePrefix . $providerID . '/';

        if ($key[0] === 's') { // sign up
            $userID = self::confirmSignupKey($providerID, $key);
            return ['redirectURL' => $url . 's:' . ($userID !== null ? '1' : '0')];
        } elseif ($key[0] === 'p') { // password reset
            $userID = self::validatePasswordResetKey($providerID, $key);
            return ['redirectURL' => $url . 'p:' . ($userID !== null ? $key : '')];
        } elseif ($key[0] === 'e') { // change email
            $result = self::validateChangeEmailKey($providerID, $key);
            if (is_array($result)) {
                $userID = $result['userID'];
                $email = $result['email'];
                self::setEmail($providerID, $userID, $email);
            } else {
                $userID = null;
            }
            self::deleteChangeEmailKey($providerID, $key);
            return ['redirectURL' => $url . 'e:' . ($userID !== null ? '1' : '0')];
        }
        return null;
    }
}
