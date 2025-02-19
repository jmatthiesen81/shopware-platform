<?php declare(strict_types=1);

namespace Shopware\Core\System\CustomEntity\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - use CustomEntityException::notFound instead - reason:remove-exception
 */
#[Package('framework')]
class CustomEntityNotFoundException extends ShopwareHttpException
{
    public function __construct(string $customEntity)
    {
        parent::__construct(
            'Custom Entity "{{ entityName }}" does not exist.',
            ['entityName' => $customEntity]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__CUSTOM_ENTITY_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
