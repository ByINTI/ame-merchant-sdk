<?php

namespace AmeMerchant\Requests;

use AmeMerchant\Exceptions\AmeMerchantHttpException;
use AmeMerchant\Exceptions\AmeMerchantSdkException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Throwable;

/**
 * Class BaseRequest
 * @package AmeMerchant\Request
 */
abstract class BaseRequest
{
    /** @var Client $client */
    protected $client;

    /** @var bool */
    protected $debug = false;

    /** @var string[] */
    private $baseUri = [
        'homologation'  => 'https://api.hml.amedigital.com/api/',
        'production' => 'https://api.amedigital.com/api/'
    ];

    /**
     * BaseRequest constructor.
     * @param bool $production
     */
    public function __construct(bool $production = true)
    {
        $this->client = new Client([
            'base_uri' => $production ? $this->baseUri['production'] : $this->baseUri['homologation'],
            'headers'  => [
                'User-Agent'      => 'ByINTI/1.0',
                'Content-Type'    => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
            ],
        ]);
    }

    /**
     * Activates Guzzle debug for the current request
     *
     * @return $this
     */
    public function withDebug(): self
    {
        $this->debug = true;
        return $this;
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        try {
            $response = $this->client->send(
                $this->makeRequest(),
                [
                    'debug'           => $this->debug,
                    'timeout'         => 5.0,
                    'connect_timeout' => 5.0,
                ]
            );

            $responseArray = json_decode(
                $response->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            return $this->readResponse($responseArray);
        } catch (GuzzleException $e) {
            if ($errorResponse = $e->getResponse()) {
                $errorResponseArr = json_decode($errorResponse->getBody()->getContents(), true);

                throw new AmeMerchantHttpException(
                    $errorResponseArr['error'] ?? 'Fatal Error',
                    $errorResponseArr['error_description'] ?? $e->getMessage(),
                    $errorResponse->getStatusCode() ?? 500,
                    $e->getPrevious()
                );
            }

            throw new AmeMerchantSdkException(
                'Fatal Error',
                $e->getMessage(),
                500,
                $e->getPrevious()
            );
        } catch (Throwable $e) {
            throw new AmeMerchantSdkException(
                'Fatal Error',
                'An unexpected error ocurred.',
                500,
                $e->getPrevious()
            );
        }
    }

    /**
     * @return Request
     */
    abstract protected function makeRequest(): Request;

    /**
     * @param array $responseBody
     */
    abstract protected function readResponse(array $responseBody);
}
