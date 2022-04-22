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
                $tokenSet = RexGraphQLAuth::login($args['user'], $args['password']);

                if ($tokenSet) {
                    switch ($info->fieldName) {
                        case 'token':
                            return $tokenSet['token'];
                        case 'refresh_token':
                            return $tokenSet['refresh_token'];
                    }
                }
            },
        ]);
    }
}