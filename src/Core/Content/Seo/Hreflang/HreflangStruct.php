<?php declare(strict_types=1);

namespace Shopware\Core\Content\Seo\Hreflang;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('inventory')]
class HreflangStruct extends Struct
{
    protected string $url;

    protected string $locale;

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getApiAlias(): string
    {
        return 'seo_hreflang';
    }
}
