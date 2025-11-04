<?php

namespace Zarplata\Zabbix;

use Zarplata\Zabbix\Request\Packet as ZabbixPacket;
use Zarplata\Zabbix\Response as ZabbixResponse;
use Zarplata\Zabbix\Exception\ZabbixNetworkException;
use Zarplata\Zabbix\Exception\ZabbixResponseException;
use Exception;
use Socket;

/** @phpstan-consistent-constructor */
class ZabbixSender
{
    /**
     * Instance instances array
     *
     * @var array<string, ZabbixSender>
     */
    protected static array $instances = [];

    /**
     *  Zabbix protocol header
     *
     * @var string
     */
    private const HEADER = 'ZBXD';

    /**
     *  Zabbix protocol version
     *
     * @var int
     */
    private const VERSION = 1;

    /**
     * Zabbix server response header length
     * https://www.zabbix.com/documentation/3.4/manual/appendix/protocols/header_datalen
     *
     * @var int
     */
    private const RESPONSE_HEADER_LENGTH = 13;

    private string $serverAddress;

    private int $serverPort;

    /**
     * Disable send operation
     */
    private bool $disable = false;

    /**
     * Create singleton object
     *
     * @param string $name Name of object
     */
    public static function instance(string $name = 'default'): ZabbixSender
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new static($name);
        }

        return self::$instances[$name];
    }

    public function __construct(
        string $serverAddress,
        int $serverPort=10051
    ) {
        $this->serverAddress = $serverAddress;
        $this->serverPort = $serverPort;
    }

    /**
     * Configure connection parameters to Zabbix server
     *
     * @param array<string, string|int|bool> $options Configuration options
     * @phpstan-param array{server_address?: string, server_port?: int, disable?: bool } $options
     *
     * @return ZabbixSender Configurated instance
     */
    public function configure(array $options = []): ZabbixSender
    {
        if (isset($options['server_address'])) {
            $this->serverAddress = $options['server_address'];
        }

        if (isset($options['server_port'])) {
            $this->serverPort = intval($options['server_port']);
        }

        if (isset($options['disable'])) {
            $this->disable = boolval($options['disable']);
        }

        return $this;
    }

    /**
     * Disable sender functionality. It may be necessary if you want
     * switch off send metrics but you don't want remove the code
     * from your project.
     */
    public function disable(): void {
        $this->disable = true;
    }

    /**
     * Enable sender functionality. This is reverse operation of `disable()`
     */
    public function enable(): void {
        $this->disable = false;
    }

    /**
     * Send packet of metrics to Zabbix server through network socket
     *
     * @throws Exception
     * @throws ZabbixNetworkException
     */
    public function send(ZabbixPacket $packet): ZabbixResponse|null
    {
        if ($this->disable) {
            return null;
        }

        $payload = $this->makePayload($packet);
        $payloadLength = strlen($payload);

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (!$socket) {
            throw new Exception("can't create TCP socket");
        }

        $socketConnected = socket_connect(
            $socket,
            $this->serverAddress,
            $this->serverPort
        );

        if (!$socketConnected) {
            throw new ZabbixNetworkException(
                sprintf(
                    "can't connect to %s:%d",
                    $this->serverAddress,
                    $this->serverPort
                )
            );
        }

        $bytesCount= socket_send(
            $socket,
            $payload,
            $payloadLength,
            0
        );

        switch (true) {
            case !$bytesCount:
                throw new ZabbixNetworkException(
                    sprintf(
                        "can't send %d bytes to zabbix server %s:%d",
                        $payloadLength,
                        $this->serverAddress,
                        $this->serverPort
                    )
                );

            case $bytesCount != $payloadLength:
                throw new ZabbixNetworkException(
                    sprintf(
                        "incorrect count of bytes %s sended, expected: %d",
                        $bytesCount,
                        $payloadLength
                    )
                );

            default:
                break;
        }

        return $this->checkResponse($socket);
    }

    /**
     * Make payload for Zabbix server with special Zabbix header
     * and datalen
     *
     * https://www.zabbix.com/documentation/current/en/manual/appendix/protocols/header_datalen
     */
    private function makePayload(ZabbixPacket $packet): string
    {
        $encodedPacket = json_encode($packet);
        if ($encodedPacket === false) {
            throw new Exception('Unable to decode JSON for: ' . $encodedPacket);
        }

        return self::zbxCreateHeader(strlen($encodedPacket)) . $encodedPacket;
    }

    /**
     * Zabbix Packet Header
     */
    public static function zbxCreateHeader(int $plain_data_size, int|null $compressed_data_size = null): string
    {
        $flags = self::VERSION;
        if ($compressed_data_size === null) {
            $datalen = $plain_data_size;
            $reserved = 0;
        } else {
            $flags |= 0x02;
            $datalen = $compressed_data_size;
            $reserved = $plain_data_size;
        }
        return self::HEADER . chr($flags) . pack("VV", $datalen, $reserved);
    }

    /**
     * Check response from Zabbix server
     *
     *
     * @throws ZabbixResponseException
     * @throws ZabbixNetworkException
     */
    private function checkResponse(Socket $socket): ZabbixResponse
    {
        $responseBuffer = "";
        $responseBufferLength = 2048;

        $bytesCount = socket_recv(
            $socket,
            $responseBuffer,
            $responseBufferLength,
            0
        );

        if (!$bytesCount || !is_string($responseBuffer)) {
            throw new ZabbixNetworkException(
                "can't receive response from socket"
            );
        }

        $responseWithoutHeader = substr(
            $responseBuffer,
            self::RESPONSE_HEADER_LENGTH
        );
        $response = json_decode(
            $responseWithoutHeader,
            true
        );

        if (!is_array($response)) {
            throw new ZabbixResponseException(
                sprintf(
                    "can't decode zabbix server response %s, reason: %s",
                    $responseWithoutHeader,
                    json_last_error_msg()
                )
            );
        }

        $zabbixResponse = new ZabbixResponse($response);

        if (!$zabbixResponse->isSuccess()) {
            throw new ZabbixResponseException(
                'zabbix server returned non-successfull response'
            );
        }

        return $zabbixResponse;
    }
}
