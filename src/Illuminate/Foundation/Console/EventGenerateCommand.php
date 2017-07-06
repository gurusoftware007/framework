<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class EventGenerateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'event:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the missing events and listeners based on registration';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->makeDynamicListeners(
            $provider = $this->laravel->getProvider(EventServiceProvider::class)
        );

        foreach ($provider->listens() as $event => $listeners) {
            $this->makeEventAndListeners($event, $listeners);
        }

        $this->info('Events and listeners generated successfully!');
    }

    /**
     * Generate the dynamic listeners which have "hears" properties.
     *
     * @param  object  $provider
     * @return void
     */
    protected function makeDynamicListeners($provider)
    {
        foreach ($provider->listeners() as $listener) {
            if (! class_exists($listener)) {
                $this->makeListeners(null, [$listener]);

                continue;
            }

            foreach ($listener::$hears as $event) {
                $this->makeEventAndListeners($event, [$listener]);
            }
        }
    }

    /**
     * Make the event and listeners for the given event.
     *
     * @param  string  $event
     * @param  array  $listeners
     * @return void
     */
    protected function makeEventAndListeners($event, $listeners)
    {
        if (! Str::contains($event, '\\')) {
            return;
        }

        $this->callSilent('make:event', ['name' => $event]);

        $this->makeListeners($event, $listeners);
    }

    /**
     * Make the listeners for the given event.
     *
     * @param  string  $event
     * @param  array  $listeners
     * @return void
     */
    protected function makeListeners($event, $listeners)
    {
        foreach ($listeners as $listener) {
            $listener = preg_replace('/@.+$/', '', $listener);

            $this->callSilent('make:listener', array_filter(
                ['name' => $listener, '--event' => $event]
            ));
        }
    }
}
