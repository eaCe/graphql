<?php
require 'lib/vendor/autoload.php';

use RexGraphQL\Defaults\Mutation;
use RexGraphQL\Defaults\Query;
use RexGraphQL\RexGraphQL;

rex_extension::register('PACKAGES_INCLUDED', static function () {
    /**
     * set default route...
     */
    RexGraphQL::$route = rex_extension::registerPoint(new rex_extension_point('GRAPHQL_ROUTE', 'graphql'));

    /**
     * register default queries/mutations
     */
    Mutation::register();
    Query::register();

    if (!\rex::isBackend()) {
        RexGraphQL::handleRoute();
    }
});
