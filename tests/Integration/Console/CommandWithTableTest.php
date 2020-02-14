<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;

class CommandWithTableTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->mockConsoleOutput = true;
    }

    public function testItCanExecuteCommandWithTable()
    {
        $this->mockConsoleOutput = false;

        $this->artisan('command:table');
    }

    public function testItCantExecuteCommandWithTableAndAppendRowDueToBufferedOutput()
    {
        $this->mockConsoleOutput = false;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Output should be an instance of "Symfony\Component\Console\Output\ConsoleSectionOutput" when calling "Symfony\Component\Console\Helper\Table::appendRow');

        $this->app[ConsoleKernel::class]->command('command:table-with-append', function () {
            $table = $this->table([
                'name',
            ], [
                ['Taylor Otwell'],
            ]);

            $table->appendRow(['Mohamed Said']);
        });

        $this->artisan('command:table-with-append');
    }

    public function testItCanExecuteMockedCommandWithTable()
    {
        $this->mockConsoleOutput = true;

        $this->artisan('command:table')
            ->assertExitCode(0);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app[ConsoleKernel::class]->command('command:table', function () {
            $this->table([
                'name',
            ], [
                ['Taylor Otwell'],
            ]);
        });
    }
}
