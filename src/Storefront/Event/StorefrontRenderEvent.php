<?php declare(strict_types=1);

namespace Shopware\Storefront\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\NestedEvent;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('framework')]
class StorefrontRenderEvent extends NestedEvent implements ShopwareSalesChannelEvent
{
    /**
     * @var array<string, mixed>
     */
    protected array $parameters;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        protected string $view,
        array $parameters,
        protected Request $request,
        protected SalesChannelContext $context,
    ) {
        $this->parameters = array_merge(['context' => $context], $parameters);
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function setSalesChannelContext(SalesChannelContext $context): void
    {
        $this->context = $context;
    }

    public function getContext(): Context
    {
        return $this->context->getContext();
    }

    public function getView(): string
    {
        return $this->view;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param mixed $value
     */
    public function setParameter(string $key, $value): void
    {
        $this->parameters[$key] = $value;
    }
}
