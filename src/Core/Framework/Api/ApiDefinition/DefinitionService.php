<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Api\ApiDefinition;

use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelDefinitionInstanceRegistry;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelDefinitionInterface;

/**
 * @phpstan-type Api DefinitionService::API|DefinitionService::STORE_API
 * @phpstan-type ApiType DefinitionService::TYPE_JSON_API|DefinitionService::TYPE_JSON
 * @phpstan-type OpenApiSpec  array{paths: array<string,array<mixed>>, components: array<mixed>}
 * @phpstan-type ApiSchema array<string, array{name: string, translatable: list<string>, properties: array<string, mixed>}|array{entity: string, properties: array<string, mixed>, write-protected: bool, read-protected: bool}>
 */
#[Package('framework')]
class DefinitionService
{
    final public const API = 'api';
    final public const STORE_API = 'store-api';

    final public const TYPE_JSON_API = 'jsonapi';

    final public const TYPE_JSON = 'json';

    /**
     * @var ApiDefinitionGeneratorInterface[]
     */
    private readonly array $generators;

    /**
     * @internal
     */
    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionRegistry,
        private readonly SalesChannelDefinitionInstanceRegistry $salesChannelDefinitionRegistry,
        ApiDefinitionGeneratorInterface ...$generators
    ) {
        $this->generators = $generators;
    }

    /**
     * @param Api $type
     * @param ApiType $apiType
     *
     * @return OpenApiSpec
     */
    public function generate(string $format = 'openapi-3', string $type = self::API, string $apiType = self::TYPE_JSON_API, ?string $bundleName = null): array
    {
        return $this->getGenerator($format, $type)->generate($this->getDefinitions($type), $type, $apiType, $bundleName);
    }

    /**
     * @param Api $type
     *
     * @return ApiSchema
     */
    public function getSchema(string $format = 'openapi-3', string $type = self::API): array
    {
        return $this->getGenerator($format, $type)->getSchema($this->getDefinitions($type));
    }

    /**
     * @return ApiType|null
     */
    public function toApiType(string $apiType): ?string
    {
        if ($apiType !== self::TYPE_JSON_API && $apiType !== self::TYPE_JSON) {
            return null;
        }

        return $apiType;
    }

    /**
     * @throws ApiDefinitionGeneratorNotFoundException
     */
    private function getGenerator(string $format, string $type): ApiDefinitionGeneratorInterface
    {
        foreach ($this->generators as $generator) {
            if ($generator->supports($format, $type)) {
                return $generator;
            }
        }

        throw new ApiDefinitionGeneratorNotFoundException($format);
    }

    /**
     * @throws ApiDefinitionGeneratorNotFoundException
     *
     * @return array<string, EntityDefinition>|array<string, EntityDefinition&SalesChannelDefinitionInterface>
     */
    private function getDefinitions(string $type): array
    {
        if ($type === self::API) {
            return $this->definitionRegistry->getDefinitions();
        }

        if ($type === self::STORE_API) {
            return $this->salesChannelDefinitionRegistry->getDefinitions();
        }

        throw new ApiDefinitionGeneratorNotFoundException($type);
    }
}
