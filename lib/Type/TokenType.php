<?php declare(strict_types=1);

namespace RexGraphQL\Type;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use RexGraphQL\RexGraphQLAuth;

class TokenType extends ObjectType
{
    public function __construct() {
        parent::__construct([
            'name' => 'Token',
            'fields' => [
                'token' => Type::string(),
                'refresh_token' => Type::string(),
            ],
            'resolveField' => function ($args, $root, $context, ResolveInfo $info) {
                switch ($info->fieldName) {
                    case 'token':
                        return $args['token'];
                    case 'refresh_token':
                        return $args['refresh_token'];
                }
            },
        ]);
    }
}