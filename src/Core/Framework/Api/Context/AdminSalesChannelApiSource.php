<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Api\Context;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;

#[Package('framework')]
class AdminSalesChannelApiSource extends SalesChannelApiSource
{
    public string $type = 'admin-sales-channel-api';

    protected Context $originalContext;

    public function __construct(
        string $salesChannelId,
        Context $originalContext
    ) {
        parent::__construct($salesChannelId);

        $this->originalContext = $originalContext;
    }

    public function getOriginalContext(): Context
    {
        return $this->originalContext;
    }
}
