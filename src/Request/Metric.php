<?php

namespace Zarplata\Zabbix\Request;

/**
 * Metric class - represents a Zabbix item (key and value)
 */
class Metric implements \JsonSerializable
{
    /**
     * @var string
     */
    private $itemKey;

    /**
     * @var string
     */
    private $itemValue;

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var int
     */
    private $timestamp;

    public function __construct(
        string $itemKey,
        string $itemValue
    ) {
        $this->itemKey = $itemKey;
        $this->itemValue = $itemValue;
        $this->hostname = gethostname();
        $this->timestamp = time();
    }

    /**
     * Add custom hostname to metric
     *
     * @param string $hostname
     */
    public function withHostname(string $hostname)
    {
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * Add custom timestamp to metric
     *
     * @param int $timestamp
     */
    public function withTimestamp(int $timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }
    
    public function jsonSerialize()
    {
        return [
            'host' => $this->hostname,
            'key' => $this->itemKey,
            'value' => $this->itemValue,
            'clock' => $this->timestamp
        ];
    }
}
