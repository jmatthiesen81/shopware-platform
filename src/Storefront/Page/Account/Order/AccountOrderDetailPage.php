<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Account\Order;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Page\Page;

#[Package('checkout')]
class AccountOrderDetailPage extends Page
{
    protected OrderEntity $order;

    protected ?OrderLineItemCollection $lineItems = null;

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function setOrder(OrderEntity $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getLineItems(): ?OrderLineItemCollection
    {
        return $this->lineItems;
    }

    public function setLineItems(?OrderLineItemCollection $lineItems): self
    {
        $this->lineItems = $lineItems;

        return $this;
    }
}
