<?php declare(strict_types=1);

namespace Shopware\Tests\Migration\Core;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Shopware\Core\Framework\Migration\MigrationCollection;
use Shopware\Core\Framework\Migration\MigrationCollectionLoader;
use Shopware\Core\Framework\Migration\MigrationRuntime;
use Shopware\Core\Framework\Migration\MigrationSource;
use Shopware\Core\Framework\Test\Migration\_test_migrations_valid_run_time\Migration1;
use Shopware\Core\Framework\Test\Migration\MigrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @internal
 */
#[CoversClass(MigrationCollection::class)]
class MigrationCollectionRuntimeTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MigrationTestBehaviour;

    private Connection $connection;

    private MigrationCollection $validMigrationCollection;

    private MigrationCollection $exceptionMigrationCollection;

    protected function setUp(): void
    {
        $container = static::getContainer();

        $this->connection = $container->get(Connection::class);
        $loader = $container->get(MigrationCollectionLoader::class);

        $this->validMigrationCollection = $loader->collect('_test_migrations_valid_run_time');
        $this->exceptionMigrationCollection = $loader->collect('_test_migrations_valid_run_time_exceptions');

        $this->validMigrationCollection->sync();
        $this->exceptionMigrationCollection->sync();
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM `migration`
              WHERE `class` LIKE \'%_test_migrations_valid_run_time%\'
              OR `class` LIKE \'%_test_migrations_valid_run_time_exceptions%\''
        );
    }

    public function testMigrationExecutionInGeneral(): void
    {
        $cnt = 0;
        $generator = $this->validMigrationCollection->migrateInSteps();

        while ($generator->valid()) {
            $generator->next();
            ++$cnt;
        }

        static::assertSame(2, $cnt);
    }

    public function testItWorksWithASingleMigration(): void
    {
        $migrations = $this->getMigrations();
        static::assertNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);

        static::assertCount(1, $this->validMigrationCollection->migrateInPlace(null, 1));

        $migrations = $this->getMigrations();
        static::assertNotNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);
    }

    public function testItWorksWithMultipleMigrations(): void
    {
        $migrations = $this->getMigrations();
        static::assertNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);

        $this->validMigrationCollection->migrateInPlace();

        $migrations = $this->getMigrations();
        static::assertNotNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNotNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);
    }

    public function testItSkipsAlreadyExecutedMigrations(): void
    {
        $migrations = $this->getMigrations();
        static::assertNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);

        $this->validMigrationCollection->migrateInPlace();

        $migrations = $this->getMigrations();
        static::assertNotNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNotNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);

        $oldDate = $migrations[0]['update'];

        $this->validMigrationCollection->migrateInPlace();

        $migrations = $this->getMigrations();
        static::assertSame($oldDate, $migrations[0]['update']);
        static::assertNotNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNotNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);
    }

    public function testNoDestructiveIfNoNoneDestructive(): void
    {
        $migrations = $this->getMigrations();
        static::assertNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);

        $this->validMigrationCollection->migrateDestructiveInPlace();

        $migrations = $this->getMigrations();
        static::assertNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);
    }

    public function testDestructiveIfOneNoneDestructive(): void
    {
        $migrations = $this->getMigrations();
        static::assertNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);

        $this->validMigrationCollection->migrateInPlace(null, 1);

        $migrations = $this->getMigrations();
        static::assertNotNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);

        $this->validMigrationCollection->migrateDestructiveInPlace();

        $migrations = $this->getMigrations();
        static::assertNotNull($migrations[0]['update']);
        static::assertNotNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);
    }

    public function testDestructiveIfMultipleNoneDestructive(): void
    {
        $migrations = $this->getMigrations();
        static::assertNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);

        $this->validMigrationCollection->migrateInPlace();

        $migrations = $this->getMigrations();
        static::assertNotNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNotNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);

        $this->validMigrationCollection->migrateDestructiveInPlace();

        $migrations = $this->getMigrations();
        static::assertNotNull($migrations[0]['update']);
        static::assertNotNull($migrations[0]['update_destructive']);
        static::assertNotNull($migrations[1]['update']);
        static::assertNotNull($migrations[1]['update_destructive']);
    }

    public function testTimestampCap(): void
    {
        $migrations = $this->getMigrations();
        static::assertNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);

        $executedMigrations = $this->validMigrationCollection->migrateInPlace(1);

        $migrations = $this->getMigrations();
        static::assertSame([Migration1::class], $executedMigrations);
        static::assertNotNull($migrations[0]['update']);
        static::assertNull($migrations[0]['update_destructive']);
        static::assertNull($migrations[1]['update']);
        static::assertNull($migrations[1]['update_destructive']);
    }

    public function testExceptionHandling(): void
    {
        $this->validMigrationCollection->migrateInPlace();

        try {
            $this->exceptionMigrationCollection->migrateInPlace();
        } catch (\Exception) {
            // nth
        }

        $migrations = $this->getMigrations();
        static::assertNull($migrations[0]['message']);
        static::assertNotNull($migrations[0]['update']);
        static::assertSame('update', $migrations[3]['message']);
        static::assertNull($migrations[3]['update']);
    }

    public function testExceptionHandlingDestructive(): void
    {
        $this->validMigrationCollection->migrateInPlace();

        try {
            $this->exceptionMigrationCollection->migrateInPlace();
        } catch (\Exception) {
            // nth
        }

        $this->validMigrationCollection->migrateDestructiveInPlace();

        try {
            $this->exceptionMigrationCollection->migrateDestructiveInPlace();
        } catch (\Exception) {
            // nth
        }

        $migrations = $this->getMigrations();
        static::assertNull($migrations[0]['message']);
        static::assertNotNull($migrations[0]['update_destructive']);
        static::assertSame('update destructive', $migrations[2]['message']);
        static::assertNull($migrations[2]['update_destructive']);
        static::assertNull($migrations[3]['update_destructive']);
        static::assertSame('update', $migrations[3]['message']);
    }

    public function testIgnoringInvalidMigrations(): void
    {
        $logger = $this->createMock(Logger::class);

        $connection = $this->createMock(Connection::class);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('orderBy')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);

        $statement = $this->createMock(Result::class);
        $statement->method('fetchFirstColumn')->willReturn(['WrongClass']);

        $queryBuilder->method('executeQuery')->willReturn($statement);

        $connection
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $runtime = new MigrationRuntime($connection, $logger);

        /** @var MigrationSource $source */
        $source = static::getContainer()->get(MigrationSource::class . '.core');

        iterator_to_array($runtime->migrate($source), true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getMigrations(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('migration')
            ->where('`class` LIKE \'%_test_migrations_valid_run_time%\'
              OR `class` LIKE \'%_test_migrations_valid_run_time_exceptions%\'')
            ->orderBy('creation_timestamp', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
