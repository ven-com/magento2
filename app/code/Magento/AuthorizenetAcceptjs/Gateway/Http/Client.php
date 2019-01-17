<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AuthorizenetAcceptjs\Gateway\Http;

use Magento\AuthorizenetAcceptjs\Gateway\Config;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Model\Method\Logger as PaymentLogger;
use Psr\Log\LoggerInterface;

/**
 * A client that can communicate with the Authorize.net API
 */
class Client implements ClientInterface
{
    /**
     * @var PaymentLogger
     */
    private $paymentLogger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ZendClientFactory
     */
    private $httpClientFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param PaymentLogger $paymentLogger
     * @param LoggerInterface $logger
     * @param ZendClientFactory $httpClientFactory
     * @param Config $config
     */
    public function __construct(
        PaymentLogger $paymentLogger,
        LoggerInterface $logger,
        ZendClientFactory $httpClientFactory,
        Config $config
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->config = $config;
        $this->paymentLogger = $paymentLogger;
        $this->logger = $logger;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param \Magento\Payment\Gateway\Http\TransferInterface $transferObject
     * @return array
     * @throws \Magento\Payment\Gateway\Http\ClientException
     */
    public function placeRequest(\Magento\Payment\Gateway\Http\TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();
        $log = [
            'request' => $request,
        ];
        $client = $this->httpClientFactory->create();
        $url = $this->config->getApiUrl();

        $type = $request['payload_type'];
        unset($request['payload_type']);
        $request = [$type => $request];

        try {
            $client->setUri($url);
            $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
            $client->setRawData(json_encode($request), 'application/json');
            $client->setMethod(ZendClient::POST);

            $responseBody = $client->request()->getBody();

            // Strip BOM because Authorize.net sends it in the response
            if ($responseBody && substr($responseBody, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
                $responseBody = substr($responseBody, 3);
            }

            $log['response'] = $responseBody;

            $data = json_decode($responseBody, true);

            if (json_last_error()) {
                throw new \Exception('Invalid JSON was returned by the gateway');
            }

            return $data;
        } catch (\Exception $e) {
            $this->logger->critical($e);

            throw new ClientException(
                __('Something went wrong in the payment gateway.')
            );
        } finally {
            $this->paymentLogger->debug($log);
        }
    }
}
