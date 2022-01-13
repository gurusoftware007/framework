<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Doctrine\Instantiator\Exception\InvalidArgumentException;

trait InteractsWithDictionary
{
    /**
     * Get a dictionary key attribute - casting it to a string if necessary.
     *
     * @param  mixed  $attribute
     * @return mixed
     *
     * @throws \Doctrine\Instantiator\Exception\InvalidArgumentException
     */
    protected function getDictionaryKey($attribute)
    {
        if (is_object($attribute)) {
            if (method_exists($attribute, '__toString')) {
                return $attribute->__toString();
            }

            if (isset($attribute->value) && (is_string($attribute->value) || is_numeric($attribute->value))) {
                return $attribute->value;
            }

            $msg = 'Model attribute value is an object but does not have a __toString method '.
                'and does not implement \Illuminate\Contracts\Database\Eloquent\StringableAttribute interface.';
            throw new InvalidArgumentException($msg);
        }

        return $attribute;
    }
}
