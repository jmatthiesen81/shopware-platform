<?php declare(strict_types=1);

namespace Shopware\Core\Content\ProductStream;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingCollection;
use Shopware\Core\Content\ProductExport\ProductExportCollection;
use Shopware\Core\Content\ProductStream\Aggregate\ProductStreamFilter\ProductStreamFilterCollection;
use Shopware\Core\Content\ProductStream\Aggregate\ProductStreamTranslation\ProductStreamTranslationCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;

#[Package('inventory')]
class ProductStreamEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    protected ?string $name = null;

    protected ?string $description = null;

    /**
     * @var array<array<string, string|array<array<string, mixed>>>>|null
     */
    protected ?array $apiFilter = null;

    protected ?ProductStreamFilterCollection $filters = null;

    protected bool $invalid;

    protected ?ProductStreamTranslationCollection $translations = null;

    protected ?ProductExportCollection $productExports = null;

    protected ?ProductCrossSellingCollection $productCrossSellings = null;

    protected ?CategoryCollection $categories = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array<array<string, string|array<array<string, mixed>>>>|null
     */
    public function getApiFilter(): ?array
    {
        return $this->apiFilter;
    }

    /**
     * @param array<array<string, string|array<array<string, mixed>>>> $apiFilter
     */
    public function setApiFilter(?array $apiFilter): void
    {
        $this->apiFilter = $apiFilter;
    }

    public function getFilters(): ?ProductStreamFilterCollection
    {
        return $this->filters;
    }

    public function setFilters(ProductStreamFilterCollection $filters): void
    {
        $this->filters = $filters;
    }

    public function isInvalid(): bool
    {
        return $this->invalid;
    }

    public function setInvalid(bool $invalid): void
    {
        $this->invalid = $invalid;
    }

    public function getTranslations(): ?ProductStreamTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(ProductStreamTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getProductExports(): ?ProductExportCollection
    {
        return $this->productExports;
    }

    public function setProductExports(ProductExportCollection $productExports): void
    {
        $this->productExports = $productExports;
    }

    public function getProductCrossSellings(): ?ProductCrossSellingCollection
    {
        return $this->productCrossSellings;
    }

    public function setProductCrossSellings(ProductCrossSellingCollection $productCrossSellings): void
    {
        $this->productCrossSellings = $productCrossSellings;
    }

    public function getCategories(): ?CategoryCollection
    {
        return $this->categories;
    }

    public function setCategories(CategoryCollection $categories): void
    {
        $this->categories = $categories;
    }
}
