<?php

namespace RexGraphQL;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class RexGraphQLAuth
{
    /**
     * login and retrieve a token or an error...
     * @return bool
     * @throws \rex_exception
     * @throws \Throwable
     */
    public static function login(string $username, string $password) {
        $secret = \rex_config::get('graphql', 'key');
        $login = new \rex_login();
        $login->setLoginQuery('SELECT * FROM ' . \rex::getTable('user') . ' WHERE status = 1 AND login = :login');
        $login->setLogin($username, $password, false);
        $loginCheck = $login->checkLogin();

        if ($loginCheck) {
            return self::getToken($login->getUser(), false);
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
     * @param \rex_sql $user
     * @param bool $refreshToken
     * @return string
     * @throws \rex_sql_exception
     */
    private static function getToken(\rex_sql $user, bool $refreshToken = false): string {
        $secret = \rex_config::get('graphql', 'key');
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
            'id' => $user->getValue('id'),
        ];

        return JWT::encode($data, $secret, 'HS256');
    }

    /**
     * check if the token is valid
     * @param string $token
     * @return bool
     */
    private static function checkToken(string $token): bool {
        $secret = \rex_config::get('graphql', 'key');
        $decodedToken = JWT::decode($token, new Key($secret, 'HS256'));
        $now = new \DateTimeImmutable();

        return !($decodedToken->iss !== \rex::getServer() ||
            $decodedToken->nbf > $now->getTimestamp() ||
            $decodedToken->exp < $now->getTimestamp());
    }

    /**
     * @param string $token
     * @return \rex_user|null
     */
    private static function getUserFromToken(string $token) {
        $secret = \rex_config::get('graphql', 'key');
        $decodedToken = JWT::decode($token, new Key($secret, 'HS256'));
        return \rex_user::get($decodedToken->id);
    }

    /**
     * Returns the authorization bearer token out of the given "Authorization" header field.
     * @param string $authorizationHeader The "Authorization" header field.
     * @return bool|string
     */
    private static function getAuthorizationBearerToken(string $authorizationHeader) {
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

    /**
     * check if a user is available
     * @param RexGraphQLContext $context
     * @return void
     * @throws \Exception
     */
    public static function protect(RexGraphQLContext $context): void {
        if(!isset($context->user) && $context->user === null) {
            throw new \Exception(\rex_i18n::msg('logged_out'));
        }
    }

    /**
     * get the current user
     * @param $headers
     * @return \rex_user|void|null
     */
    public static function getContextUser($headers) {
        if(isset($headers['Authorization'])) {
            $token = self::getAuthorizationBearerToken($headers['Authorization']);

            if(self::checkToken($token)) {
                return self::getUserFromToken($token) ?: null;
            }
        }
    }
}