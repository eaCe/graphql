<?php

namespace RexGraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use GraphQL\Server\StandardServer;

class RexGraphQLResolver
{
    /**
     * @throws \rex_sql_exception
     */
    public static function rexSqlResolver($root, \rex_sql $sql, $context, ResolveInfo $info) {
        return $sql->getValue($info->fieldName);
    }

    public static function rexValueResolver($root, $rex, $context, ResolveInfo $info) {
        $rex->getValue($info->fieldName);
    }
}