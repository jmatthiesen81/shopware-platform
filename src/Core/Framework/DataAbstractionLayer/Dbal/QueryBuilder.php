<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Dbal;

use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Shopware\Core\Framework\Log\Package;

#[Package('framework')]
class QueryBuilder extends DBALQueryBuilder
{
    /**
     * @var array<string, string>
     */
    private array $states = [];

    /**
     * @var array<string, array{fromAlias: string, queryBuilder: self, joinCondition: string}>
     */
    private array $translationJoins = [];

    /**
     * @var array<string>
     */
    private array $selectParts = [];

    /**
     * @var array<string>
     */
    private array $oderByParts = [];

    private ?string $title = null;

    public function addState(string $state): void
    {
        $this->states[$state] = $state;
    }

    public function removeState(string $state): void
    {
        unset($this->states[$state]);
    }

    public function hasState(string $state): bool
    {
        return \in_array($state, $this->states, true);
    }

    /**
     * @return array<string, string>
     */
    public function getStates(): array
    {
        return $this->states;
    }

    public function addTranslationJoin(
        string $fromAlias,
        string $joinAlias,
        self $queryBuilder,
        string $joinCondition,
    ): void {
        $this->translationJoins[$joinAlias] = [
            'fromAlias' => $fromAlias,
            'queryBuilder' => $queryBuilder,
            'joinCondition' => $joinCondition,
        ];
    }

    public function getTranslationQueryBuilder(string $joinAlias): ?self
    {
        return $this->translationJoins[$joinAlias]['queryBuilder'] ?? null;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getSQL(): string
    {
        // Use a copy of this query builder to generate the SQL including the translation joins. This way calling this
        // getter does not have any side effects on the original instance.
        $query = clone $this;
        foreach ($this->translationJoins as $joinAlias => $translationJoin) {
            $query->leftJoin(
                $translationJoin['fromAlias'],
                '(' . $translationJoin['queryBuilder']->getSQL() . ')',
                $joinAlias,
                $translationJoin['joinCondition'],
            );
        }
        $sql = $query->getUnmodifiedSQL();

        if ($this->getTitle()) {
            $sql = '# ' . $this->title . \PHP_EOL . $sql;
        }

        return $sql;
    }

    /**
     * @internal
     * {@inheritdoc}
     */
    public function select(string ...$expressions): self
    {
        $this->selectParts = $expressions;

        return parent::select(...$expressions);
    }

    /**
     * @internal
     * {@inheritdoc}
     */
    public function addSelect(string $expression, string ...$expressions): self
    {
        $this->selectParts = array_merge($this->selectParts, [$expression], $expressions);

        return parent::addSelect($expression, ...$expressions);
    }

    /**
     * @internal
     * {@inheritdoc}
     */
    public function orderBy(string $sort, ?string $order = null): self
    {
        $this->oderByParts = [$sort . ' ' . ($order ?? 'ASC')];

        return parent::orderBy($sort, $order);
    }

    /**
     * @internal
     * {@inheritdoc}
     */
    public function addOrderBy(string $sort, ?string $order = null): self
    {
        $this->oderByParts[] = $sort . ' ' . ($order ?? 'ASC');

        return parent::addOrderBy($sort, $order);
    }

    /**
     * This method is a hacky way to fix deprecations in the Doctrine DBAL QueryBuilder. It's usage is strongly discouraged.
     *
     * @internal
     *
     * @return array<string>
     */
    public function getSelectParts(): array
    {
        return $this->selectParts;
    }

    /**
     * This method is a hacky way to fix deprecations in the Doctrine DBAL QueryBuilder. It's usage is strongly discouraged.
     *
     * @return array<string>
     *
     *@internal
     */
    public function getOrderByParts(): array
    {
        return $this->oderByParts;
    }

    /**
     * A helper function allowing to get the SQL without applying translation joins. This is necessary for preventing
     * infinite recursion in {@link self::getSQL()}.
     */
    private function getUnmodifiedSQL(): string
    {
        return parent::getSQL();
    }
}
