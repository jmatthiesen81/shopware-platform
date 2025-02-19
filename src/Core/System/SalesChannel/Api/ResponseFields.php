<?php declare(strict_types=1);

namespace Shopware\Core\System\SalesChannel\Api;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelException;

#[Package('framework')]
class ResponseFields
{
    /**
     * @param array<string, list<string>>|null $includes
     */
    public function __construct(protected ?array $includes = null)
    {
    }

    public function isAllowed(string $type, string $property): bool
    {
        if (!isset($this->includes[$type])) {
            return true;
        }

        if (!\is_array($this->includes[$type])) {
            throw SalesChannelException::invalidType(
                \sprintf(
                    'The includes for type "%s" must be of the type array, %s given',
                    $type,
                    \gettype($this->includes[$type])
                )
            );
        }

        return \in_array($property, $this->includes[$type], true);
    }

    public function hasNested(string $alias, string $prefix): bool
    {
        $fields = $this->includes[$alias] ?? [];

        $prefix .= '.';
        foreach ($fields as $property) {
            if (str_starts_with((string) $property, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
