<?php declare(strict_types=1);

namespace Shopware\Core\Framework\App\Aggregate\CmsBlock;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 *
 * @extends EntityCollection<AppCmsBlockEntity>
 */
#[Package('discovery')]
class AppCmsBlockCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AppCmsBlockEntity::class;
    }
}
