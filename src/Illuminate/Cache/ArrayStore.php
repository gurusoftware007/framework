<?php

namespace Illuminate\Cache;

use Illuminate\Support\InteractsWithTime;

class ArrayStore extends TaggableStore
{
    use RetrievesMultipleKeys, InteractsWithTime;

    /**
     * The array of stored values.
     *
     * @var array
     */
    protected $storage = [];

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        if (! isset($this->storage[$key])) {
            return null;
        }

        $item = $this->storage[$key];
        if ($item['expiresAt'] !== 0 && $this->currentTime() > $item['expiresAt']) {
            $this->forget($key);

            return null;
        }

        return $item['value'];
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  float|int  $minutes
     * @return bool
     */
    public function put($key, $value, $minutes)
    {
        $this->storage[$key] = [
            'value' => $value,
            'expiresAt' => $this->calculateExpiration($minutes),
        ];

        return true;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        if (! isset($this->storage[$key])) {
            $this->forever($key, $value);

            return $this->storage[$key]['value'];
        }

        $this->storage[$key]['value'] = ((int) $this->storage[$key]['value']) + $value;

        return $this->storage[$key]['value'];
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        unset($this->storage[$key]);

        return true;
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        $this->storage = [];

        return true;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return '';
    }

    /**
     * Get the expiration time of the key.
     *
     * @param  int  $minutes
     * @return int
     */
    protected function calculateExpiration($minutes)
    {
        return $this->toTimestamp($minutes);
    }

    /**
     * Get the UNIX timestamp for the given number of minutes.
     *
     * @param  int  $minutes
     * @return int
     */
    protected function toTimestamp($minutes)
    {
        return $minutes > 0 ? $this->availableAt($minutes * 60) : 0;
    }
}
