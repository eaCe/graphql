<?php

namespace RexGraphQL;

use Firebase\JWT\JWT;

class RexGraphQLAuth
{
    private $username;
    private $password;
    private $secret;
    private $user;

    public function __construct(string $username, string $password) {
        $this->username = $username;
        $this->password = $password;
        $this->secret = rex_config::get('graphql', 'key');
    }

    /**
     * login and retrieve a token
     * @return bool
     * @throws \rex_exception
     */
    public function login() {
        $login = new \rex_login();
        $login->setLogin($this->username, $this->password, false);
        $loginCheck = $login->checkLogin();

        if ($loginCheck) {
            $this->user = $login->getUser();
            return $this->getJWT();
        }

        return $loginCheck;
    }

    /**
     * logout the logged in user...
     * @return void
     */
    public function logout(): void {
        $logout = new \rex_backend_login();
        $logout->setLogout(true);
        $logout->checkLogin();
    }

    /**
     * get the jwt for the logged in user
     * @param bool $refreshToken
     * @return string
     */
    private function getToken(bool $refreshToken = false): string {
        $key = $this->secret;
        $issuedAt = new DateTimeImmutable();
        $expire = $issuedAt->modify('+10 minutes')->getTimestamp();

        if ($refreshToken) {
            $expire = $issuedAt->modify('+15 days')->getTimestamp();
        }

        $data = [
            'iat' => $issuedAt->getTimestamp(),
            'iss' => rex::getServer(),
            'nbf' => $issuedAt->getTimestamp(),
            'exp' => $expire,
            'userName' => $this->username,
        ];

        return JWT::encode(
            $data,
            $key,
            'HS512'
        );
    }

    /**
     * check if the token is valid
     * @param string $token
     * @return bool
     */
    private function checkToken(string $token): bool {
        $token = JWT::decode($token, $this->secret, ['HS512']);
        $now = new DateTimeImmutable();

        return !($token->iss !== rex::getServer() ||
            $token->nbf > $now->getTimestamp() ||
            $token->exp < $now->getTimestamp());
    }

    /**
     * Returns the authorization bearer token out of the given "Authorization" header field.
     * @param string $authorizationHeader The "Authorization" header field.
     * @return bool|string
     */
    public function getAuthorizationBearerToken($authorizationHeader) {
        if ($authorizationHeader === '') {
            return false;
        }
        // parse "Authorization: Bearer <token>"
        $matches = null;
        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)
            === 1
            && isset($matches[1])
            && $matches[1] != ''
        ) {
            return $matches[1];
        }
        return false;
    }
}