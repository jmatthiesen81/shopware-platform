<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\Adapter\Cache\ReverseProxy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Adapter\Cache\ReverseProxy\AbstractReverseProxyGateway;
use Shopware\Core\Framework\Adapter\Cache\ReverseProxy\FastlyReverseProxyGateway;
use Shopware\Core\Framework\Adapter\Cache\ReverseProxy\ReverseProxyCache;
use Shopware\Core\Framework\Adapter\Cache\ReverseProxy\ReverseProxyCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
#[CoversClass(ReverseProxyCompilerPass::class)]
class ReverseProxyCompilerPassTest extends TestCase
{
    public function testFastlyReplaces(): void
    {
        $container = self::getContainer();

        $container->compile();

        static::assertTrue($container->has(ReverseProxyCache::class));
        static::assertTrue($container->has(FastlyReverseProxyGateway::class));

        /** @var DummyService $dummy */
        $dummy = $container->get(DummyService::class);
        static::assertInstanceOf(FastlyService::class, $dummy->get());
    }

    public function testFastlyReplacesAndPluginExtendsFastly(): void
    {
        $container = self::getContainer();

        $container->register(PluginService::class)
            ->setPublic(true)
            ->setDecoratedService(FastlyReverseProxyGateway::class);

        $container->compile();

        static::assertTrue($container->has(ReverseProxyCache::class));
        static::assertTrue($container->has(FastlyReverseProxyGateway::class));

        static::assertInstanceOf(PluginService::class, $container->get(FastlyReverseProxyGateway::class));

        /** @var DummyService $dummy */
        $dummy = $container->get(DummyService::class);
        static::assertInstanceOf(PluginService::class, $dummy->get());
    }

    public function testFastlyDisabled(): void
    {
        $container = self::getContainer();
        $container->setParameter('shopware.http_cache.reverse_proxy.fastly.enabled', false);

        $container->compile();

        static::assertTrue($container->has(ReverseProxyCache::class));
        static::assertTrue($container->has(FastlyReverseProxyGateway::class));

        /** @var DummyService $dummy */
        $dummy = $container->get(DummyService::class);
        static::assertInstanceOf(VarnishService::class, $dummy->get());
    }

    public function testReverseProxyDisabled(): void
    {
        $container = self::getContainer();
        $container->setParameter('shopware.http_cache.reverse_proxy.enabled', false);

        $container->compile();

        static::assertFalse($container->has(ReverseProxyCache::class));
        static::assertFalse($container->has(FastlyReverseProxyGateway::class));
        static::assertFalse($container->has(FastlyReverseProxyGateway::class));
    }

    public static function getContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->setParameter('shopware.http_cache.reverse_proxy.enabled', true);
        $container->setParameter('shopware.http_cache.reverse_proxy.fastly.enabled', true);

        $container
            ->register(ReverseProxyCache::class)
            ->setPublic(true);

        $container
            ->register(AbstractReverseProxyGateway::class, VarnishService::class)
            ->setPublic(true);

        $container
            ->register(FastlyReverseProxyGateway::class, FastlyService::class)
            ->setPublic(true);

        $container
            ->register(DummyService::class)
            ->setPublic(true)
            ->addArgument(new Reference(AbstractReverseProxyGateway::class, ContainerInterface::NULL_ON_INVALID_REFERENCE));

        $container->addCompilerPass(new ReverseProxyCompilerPass());

        return $container;
    }
}

/**
 * @internal
 */
class VarnishService
{
}

/**
 * @internal
 */
class FastlyService
{
}

/**
 * @internal
 */
class PluginService
{
}

/**
 * @internal
 */
class DummyService
{
    public function __construct(private readonly object $gateway)
    {
    }

    public function get(): object
    {
        return $this->gateway;
    }
}
