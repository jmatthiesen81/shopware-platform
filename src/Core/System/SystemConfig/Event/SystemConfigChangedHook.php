<?php declare(strict_types=1);

namespace Shopware\Core\System\SystemConfig\Event;

use Shopware\Core\Framework\App\AppEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Webhook\AclPrivilegeCollection;
use Shopware\Core\Framework\Webhook\Hookable;

#[Package('framework')]
/**
 * @final
 */
class SystemConfigChangedHook implements Hookable
{
    public const EVENT_NAME = 'app.config.changed';

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $appMapping
     */
    public function __construct(
        private readonly array $values,
        private readonly array $appMapping,
        public readonly ?string $salesChannelId = null
    ) {
    }

    public function getName(): string
    {
        return 'app.config.changed';
    }

    /**
     * @return array{changes: array<string>}
     */
    public function getWebhookPayload(?AppEntity $app = null): array
    {
        if ($app === null) {
            return [
                'changes' => array_keys($this->values),
                'salesChannelId' => $this->salesChannelId,
            ];
        }

        $values = [];

        foreach ($this->values as $key => $value) {
            if (str_starts_with($key, $app->getName() . '.')) {
                $values[] = $key;
            }
        }

        return [
            'changes' => $values,
            'salesChannelId' => $this->salesChannelId,
        ];
    }

    public function isAllowed(string $appId, AclPrivilegeCollection $permissions): bool
    {
        // Needs basic system_config.read permission
        if (!$permissions->isAllowed('system_config', 'read')) {
            return false;
        }

        $appName = $this->appMapping[$appId] ?? null;

        // When app doesn't exist
        if ($appName === null) {
            return false;
        }

        foreach ($this->values as $k => $v) {
            if (str_starts_with($k, $appName . '.')) {
                return true;
            }
        }

        return false;
    }
}
