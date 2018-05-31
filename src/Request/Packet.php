<?php

namespace Zarplata\Zabbix\Request;

use Zarplata\Zabbix\Request\Metric as ZabbixMetric;

/**
 * Packet class - represents a set of Metrics
 */
class Packet implements \JsonSerializable
{
    /**
     * @var array
     */
    private $packet = [];

    public function __construct(string $request='sender data')
    {
        $this->packet['request'] = $request;
    }

    public function addMetric(ZabbixMetric $metric)
    {
        $this->packet['data'][] = $metric;
    }

    public function getPacket(): array
    {
        return $this->packet;
    }

    public function jsonSerialize()
    {
        return $this->packet;
    }
}
