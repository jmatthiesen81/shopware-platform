<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Adapter;

use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Asset\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Twig\Node\Expression\AbstractExpression;
use Twig\Source;

#[Package('checkout')]
class AdapterException extends HttpException
{
    public const UNEXPECTED_TWIG_EXPRESSION = 'FRAMEWORK__UNEXPECTED_TWIG_EXPRESSION';
    public const MISSING_EXTENDING_TWIG_TEMPLATE = 'FRAMEWORK__MISSING_EXTENDING_TWIG_TEMPLATE';
    public const TEMPLATE_SCOPE_DEFINITION_ERROR = 'FRAMEWORK__TEMPLATE_SCOPE_DEFINITION_ERROR';
    public const TEMPLATE_SW_USE_SYNTAX_ERROR = 'FRAMEWORK__TEMPLATE_SW_USE_SYNTAX_ERROR';
    public const MISSING_DEPENDENCY_ERROR_CODE = 'FRAMEWORK__FILESYSTEM_ADAPTER_DEPENDENCY_MISSING';
    public const INVALID_TEMPLATE_SYNTAX = 'FRAMEWORK__INVALID_TEMPLATE_SYNTAX';
    public const REDIS_UNKNOWN_CONNECTION = 'FRAMEWORK__REDIS_UNKNOWN_CONNECTION';
    public const INVALID_ASSET_URL = 'FRAMEWORK__INVALID_ASSET_URL';
    final public const INVALID_ARGUMENT = 'FRAMEWORK__INVALID_ARGUMENT_EXCEPTION';
    final public const STRING_TEMPLATE_RENDERING_FAILED = 'FRAMEWORK__STRING_TEMPLATE_RENDERING_FAILED';

    public static function unexpectedTwigExpression(AbstractExpression $expression): self
    {
        return new self(
            Response::HTTP_NOT_ACCEPTABLE,
            self::UNEXPECTED_TWIG_EXPRESSION,
            'Unexpected Expression of type "{{ type }}".',
            [
                'type' => $expression::class,
            ]
        );
    }

    public static function missingExtendsTemplate(string $template): self
    {
        return new self(
            Response::HTTP_NOT_ACCEPTABLE,
            self::MISSING_EXTENDING_TWIG_TEMPLATE,
            'Template "{{ template }}" does not have an extending template.',
            [
                'template' => $template,
            ],
        );
    }

    public static function invalidTemplateScope(mixed $scope): self
    {
        return new self(
            Response::HTTP_NOT_ACCEPTABLE,
            self::TEMPLATE_SCOPE_DEFINITION_ERROR,
            'Template scope is wronly defined: {{ scope }}',
            [
                'scope' => $scope,
            ],
        );
    }

    public static function missingDependency(string $dependency): self
    {
        return new self(
            Response::HTTP_FAILED_DEPENDENCY,
            self::MISSING_DEPENDENCY_ERROR_CODE,
            'Missing dependency "{{ dependency }}". Check the suggested composer dependencies for version and install the package.',
            [
                'dependency' => $dependency,
            ],
        );
    }

    public static function invalidTemplateSyntax(string $message): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_TEMPLATE_SYNTAX,
            'Failed rendering Twig string template due syntax error: "{{ message }}"',
            ['message' => $message]
        );
    }

    public static function swUseSyntaxError(int $line, Source $context): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::TEMPLATE_SW_USE_SYNTAX_ERROR,
            'The template references in a "sw_use" statement must be a string.',
            ['line' => $line, 'context' => $context]
        );
    }

    public static function renderingTemplateFailed(string $message): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::STRING_TEMPLATE_RENDERING_FAILED,
            'Failed rendering string template using Twig: {{ message }}',
            ['message' => $message]
        );
    }

    public static function unknownRedisConnection(string $connectionName): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::REDIS_UNKNOWN_CONNECTION,
            'Can\'t provide connection "{{ connectionName }}", check if it\'s configured under shopware.redis.connections.',
            [
                'connectionName' => $connectionName,
            ],
        );
    }

    public static function invalidRedisConnectionDsn(string $connectionName): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::REDIS_UNKNOWN_CONNECTION,
            'shopware.redis.connections dsn of "%s" connection must be a string.',
            [
                'connectionName' => $connectionName,
            ],
        );
    }

    public static function invalidAssetUrl(InvalidArgumentException $previous): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::INVALID_ASSET_URL,
            'Invalid asset URL. Check the "APP_URL" environment variable. Error message: {{ message }}',
            [
                'message' => $previous->getMessage(),
            ],
            $previous
        );
    }

    public static function invalidArgument(string $message): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::INVALID_ARGUMENT,
            $message
        );
    }
}
