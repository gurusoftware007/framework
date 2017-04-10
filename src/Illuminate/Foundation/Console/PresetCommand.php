<?php

namespace Illuminate\Foundation\Console;

use InvalidArgumentException;
use Illuminate\Console\Command;
use Illuminate\Support\Traits\Macroable;

class PresetCommand extends Command
{
    use Macroable;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'preset { type : The preset type (fresh, bootstrap, react) }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Swap the front-end scaffolding for the application';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (static::hasMacro($this->argument('type'))) {
            return call_user_func(static::$macros[$this->argument('type')], $this);
        }

        if (! in_array($this->argument('type'), ['none', 'bootstrap', 'react'])) {
            throw new InvalidArgumentException('Invalid preset.');
        }

        return $this->{$this->argument('type')}();
    }

    /**
     * Install the "fresh" preset.
     *
     * @return void
     */
    protected function none()
    {
        Presets\None::install();

        $this->info('Frontend scaffolding removed successfully.');
    }

    /**
     * Install the "fresh" preset.
     *
     * @return void
     */
    protected function bootstrap()
    {
        Presets\Bootstrap::install();

        $this->info('Bootstrap scaffolding installed successfully.');
        $this->comment('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
    }

    /**
     * Install the "react" preset.
     *
     * @return void
     */
    public function react()
    {
        Presets\React::install();

        $this->info('React scaffolding installed successfully.');
        $this->comment('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
    }
}
