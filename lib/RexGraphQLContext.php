<?php

namespace RexGraphQL;

class RexGraphQLContext
{    public string $rootUrl;

    /** @var array<string, mixed> */
    public array $request;

    /** @var array<string, mixed> */
    public array $headers;

    /** @var \rex_user|null */
    public ?\rex_user $user;
}