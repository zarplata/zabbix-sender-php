<?php

namespace Zarplata\Zabbix\Request;

use Zarplata\Zabbix\Request\Metric as ZabbixMetric;

/**
 * Packet class - represents a set of Metrics
 */
class Packet implements \JsonSerializable
{
    /**
     * @var array<string, string|ZabbixMetric[]>
     * @phpstan-var array{request: string, data: ZabbixMetric[]}
     */
    private array $packet;

    public function __construct(string $request = 'sender data')
    {
        $this->packet = [
            'request' => $request,
            'data' => [],
        ];
    }

    public function addMetric(ZabbixMetric $metric): void
    {
        $this->packet['data'][] = $metric;
    }

    /**
     * @return array<string, string|ZabbixMetric[]>
     * @phpstan-return array{request: string, data: ZabbixMetric[]}
     */
    public function getPacket(): array
    {
        return $this->packet;
    }

    /**
     * @return array<string, string|ZabbixMetric[]>
     * @phpstan-return array{request: string, data: ZabbixMetric[]}
     */
    public function jsonSerialize(): array
    {
        return $this->packet;
    }
}
