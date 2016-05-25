<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class DatabaseMigratorIntegrationTest extends PHPUnit_Framework_TestCase
{
    protected $db;

    /**
     * Bootstrap Eloquent.
     *
     * @return void
     */
    public function setUp()
    {
        $this->db = $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->setAsGlobal();

        $this->migrator = new Migrator(
            $repository = new DatabaseMigrationRepository($db->getDatabaseManager(), 'migrations'),
            $db->getDatabaseManager(),
            new Filesystem
        );

        $container = new Illuminate\Container\Container;
        $container->instance('db', $db->getDatabaseManager());
        Illuminate\Support\Facades\Facade::setFacadeApplication($container);

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }
    }

    public function tearDown()
    {
        Illuminate\Support\Facades\Facade::clearResolvedInstances();
        Illuminate\Support\Facades\Facade::setFacadeApplication(null);
    }

    public function testBasicMigrationOfSingleFolder()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
    }

    public function testMigrationsCanBeRolledBack()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->migrator->rollback([__DIR__.'/migrations/one']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
    }

    public function testMigrationsCanBeReset()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->migrator->reset([__DIR__.'/migrations/one']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
    }

    public function testNoErrorIsThrownWhenNoOutstandingMigrationsExist()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->migrator->run([__DIR__.'/migrations/one']);
    }

    public function testNoErrorIsThrownWhenNothingToRollback()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->migrator->rollback([__DIR__.'/migrations/one']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
        $this->migrator->rollback([__DIR__.'/migrations/one']);
    }

    public function testMigrationsCanRunAcrossMultiplePaths()
    {
        $this->migrator->run([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->assertTrue($this->db->schema()->hasTable('flights'));
    }

    public function testMigrationsCanBeRolledBackAcrossMultiplePaths()
    {
        $this->migrator->run([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->assertTrue($this->db->schema()->hasTable('flights'));
        $this->migrator->rollback([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
        $this->assertFalse($this->db->schema()->hasTable('flights'));
    }

    public function testMigrationsCanBeResetAcrossMultiplePaths()
    {
        $this->migrator->run([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->assertTrue($this->db->schema()->hasTable('flights'));
        $this->migrator->reset([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
        $this->assertFalse($this->db->schema()->hasTable('flights'));
    }
}
