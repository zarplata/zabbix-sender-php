<?php

namespace Zarplata\Zabbix;

use Zarplata\Zabbix\Exception\ZabbixResponseException;

/**
 * Response class - represents a Zabbix response.
 *
 */
class Response
{
    private const SUCCESS_RESPONSE = 'success';

    /*
     * @var string
     */
    private $responceStatus;

    /**
     * @var int
     */
    private $processedItems;

    /**
     * @var int
     */
    private $failedItems;

    /**
     * @var int
     */
    private $totalItems;

    /**
     * @var float
     */
    private $secondSpent;
    
    public function __construct(array $response)
    {
        $this->parseZabbixResponse($response);
    }

    public function isSuccess(): bool
    {
        return $this->responceStatus === self::SUCCESS_RESPONSE;
    }

    public function getProcessedCount(): int
    {
        return $this->processedItems;
    }

    public function getFailedCount(): int
    {
        return $this->failedItems;
    }

    public function getTotalCount(): int
    {
        return $this->totalItems;
    }

    /**
     * Parse array to Response class properties
     *
     * This method takes array of values through argument
     * check required fields - `response` and `info` and
     * trying to find information about processed items
     * to zabbix server through reqular expression.
     *
     * @param array $response
     *
     * @return void
     *
     * @throws ZabbixResponseException
     */
    private function parseZabbixResponse(array $response)
    {
        if (!isset($response['response'])) {
            throw new ZabbixResponseException(
                'invalid zabbix server response, missing `response` field'
            );
        }

        $this->responceStatus = $response['response'];

        if (!isset($response['info'])) {
            throw new ZabbixResponseException(
                'invalid zabbix server response, missing `info` field'
            );
        }

        $pattern = '/\w+: (\d+); \w+: (\d+); \w+: (\d+); [a-z ]+: (\d+\.\d+)/';
        $matches = [];

        $matched = preg_match(
            $pattern,
            $response['info'],
            $matches
        );

        switch (true) {
            case $matched === false:
                throw new ZabbixResponseException(
                    sprintf(
                        "can't decode info into values, preg_match error: %d",
                        preg_last_error()
                    )
                );

            case $matched === 0:
                throw new ZabbixResponseException(
                    sprintf(
                        "pattern '%s' didn't satisfy to subject '%s'",
                        $pattern,
                        $response['info']
                    )
                );

            default:
                break;
        }

        /*
         * $matches must contains the following values:
         *
         * $matches[0] - whole matched string for example:
         * processed: 2; failed: 0; total: 2; seconds spent: 0.000059
         *
         * $matches[1] - 2 (processed)
         * $matches[2] - 0 (failed)
         * $matches[3] - 2 (total)
         * $matches[4] - 0.000059 (secods spent)
         */
        $this->processedItems = intval($matches[1]);
        $this->failedItems = intval($matches[2]);
        $this->totalItems = intval($matches[3]);
        $this->secondSpent = floatval($matches[4]);
    }
}
