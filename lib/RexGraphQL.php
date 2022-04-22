<?php

namespace RexGraphQL;

use GraphQL\Error\DebugFlag;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use GraphQL\Server\StandardServer;

class RexGraphQL
{
    public static string $route = '';
    public static $auth = null;
    public static array $queries = [];
    public static array $mutations = [];

    /**
     * add query to queries array
     * @param array $query
     */
    public static function addQuery(array $query): void {
        self::$queries = array_merge(self::$queries, $query);
    }

    /**
     * add mutation to mutations array
     * @param array $mutation
     */
    public static function addMutation(array $mutation): void {
        self::$mutations = array_merge(self::$mutations, $mutation);
    }

    /**
     * get current url path
     * @return string
     */
    public static function getCurrentPath(): string {
        $url = parse_url($_SERVER['REQUEST_URI']);
        return $url['path'] ?? '';
    }

    /**
     * handle graphql requests
     * @return void
     */
    public static function handleRoute() {
        if (mb_substr(self::getCurrentPath(), 1, mb_strlen(self::$route)) === self::$route) {
                $context = new RexGraphQLContext();
                $context->rootUrl = \rex::getServer();
                $context->headers = apache_request_headers();
                $context->request = $_REQUEST;
                $context->user = RexGraphQLAuth::getContextUser($context->headers);

                $schema = new Schema(self::getSchemaDefinition());

                $server = new StandardServer([
                    'schema' => $schema,
                    'context' => $context,
                    'debugFlag' => DebugFlag::INCLUDE_DEBUG_MESSAGE,
                ]);

                header('Content-Type: application/json');
                $server->handleRequest();
                exit();
        }
    }

    /**
     * get the schema
     * @return array
     */
    private static function getSchemaDefinition(): array {
        $schemaDefinition = [];

        $query = new ObjectType([
            'name' => 'Query',
            'fields' => self::$queries,
            'resolveField' => function ($root, $args) { return $args; },
        ]);

        $mutation = new ObjectType([
            'name' => 'Mutation',
            'fields' => self::$mutations,
            'resolveField' => function ($root, $args) { return $args; },
        ]);

        if (!empty(self::$mutations)) {
            $schemaDefinition['mutation'] = $mutation;
        }

        if (!empty(self::$queries)) {
            $schemaDefinition['query'] = $query;
        }

        return $schemaDefinition;
    }

    /**
     * get the absolute graphql url
     * @return string
     */
    public static function getURL(): string {
        return \rex::getServer() . self::$route;
    }
}