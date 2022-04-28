<?php declare(strict_types=1);

namespace RexGraphQL\Type;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use RexGraphQL\RexGraphQLContext;

class UserType extends ObjectType
{
    public function __construct() {
        parent::__construct([
            'name' => 'User',
            'fields' => [
                'id' => Type::id(),
                'name' => Type::string(),
                'description' => Type::string(),
                'login' => Type::string(),
                'email' => Type::string(),
                'status' => Type::string(),
                'admin' => Type::string(),
                'role' => Type::string(),
                'login_tries' => Type::string(),
            ],
            'resolveField' => function ($root, $args, RexGraphQLContext $context, ResolveInfo $info) {
                return $context->user->getValue($info->fieldName);
            },
        ]);
    }
}
