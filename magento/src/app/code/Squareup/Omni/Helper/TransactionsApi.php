<?php

namespace Squareup\Omni\Helper;

use \SquareConnect\ApiClient;
use \SquareConnect\ApiException;

class TransactionsApi extends \SquareConnect\Api\TransactionsApi
{
    private $apiClient;

    public function __construct($apiClient = null)
    {
        parent::__construct($apiClient);

        if ($apiClient == null) {
            $apiClient = new ApiClient();
            $apiClient->getConfig()
                ->setHost('https://connect.squareup.com');
        }

        $this->apiClient = $apiClient;
    }

    public function chargeWithHttpInfo($location_id, $body)
    {
        // verify the required parameter 'location_id' is set
        if ($location_id === null) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $location_id when calling charge'
            );
        }
        // verify the required parameter 'body' is set
        if ($body === null) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $body when calling charge'
            );
        }

        // parse inputs
        $resourcePath = "/v2/locations/{location_id}/transactions";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = ApiClient::selectHeaderAccept(['application/json']);
        if ($_header_accept !== null) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = ApiClient::selectHeaderContentType(['application/json']);
        $headerParams['Square-Version'] = "2019-09-25";

        // path params
        if ($location_id !== null) {
            $resourcePath = str_replace(
                "{" . "location_id" . "}",
                $this->apiClient->getSerializer()
                    ->toPathValue($location_id),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        // body params
        $_tempBody = null;
        if (isset($body)) {
            $_tempBody = $body;
        }

        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (!empty($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }

        // this endpoint requires OAuth (access token)
        if ($this->apiClient->getConfig()->getAccessToken() !== "") {
            $headerParams['Authorization'] = 'Bearer ' . $this->apiClient->getConfig()
                    ->getAccessToken();
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'POST',
                $queryParams,
                $httpBody,
                $headerParams,
                \SquareConnect\Model\ChargeResponse::class
            );
            if (!$response) {
                return [null, $statusCode, $httpHeader];
            }

            return [
                \SquareConnect\ObjectSerializer::deserialize(
                    $response,
                    \SquareConnect\Model\ChargeResponse::class,
                    $httpHeader
                ),
                $statusCode,
                $httpHeader
            ];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = \SquareConnect\ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        \SquareConnect\Model\ChargeResponse::class,
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    break;
            }

            throw $e;
        }
    }
}
