<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Checkout\Rule\Rule\LineItem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Rule\CartRuleScope;
use Shopware\Core\Checkout\Cart\Rule\LineItemRule;
use Shopware\Core\Checkout\Cart\Rule\LineItemScope;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(LineItemRule::class)]
class LineItemRuleTest extends TestCase
{
    public function testRuleMatch(): void
    {
        $rule = (new LineItemRule())
            ->assign(['identifiers' => ['A']]);

        $context = $this->createMock(SalesChannelContext::class);

        $lineItem = new LineItem('A', 'product', 'A');

        static::assertTrue(
            $rule->match(new LineItemScope($lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($lineItem);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleNotMatch(): void
    {
        $rule = (new LineItemRule())
            ->assign(['identifiers' => ['A']]);

        $context = $this->createMock(SalesChannelContext::class);

        $lineItem = new LineItem('A', 'product', 'B');

        static::assertFalse(
            $rule->match(new LineItemScope($lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($lineItem);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }
}
