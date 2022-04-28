<?php

namespace RexGraphQL\Defaults;

use GraphQL\Type\Definition\ResolveInfo;
use RexGraphQL\RexGraphQL;
use RexGraphQL\RexGraphQLContext;
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
                'resolve' => static function ($root, $args, RexGraphQLContext $context, ResolveInfo $info) {
                    $context->protect();
                    return $args;
                }
            ],
        ];

        RexGraphQL::addQuery($meQuery);
    }
}