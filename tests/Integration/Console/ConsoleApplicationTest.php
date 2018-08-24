<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Command;
use Orchestra\Testbench\TestCase;
use Illuminate\Contracts\Console\Kernel;

class ConsoleApplicationTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->app[Kernel::class]->registerCommand(new FooCommandStub);
    }

    public function test_artisan_call_using_command_name()
    {
        $exitCode = $this->artisan('foo:bar', [
            'id' => 1,
        ])->expectsExitCode(0);
    }

    public function test_artisan_call_using_command_class()
    {
        $exitCode = $this->artisan(FooCommandStub::class, [
            'id' => 1,
        ])->expectsExitCode(0);
    }
}

class FooCommandStub extends Command
{
    protected $signature = 'foo:bar {id}';

    public function handle()
    {
        //
    }
}
