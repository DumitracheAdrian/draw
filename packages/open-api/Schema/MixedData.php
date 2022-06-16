<?php

namespace Draw\Component\OpenApi\Schema;

class MixedData
{
    /**
     * @var MixedData
     */
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public static function convert($value, $valueIsArray = false)
    {
        if (null === $value) {
            return null;
        }

        if ($valueIsArray && \is_array($value)) {
            foreach ($value as $key => $data) {
                $value[$key] = static::convert($data);
            }

            return $value;
        }

        if ($value instanceof self) {
            return $value;
        }

        return new static($value);
    }
}
