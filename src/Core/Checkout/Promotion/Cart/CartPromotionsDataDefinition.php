<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Promotion\Cart;

use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('checkout')]
class CartPromotionsDataDefinition extends Struct
{
    /**
     * @var array<string, array<PromotionEntity>>
     */
    private array $codePromotions;

    /**
     * @var array<PromotionEntity>
     */
    private array $automaticPromotions;

    public function __construct()
    {
        $this->codePromotions = [];
        $this->automaticPromotions = [];
    }

    /**
     * Adds a list of promotions to the existing list of automatic promotions.
     *
     * @param array<PromotionEntity> $promotions
     */
    public function addAutomaticPromotions(array $promotions): void
    {
        $this->automaticPromotions = array_merge($this->automaticPromotions, $promotions);
    }

    /**
     * Adds the provided list of promotions to the existing list of promotions for this code.
     *
     * @param string $code the promotion code
     * @param array<PromotionEntity> $promotions a list of promotion entities for this code
     */
    public function addCodePromotions(string $code, array $promotions): void
    {
        if (!\array_key_exists($code, $this->codePromotions)) {
            $this->codePromotions[$code] = $promotions;

            return;
        }

        $this->codePromotions[$code] = array_merge($this->codePromotions[$code], $promotions);
    }

    /**
     * Gets a list of all added automatic and code promotions.
     *
     * @return list<PromotionCodeTuple>
     */
    public function getPromotionCodeTuples(): array
    {
        $list = [];

        foreach ($this->automaticPromotions as $promotion) {
            $list[] = new PromotionCodeTuple('', $promotion);
        }

        foreach ($this->codePromotions as $code => $promotionList) {
            foreach ($promotionList as $promotion) {
                // Keep the string cast, as numeric codes will be implicitly cast to integer
                $list[] = new PromotionCodeTuple((string) $code, $promotion);
            }
        }

        return $list;
    }

    /**
     * Gets if there is at least an empty list of promotions available for the provided code.
     */
    public function hasCode(string $code): bool
    {
        return \array_key_exists($code, $this->codePromotions);
    }

    /**
     * Removes the assigned promotions for the provided code, if existing.
     */
    public function removeCode(string $code): void
    {
        if (!\array_key_exists($code, $this->codePromotions)) {
            return;
        }

        unset($this->codePromotions[$code]);
    }

    /**
     * Gets a flat list of all added codes.
     *
     * @return list<string>
     */
    public function getAllCodes(): array
    {
        return array_keys($this->codePromotions);
    }

    public function getApiAlias(): string
    {
        return 'cart_promotions_data_definition';
    }
}
