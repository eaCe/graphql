<?php

namespace RexGraphQL\Defaults;

use GraphQL\Type\Definition\ResolveInfo;
use RexGraphQL\RexGraphQL;
use RexGraphQL\Types;

class Query
{
    /**
     * register default queries
     * @return void
     */
    public static function register(): void {
        /**
         * get currently authenticated user
         */
        $meQuery = [
            'me' => [
                'type' => Types::user(),
                'description' => 'Get the currently authenticated user',
                'resolveField' => function ($root, array $args, $context, ResolveInfo $info) {
                    return $args;
                }
            ],
        ];

        RexGraphQL::addQuery($meQuery);
    }
}