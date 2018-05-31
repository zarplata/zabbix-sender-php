<?php

namespace Zarplata\Zabbix;

use Zarplata\Zabbix\Request\Packet as ZabbixPacket;
use Zarplata\Zabbix\Response as ZabbixResponse;
use Zarplata\Zabbix\Exception\ZabbixNetworkException;
use Zarplata\Zabbix\Exception\ZabbixResponseException;

class ZabbixSender
{
    /**
     *  Zabbix protocol header
     *
     *  @var string
     */
    private const HEADER = 'ZBXD';

    /**
     *  Zabbix protocol version
     *
     *  @var int
     */
    private const VERSION = 1;

    /**
     * Zabbix server response header length
     * https://www.zabbix.com/documentation/3.4/manual/appendix/protocols/header_datalen
     *
     * @var int
     */
    private const RESPONSE_HEADER_LENGTH = 13;

    /**
     *  @var string
     */
    private $serverAddress;

    /**
     *  @var int
     */
    private $serverPort;

    /**
     * @var ZabbixPacket
     */
    private $packet;

    public function __construct(
        string $serverAddress,
        int $serverPort=10051
    ) {
        $this->serverAddress = $serverAddress;
        $this->serverPort = $serverPort;
    }

    /**
     * Send packet of metrics to Zabbix server through network socket
     *
     *
     * @param ZabbixPacket $packet
     *
     * @return void
     *
     * @throws Exception
     * @throws ZabbixNetworkException
     */
    public function send(ZabbixPacket $packet)
    {
        $payload = $this->makePayload($packet);
        $payloadLength = strlen($payload);

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (!$socket) {
            throw new \Exception("can't create TCP socket");
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

        $this->checkResponse($socket);
    }

    /**
     * Make payload for Zabbix server with special Zabbix header
     * and datalen
     *
     * https://www.zabbix.com/documentation/3.4/manual/appendix/protocols/header_datalen
     */
    private function makePayload(ZabbixPacket $packet): string
    {
        $encodedPacket = json_encode($packet);

        return pack(
            "a4CPa*",
            self::HEADER,
            self::VERSION,
            strlen($encodedPacket),
            $encodedPacket
        );
    }

    /**
     * Check response from Zabbix server
     *
     * @param resource $socket
     *
     * @return void
     *
     * @throws ZabbixResponseException
     * @throws ZabbixNetworkException
     */
    private function checkResponse($socket)
    {
        $responseBuffer = "";
        $responseBufferLength = 2048;

        $bytesCount = socket_recv(
            $socket,
            $responseBuffer,
            $responseBufferLength,
            0
        );

        if (!$bytesCount) {
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

        switch (true) {
        case $response === null:
        case $response === false:
            throw new ZabbixResponseException(
                sprintf(
                    "can't decode zabbix server response %s, reason: %s",
                    $responseWithoutHeader,
                    json_last_error_msg()
                )
            );

        default:
            break;
        }

        $zabbixResponse = new ZabbixResponse($response);

        if (!$zabbixResponse->isSuccess()) {
            throw new ZabbixResponseException(
                'zabbix server returned non-successfull response'
            );
        }
    }
}
