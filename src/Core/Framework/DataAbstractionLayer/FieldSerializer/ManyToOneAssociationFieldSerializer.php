<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer;

use Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteCommandExtractor;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('framework')]
class ManyToOneAssociationFieldSerializer implements FieldSerializerInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly WriteCommandExtractor $writeExtractor)
    {
    }

    public function normalize(Field $field, array $data, WriteParameterBag $parameters): array
    {
        if (!$field instanceof ManyToOneAssociationField) {
            throw DataAbstractionLayerException::invalidSerializerField(ManyToOneAssociationField::class, $field);
        }

        $referenceField = $field->getReferenceDefinition()->getFields()->getByStorageName($field->getReferenceField());
        if ($referenceField === null) {
            throw DataAbstractionLayerException::definitionFieldDoesNotExist(
                $field->getReferenceDefinition()::class,
                $field->getReferenceField(),
            );
        }
        $key = $field->getPropertyName();
        $value = $data[$key] ?? null;
        if ($value === null) {
            return $data;
        }

        if (!\is_array($value)) {
            throw DataAbstractionLayerException::expectedArray($parameters->getPath() . '/' . $key);
        }

        $fkField = $parameters->getDefinition()->getFields()->getByStorageName($field->getStorageName());
        if ($fkField === null) {
            throw DataAbstractionLayerException::fkFieldByStorageNameNotFound(
                $parameters->getDefinition()::class,
                $field->getStorageName(),
            );
        }

        $isPrimary = $fkField->is(PrimaryKey::class);

        if (isset($value[$referenceField->getPropertyName()])) {
            $id = $value[$referenceField->getPropertyName()];

        // plugins can add a ManyToOne where they define that the local/storage column is the primary and the reference is the foreign key
        // in this case we have a reversed many to one association configuration
        } elseif ($isPrimary) {
            $id = $parameters->getContext()->get($parameters->getDefinition()->getEntityName(), $fkField->getPropertyName());
        } else {
            $id = Uuid::randomHex();
            $value[$referenceField->getPropertyName()] = $id;
        }

        $clonedParams = $parameters->cloneForSubresource(
            $field->getReferenceDefinition(),
            $parameters->getPath() . '/' . $key
        );

        $value = $this->writeExtractor->normalizeSingle($field->getReferenceDefinition(), $value, $clonedParams);

        // in case of a reversed many to one configuration we have to return nothing, otherwise the primary key would be overwritten
        if (!$isPrimary) {
            $data[$fkField->getPropertyName()] = $id;
        }

        $data[$key] = $value;

        return $data;
    }

    public function encode(
        Field $field,
        EntityExistence $existence,
        KeyValuePair $data,
        WriteParameterBag $parameters
    ): \Generator {
        if (!$field instanceof ManyToOneAssociationField) {
            throw DataAbstractionLayerException::invalidSerializerField(ManyToOneAssociationField::class, $field);
        }

        $value = $data->getValue();
        if (!\is_array($value) || !$this->isArrayAssociative($value)) {
            throw DataAbstractionLayerException::expectedAssociativeArray($parameters->getPath());
        }

        $this->writeExtractor->extract(
            $value,
            $parameters->cloneForSubresource(
                $field->getReferenceDefinition(),
                $parameters->getPath() . '/' . $data->getKey()
            )
        );

        yield from [];
    }

    public function decode(Field $field, mixed $value): never
    {
        throw DataAbstractionLayerException::decodeHandledByHydrator($field);
    }

    /**
     * @param array<array-key, mixed> $array
     */
    private function isArrayAssociative(array $array): bool
    {
        $isString = array_map(is_string(...), array_keys($array));

        return \count(array_filter($isString)) === \count($array);
    }
}
