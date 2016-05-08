<?php

/**
 * @link https://github.com/borodulin/yii2-oauth2-server
 * @copyright Copyright (c) 2015 Andrey Borodulin
 * @license https://github.com/borodulin/yii2-oauth2-server/blob/master/LICENSE
 */

namespace app\components\oauth2\granttypes;

use app\models\User;
use app\components\oauth2\BaseModel;
use app\components\oauth2\models\AccessToken;
use app\components\oauth2\models\RefreshToken;
use app\components\oauth2\Exception;

/**
 *
 * @author Andrey Borodulin
 * @author comet(extends)
 */
class UserCredentials extends BaseModel {

    /**
     * @var User
     */
    private $_user;

    /**
     * Value MUST be set to "password".
     * @var string
     */
    public $grant_type;

    /**
     *
     * @var string
     */
    public $client_id;

    /**
     *
     * @var string
     */
    public $client_secret;

    /**
     *
     * @var string
     */
    public $username;

    /**
     *
     * @var string
     */
    public $password;

    /**
     * The scope of the access request as described by Section 3.3.
     * @var string
     */
    public $scope;

    public function rules() {
        return [
            [['grant_type', 'client_id', 'client_secret', 'username', 'password'], 'required'],
            [['client_id', 'client_secret'], 'string', 'max' => 80],
            [['username', 'password'], 'string', 'max' => 40],
            [['client_id'], 'validateClient_id'],
            [['client_secret'], 'validateClient_secret'],
            [['username'], 'validateUsername'],
            [['password'], 'validatePassword'],
        ];
    }

    function getResponseData() {
        $user = $this->getUser();

        $accessToken = AccessToken::createAccessToken([
            'client_id' => $this->client_id,
            'user_id' => $user->id,
            'expires' => $this->accessTokenLifetime + time(),
            'scope' => $this->getClient()->scope,
        ]);

        $refreshToken = RefreshToken::createRefreshToken([
            'client_id' => $this->client_id,
            'user_id' => $user->id,
            'expires' => $this->refreshTokenLifetime + time(),
            'scope' => $this->getClient()->scope,
        ]);

        return  [
            'access_token' => $accessToken->access_token,
            'expires_in' => $this->accessTokenLifetime,
            'token_type' => $this->tokenType,
            'scope' => $refreshToken->scope,
            'refresh_token' => $refreshToken->refresh_token,
        ];
    }

    public function validateUsername() {
        $this->getUser();
    }

    public function validatePassword($attribute) {
        if (!$this->getUser()->validatePassword($this->password)) {
            $this->addError($attribute, 'The client credentials are invalid');
        }
    }

    /**
     * @return User
     * @throws Exception
     */
    public function getUser() {
        if (is_null($this->_user)) {
            if (empty($this->username)) {
                $this->errorServer('The request is missing "username" parameter');
            }
            if (!$this->_user = User::findByUsername($this->username)) {
                $this->errorServer('The username is invalid');
            }
        }
        return $this->_user;
    }
}