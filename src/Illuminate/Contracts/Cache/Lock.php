<?php

namespace Illuminate\Contracts\Cache;

interface Lock
{
    /**
     * Attempt to acquire the lock.
     *
     * @param  callable|null  $callback
     * @return bool
     */
    public function get($callback = null);

    /**
     * Attempt to acquire the lock for the given number of seconds.
     *
     * @param  int  $seconds
     * @param  callable|null  $callback
     * @return bool
     */
    public function block($seconds, $callback = null);

    /**
     * Release the lock.
     *
     * @return void
     */
    public function release();

    /**
     * Secures this lock against out of order releases of expired clients via assigning an owner.
     *
     * @return mixed
     */
    public function owned();
}
