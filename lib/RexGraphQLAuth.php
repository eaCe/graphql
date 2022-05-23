<?php

namespace RexGraphQL;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RexGraphQL\Exception\Exception;

class RexGraphQLAuth
{
    /**
     * login and retrieve a token or an error...
     * @return array
     * @throws \rex_exception
     * @throws \Throwable
     */
    public static function login(string $username, string $password) {
        $tableName = \rex::getTable('user');
        $login = new \rex_login();
        $loginQuery = 'SELECT * FROM ' . $tableName .
            ' WHERE status = 1 AND login = :login AND (login_tries < ' . \rex_backend_login::LOGIN_TRIES_1 .
            ' OR login_tries < ' . \rex_backend_login::LOGIN_TRIES_2 . ' AND lasttrydate < "' . \rex_sql::datetime(time() - \rex_backend_login::RELOGIN_DELAY_1) .
            '" OR lasttrydate < "' . \rex_sql::datetime(time() - \rex_backend_login::RELOGIN_DELAY_2) .
            '")';
        $login->setLoginQuery($loginQuery);
        $login->setLogin($username, $password, false);
        $loginCheck = $login->checkLogin();

        if ($loginCheck) {
            $user = $login->getUser();
            self::resetLoginTries($tableName, $username);

            return [
                'token' => self::getToken($user),
                'refresh_token' => self::getToken($user, true)
            ];
        }

        self::setLoginTries($tableName, $username);

        throw new Exception(\rex_i18n::msg('login_error'));
    }

    /**
     * reset login attempts
     * @param string $tableName
     * @param string $username
     * @return void
     * @throws \rex_sql_exception
     */
    private static function resetLoginTries(string $tableName, string $username): void {
        $dateTime = \rex_sql::datetime();
        $sql = \rex_sql::factory();
        $sql->setTable($tableName)
            ->setWhere('login = :login', [':login' => $username])
            ->setValue('login_tries', 0)
            ->setValue('lasttrydate', $dateTime)
            ->setValue('lastlogin', $dateTime)
            ->update();
    }

    /**
     * update login attempts
     * @param string $tableName
     * @param string $username
     * @return void
     * @throws Exception
     * @throws \rex_sql_exception
     */
    private static function setLoginTries(string $tableName, string $username): void {
        $sql = \rex_sql::factory();
        $sql->setQuery('SELECT login_tries FROM ' . $tableName . ' WHERE login=? LIMIT 1', [$username]);

        if ($sql->getRows() > 0) {
            $loginTries = $sql->getValue('login_tries');
            $sql->setQuery('UPDATE ' . $tableName . ' SET login_tries=login_tries+1,session_id="",lasttrydate=? WHERE login=? LIMIT 1', [\rex_sql::datetime(), $username]);
            if ($loginTries >= \rex_backend_login::LOGIN_TRIES_1 - 1) {
                $time = $loginTries < \rex_backend_login::LOGIN_TRIES_2 ? \rex_backend_login::RELOGIN_DELAY_1 : \rex_backend_login::RELOGIN_DELAY_2;
                $hours = floor($time / 3600);
                $minutes = floor(($time - ($hours * 3600)) / 60);
                $seconds = $time % 60;
                $formatted = ($hours ? $hours . 'h ' : '') . ($hours || $minutes ? $minutes . 'min ' : '') . $seconds . 's';
                throw new Exception(\rex_i18n::rawMsg('login_wait', $formatted));
            }
        }
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
     * refresh the token set
     * @param \rex_sql $user
     * @param string $refreshToken
     * @return array
     * @throws \rex_sql_exception
     * @throws Exception
     */
    public static function refreshTokenSet(\rex_sql $user, string $refreshToken): array {
        if (!self::checkToken($refreshToken)) {
            throw new Exception('Token expired');
        }

        return [
            'token' => self::getToken($user),
            'refresh_token' => self::getToken($user, true)
        ];
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
        $expire = $issuedAt->modify('+15 minutes')->getTimestamp();

        if ($refreshToken) {
            $expire = $issuedAt->modify('+7 days')->getTimestamp();
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
        try {
            $decodedToken = JWT::decode($token, new Key($secret, 'HS256'));
        }
        catch (\Firebase\JWT\ExpiredException $e) {
            http_response_code(401);
            die();
        }
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
        if (!isset($context->user) && $context->user === null) {
            throw new Exception(\rex_i18n::msg('logged_out'));
        }
    }

    /**
     * get the current user
     * @param $headers
     * @return \rex_user|void|null
     */
    public static function getContextUser($headers) {
        $authHeader = null;

        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        }
        elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }
        elseif (isset($headers['token'])) {
            $authHeader = $headers['token'];
        }
        elseif (isset($headers['Token'])) {
            $authHeader = $headers['Token'];
        }

        if ($authHeader) {
            $token = self::getAuthorizationBearerToken($authHeader);

            if (self::checkToken($token)) {
                return self::getUserFromToken($token) ?: null;
            }
        }
    }
}