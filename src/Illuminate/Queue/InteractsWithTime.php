<?php

namespace Illuminate\Queue;

use DateTime;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Carbon;

trait InteractsWithTime
{
    /**
     * Get the number of seconds until the given DateTime.
     *
     * @param  \DateTimeInterface|\DateInterval  $delay
     * @return int
     */
    protected function secondsUntil($delay)
    {
        $delay = $this->handlesInterval($delay);

        return $delay instanceof DateTimeInterface
                            ? max(0, $delay->getTimestamp() - $this->currentTime())
                            : (int) $delay;
    }

    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @return int
     */
    protected function availableAt($delay = 0)
    {
        $delay = $this->handlesInterval($delay);

        return $delay instanceof DateTimeInterface
                            ? $delay->getTimestamp()
                            : Carbon::now()->addSeconds($delay)->getTimestamp();
    }

    /**
     * Converts an interval to a DateTime instance.
     *
     * @param \DateTimeInterface|\DateInterval|int
     * @return \DateTime
     */
    protected function handlesInterval($delay)
    {
        if ($delay instanceof DateInterval) {
            $delay = (new DateTime)->add($delay);
        }

        return $delay;
    }

    /**
     * Get the current system time as a UNIX timestamp.
     *
     * @return int
     */
    protected function currentTime()
    {
        return Carbon::now()->getTimestamp();
    }
}
