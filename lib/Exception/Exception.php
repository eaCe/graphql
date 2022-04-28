<?php

namespace RexGraphQL\Exception;

use GraphQL\Error\ClientAware;

class Exception extends \Exception implements ClientAware
{
    /**
     * @var string
     */
    private string $categoryName;

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, string $categoryName = 'internal') {
        parent::__construct($message, $code, $previous);
        $this->categoryName = $categoryName;
    }

    public function isClientSafe(): bool {
        return true;
    }

    public function getCategory(): string {
        return $this->categoryName;
    }
}