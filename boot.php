<?php

rex_extension::register('PACKAGES_INCLUDED', static function () {
    /**
     * set default route...
     */
    $route = rex_extension::registerPoint(new rex_extension_point('GRAPHQL_ROUTE', 'graphql'));
    \RexGraphQL\RexGraphQL::$route = $route;

    if (!\rex::isBackend()) {
        \RexGraphQL\RexGraphQL::handleRoute();
    }
});