<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Contexts;

use Jeremeamia\Slack\Apps\Exception;

trait HasData
{
    /** @var array  */
    protected $data = [];

    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    private function setData(array $data): void
    {
        if (!empty($data)) {
            $data = array_filter($data, function ($value) {
                return $value !== null;
            });
        }

        $this->data = $data;
    }

    /**
     * Get a value from the data.
     *
     * @param string $key Key or dot-separated path to value in data.
     * @param bool $required Whether to throw an exception if the value is not set.
     * @return string|array|int|float|bool|null
     */
    public function get(string $key, bool $required = false)
    {
        $value = $this->getDeep(explode('.', $key), $this->data);
        if ($required && $value === null) {
            $class = static::class;
            throw new Exception("Missing required value from {$class}: \"{$key}\".");
        }

        return $value;
    }

    /**
     * @param string[] $keys
     * @param bool $required Whether to throw an exception if none of the values are set.
     * @return string|array|int|float|bool|null
     */
    public function getOneOf(array $keys, bool $required = false)
    {
        foreach ($keys as $key) {
            $value = $this->get($key);
            if ($value !== null) {
                return $value;
            }
        }

        if ($required) {
            $class = static::class;
            $list = implode(', ', array_map(function (string $key) {
                return "\"{$key}\"";
            }, $keys));

            throw new Exception("Missing required value from {$class}: one of {$list}.");
        }

        return null;
    }

    /**
     * @param array $keys
     * @param array $data
     * @return string|array|int|float|bool|null
     */
    private function getDeep(array $keys, array &$data)
    {
        // Try the first key segment.
        $key = array_shift($keys);
        $value = $data[$key] ?? null;
        if ($value === null) {
            return null;
        }

        // If no more key segments, then it's are done. Don't recurse.
        if (empty($keys)) {
            return $value;
        }

        // If there is nothing to recurse into, don't recurse.
        if (!is_array($value)) {
            return null;
        }

        // Recurse into the next layer of the data with the remaining key segments.
        return $this->getDeep($keys, $value);
    }

    /**
     * Get all data as an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
