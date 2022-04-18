<?php declare(strict_types=1);

namespace RexGraphQL;

use Closure;
use Exception;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use RexGraphQL\Type\UserType;
use function lcfirst;
use function method_exists;
use function preg_replace;
use function strtolower;

/**
 * Acts as a registry and factory for your types.
 * As simplistic as possible for the sake of clarity of this example.
 * Your own may be more dynamic (or even code-generated).
 */
final class Types
{
    /** @var array<string, Type> */
    private static array $types = [];

    public static function user(): callable {
        return self::get(UserType::class);
    }

    /**
     * @param class-string<Type> $classname
     * @return Closure(): Type
     */
    private static function get(string $classname): Closure {
        return static fn() => self::byClassName($classname);
    }

    /**
     * @param class-string<Type> $classname
     */
    private static function byClassName(string $classname): Type {
        $parts = explode('\\', $classname);

        $withoutTypePrefix = preg_replace('~Type$~', '', $parts[count($parts) - 1]);
        assert(is_string($withoutTypePrefix), 'regex is statically known to be correct');

        $cacheName = strtolower($withoutTypePrefix);

        if (!isset(self::$types[$cacheName])) {
            return self::$types[$cacheName] = new $classname();
        }

        return self::$types[$cacheName];
    }

    public static function byTypeName(string $shortName): Type {
        $cacheName = strtolower($shortName);
        $type = null;

        if (isset(self::$types[$cacheName])) {
            return self::$types[$cacheName];
        }

        $method = lcfirst($shortName);
        if (method_exists(self::class, $method)) {
            $type = self::{$method}();
        }

        if (!$type) {
            throw new Exception('Unknown graphql type: ' . $shortName);
        }

        return $type;
    }

    public static function boolean(): ScalarType {
        return Type::boolean();
    }

    public static function float(): ScalarType {
        return Type::float();
    }

    public static function id(): ScalarType {
        return Type::id();
    }

    public static function int(): ScalarType {
        return Type::int();
    }

    public static function string(): ScalarType {
        return Type::string();
    }
}
