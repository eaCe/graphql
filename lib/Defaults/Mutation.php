<?php

namespace RexGraphQL\Defaults;

use RexGraphQL\RexGraphQL;
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
                'description' => 'Login an receive a JWT Token',
                'args' => [
                    'user' => Type::nonNull(Types::string()),
                    'password' => Type::nonNull(Types::string()),
                ],
            ],
        ];

        RexGraphQL::addMutation($loginMutation);
    }
}