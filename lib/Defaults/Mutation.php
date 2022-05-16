<?php

namespace RexGraphQL\Defaults;

use GraphQL\Type\Definition\ResolveInfo;
use RexGraphQL\RexGraphQL;
use RexGraphQL\RexGraphQLAuth;
use RexGraphQL\Types;
use GraphQL\Type\Definition\Type;

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
                'type' => Types::token(),
                'description' => 'Login and receive a JWT Token',
                'args' => [
                    'user' => Type::nonNull(Types::string()),
                    'password' => Type::nonNull(Types::string()),
                ],
                'resolve' => static function ($args, $root, $context, ResolveInfo $info) {
                    return RexGraphQLAuth::login($root['user'], $root['password']);
                },
            ],
        ];

        RexGraphQL::addMutation($loginMutation);
    }
}