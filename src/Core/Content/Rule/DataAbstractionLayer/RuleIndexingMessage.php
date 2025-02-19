<?php declare(strict_types=1);

namespace Shopware\Core\Content\Rule\DataAbstractionLayer;

use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
class RuleIndexingMessage extends EntityIndexingMessage
{
}
