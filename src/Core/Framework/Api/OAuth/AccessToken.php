<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Api\OAuth;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;
use Shopware\Core\Framework\Log\Package;

#[Package('framework')]
class AccessToken implements AccessTokenEntityInterface
{
    use AccessTokenTrait;
    use EntityTrait;
    use RefreshTokenTrait;

    /**
     * @internal
     *
     * @param ScopeEntityInterface[] $scopes
     * @param non-empty-string|null $userIdentifier
     */
    public function __construct(
        private ClientEntityInterface $client,
        private array $scopes,
        private ?string $userIdentifier = null
    ) {
    }

    public function getClient(): ClientEntityInterface
    {
        return $this->client;
    }

    /**
     * @return non-empty-string|null
     */
    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    /**
     * @return ScopeEntityInterface[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Set the identifier of the user associated with the token.
     *
     * @param non-empty-string $identifier The identifier of the user
     */
    public function setUserIdentifier(string $identifier): void
    {
        $this->userIdentifier = $identifier;
    }

    /**
     * Set the client that the token was issued to.
     */
    public function setClient(ClientEntityInterface $client): void
    {
        $this->client = $client;
    }

    /**
     * Associate a scope with the token.
     */
    public function addScope(ScopeEntityInterface $scope): void
    {
        $this->scopes[] = $scope;
    }

    public function initJwtConfiguration(): void
    {
        if (!$this->privateKey instanceof FakeCryptKey) {
            $jwtConfig = JWTConfigurationFactory::createJWTConfiguration();
            $this->privateKey = new FakeCryptKey($jwtConfig);
        }

        $this->jwtConfiguration = $this->privateKey->configuration;
    }
}
