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
        $this->secret = \rex_config::get('graphql', 'key');
    }

    /**
     * login and retrieve a token or an error...
     * @return bool
     * @throws \rex_exception
     * @throws \Throwable
     */
    public function login() {
        $login = new \rex_login();
        $login->setLoginQuery('SELECT * FROM ' . \rex::getTable('user') . ' WHERE status = 1 AND login = :login');
        $login->setLogin($this->username, $this->password, false);
        $loginCheck = $login->checkLogin();

        if ($loginCheck) {
            $this->user = $login->getUser();
            return $this->getToken();
        }

        throw new \GraphQL\Error\UserError(\rex_i18n::msg('login_error'));
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
        $issuedAt = new \DateTimeImmutable();
        $expire = $issuedAt->modify('+10 minutes')->getTimestamp();

        if ($refreshToken) {
            $expire = $issuedAt->modify('+15 days')->getTimestamp();
        }

        $data = [
            'iat' => $issuedAt->getTimestamp(),
            'iss' => \rex::getServer(),
            'nbf' => $issuedAt->getTimestamp(),
            'exp' => $expire,
            'name' => $this->username,
        ];

        return JWT::encode($data, $key, 'HS256');
    }

    /**
     * check if the token is valid
     * @param string $token
     * @return bool
     */
    private function checkToken(string $token): bool {
        $token = JWT::encode($token, $this->secret, ['HS512']);
        $now = new \DateTimeImmutable();

        return !($token->iss !== rex::getServer() ||
            $token->nbf > $now->getTimestamp() ||
            $token->exp < $now->getTimestamp());
    }

    /**
     * Returns the authorization bearer token out of the given "Authorization" header field.
     * @param string $authorizationHeader The "Authorization" header field.
     * @return bool|string
     */
    public function getAuthorizationBearerToken(string $authorizationHeader) {
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