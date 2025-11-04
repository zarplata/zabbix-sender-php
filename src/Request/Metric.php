<?php

namespace Zarplata\Zabbix\Request;

/**
 * Metric class - represents a Zabbix item (key and value)
 */
class Metric implements \JsonSerializable
{
    private string $itemKey;

    private string|int|float $itemValue;

    private string $hostname;

    private int $timestamp;

    public function __construct(
        string $itemKey,
        string|int|float $itemValue,
        string|null $hostname = null,
        int|null $time = null
    ) {
        $this->itemKey = $itemKey;
        $this->itemValue = $itemValue;
        $this->hostname = $hostname ?? gethostname();
        $this->timestamp = $time ?? time();
    }

    /**
     * Add custom hostname to metric
     */
    public function withHostname(string $hostname): static
    {
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * Add custom timestamp to metric
     */
    public function withTimestamp(int $timestamp): static
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return array<string, string|int|float>
     * @phpstan-return array{host: string, key: string, value: string|int|float, clock: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'host' => $this->hostname,
            'key' => $this->itemKey,
            'value' => $this->itemValue,
            'clock' => $this->timestamp
        ];
    }
}
