<?php

namespace RexGraphQL\Defaults;

use GraphQL\Type\Definition\ResolveInfo;
use RexGraphQL\RexGraphQL;
use RexGraphQL\RexGraphQLAuth;
use RexGraphQL\Types;

class Mutation
{
    /**
     * register default mutations
     * @return void
     */
    public static function register(): void {
        /**
         * login an receive a JWT
         */
        $loginMutation = [
            'login' => [
                'type' => Types::string(),
                'description' => 'Login an receive a JWT Token',
                'args' => [
                    'user' => Types::string(),
                    'password' => Types::string(),
                ],
                'resolve' => function ($root, array $args, $context, ResolveInfo $info) {
                    return (new RexGraphQLAuth($args['user'], $args['password']))->login();
                }
            ],
        ];

        RexGraphQL::addMutation($loginMutation);
    }
}