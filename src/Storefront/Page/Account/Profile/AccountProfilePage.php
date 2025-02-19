<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Account\Profile;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Storefront\Page\Page;

#[Package('checkout')]
class AccountProfilePage extends Page
{
    protected SalutationCollection $salutations;

    public function getSalutations(): SalutationCollection
    {
        return $this->salutations;
    }

    public function setSalutations(SalutationCollection $salutations): void
    {
        $this->salutations = $salutations;
    }
}
