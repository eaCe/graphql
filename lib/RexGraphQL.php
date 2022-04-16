<?php

namespace RexGraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Server\StandardServer;

class RexGraphQL
{
    public static $route = '';
    public static $auth = null;

    /**
     * @return string
     */
    public static function getCurrentPath(): string {
        $url = parse_url($_SERVER['REQUEST_URI']);
        return $url['path'] ?? '';
    }

    /**
     * handle requests
     * @return void
     */
    public static function handleRoute() {
        if (mb_substr(self::getCurrentPath(), 1, mb_strlen(self::$route)) === self::$route) {

            /**
             * basic query
             */
            $queryType = new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'helloWorld' => [
                        'type' => Type::string(),
                        'args' => [
                            'name' => Type::nonNull(Type::string()),
                        ],
                        'resolve' => function ($rootValue, $args) {
                            return 'Hello ' . $args['name'] . '!';
                        }
                    ],
                ],
            ]);

            $schema = new Schema([
                'query' => $queryType,
            ]);

            $server = new StandardServer([
                'schema' => $schema,
            ]);

            header('Content-Type: application/json');
            $server->handleRequest();
            exit();
        }
    }

    private static function extractToken() {

    }

    public static function getURL() {
        return \rex::getServer() . self::$route;
    }
}