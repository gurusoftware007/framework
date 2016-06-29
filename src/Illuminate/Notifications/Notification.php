<?php

namespace Illuminate\Notifications;

use Illuminate\Support\Str;

class Notification
{
    /**
     * Get the "level" of the notification.
     *
     * @return string
     */
    public function level()
    {
        return property_exists($this, 'level') ? $this->level : 'info';
    }

    /**
     * Get the subject of the notification.
     *
     * @return string
     */
    public function subject()
    {
        return property_exists($this, 'subject')
                        ? $this->subject
                        : Str::title(Str::snake(class_basename($this), ' '));
    }

    /**
     * Get the notification channel options data.
     *
     * @return array
     */
    public function options()
    {
        return property_exists($this, 'options')
                        ? $this->options
                        : [];
    }

    /**
     * Create a new message builder instance.
     *
     * @param  string  $line
     * @return \Illuminate\Notifications\MessageBuilder
     */
    protected function line($line)
    {
        return new MessageBuilder($line);
    }
}
