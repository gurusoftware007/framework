<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class WipeCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db:wipe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables, views & types';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $database = $this->input->getOption('database');

        if ($this->option('drop-views')) {
            $this->dropAllViews($database);

            $this->info('Dropped all views successfully.');
        }

        $this->dropAllTables($database);

        $this->info('Dropped all tables successfully.');

        if ($this->option('drop-types')) {
            $this->dropAllTypes($database);

            $this->info('Dropped all types successfully.');
        }
    }

    /**
     * Drop all of the database tables.
     *
     * @param  string  $database
     * @return void
     */
    protected function dropAllTables($database)
    {
        $this->laravel['db']->connection($database)
                    ->getSchemaBuilder()
                    ->dropAllTables();
    }

    /**
     * Drop all of the database views.
     *
     * @param  string  $database
     * @return void
     */
    protected function dropAllViews($database)
    {
        $this->laravel['db']->connection($database)
                    ->getSchemaBuilder()
                    ->dropAllViews();
    }

    /**
     * Drop all of the database types.
     *
     * @param string $database
     * @return void
     */
    protected function dropAllTypes($database)
    {
        $this->laravel['db']->connection($database)
                    ->getSchemaBuilder()
                    ->dropAllTypes();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['drop-views', null, InputOption::VALUE_NONE, 'Drop all tables and views'],
            ['drop-types', null, InputOption::VALUE_NONE, 'Drop all tables and types (Postgres only)'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
