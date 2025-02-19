<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\TestCaseBase;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use Shopware\Core\Framework\Test\TestCacheClearer;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait CacheTestBehaviour
{
    #[Before]
    #[After]
    public function clearCacheData(): void
    {
        /** @var TestCacheClearer $cacheClearer */
        $cacheClearer = static::getContainer()->get(TestCacheClearer::class);
        $cacheClearer->clear();

        static::getContainer()
            ->get('services_resetter')
            ->reset();
    }

    abstract protected static function getContainer(): ContainerInterface;
}
