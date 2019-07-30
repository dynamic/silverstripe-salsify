<?php

namespace Dynamic\Salsify\Model;

use Exception;
use GuzzleHttp\Client;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * Class Importer
 * @package Dynamic\Salsify\Model
 *
 * Based off https://github.com/XinV/salsify-php-api/blob/master/lib/Salsify/API.php
 *
 * @mixin Configurable
 * @mixin Extensible
 * @mixin Injectable
 */
class Fetcher extends Service
{
    /**
     * @var string
     */
    const API_BASE_URL = 'https://app.salsify.com/api/';

    /**
     * @var string
     */
    protected $channelRunID;

    /**
     * @var string
     */
    protected $channelRunDataUrl;

    /**
     * Importer constructor.
     * @param string $importerKey
     * @throws \Exception
     */
    public function __construct($importerKey)
    {
        parent::__construct($importerKey);

        if (!$this->config()->get('apiKey')) {
            throw new Exception('An API key needs to be provided');
        }

        if (!$this->config()->get('channel')) {
            throw new Exception('A fetcher needs a channel');
        }
    }

    /**
     * @return string
     */
    private function channelUrl()
    {
        $channel = $this->config()->get('channel');
        if ($organization = $this->config()->get('organizationID')) {
            return "orgs/{$organization}/channels/{$channel}";
        }
        return "channels/{$channel}";
    }

    /**
     * @return string
     */
    private function channelRunsBaseUrl()
    {
        return $this->channelUrl() . '/runs';
    }

    /**
     * @return string
     */
    private function createChannelRunUrl()
    {
        return $this->channelRunsBaseUrl();
    }

    /**
     * @return string
     */
    private function channelRunUrl()
    {
        if ($this->config()->get('useLatest')) {
            return $this->channelRunsBaseUrl() . '/latest';
        }
        return $this->channelRunsBaseUrl() . '/' . $this->channelRunID;
    }

    /**
     * @param string $url
     * @param string $method
     * @param string|null $postBody
     * @return array|string
     *
     * @throws \Exception
     */
    private function salsifyRequest($url, $method = 'GET', $postBody = null)
    {
        $client = new Client([
            'base_uri' => self::API_BASE_URL,
            'timeout' => $this->config()->get('timeout'),
            'http_errors' => false,
            'verify' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config()->get('apiKey'),
                'Content-Type' => 'application/json',
            ],
        ]);

        if ($method === 'POST' && is_array($postBody)) {
            $response = $client->request($method, $url, [
                'json' => $postBody,
            ]);
        } else {
            $response = $client->request($method, $url);
        }


        if ($response->getStatusCode() === 404) {
            throw new Exception("Endpoint wasn't found. Are you sure the channel and organization are correct?");
        }

        if ($response->getBody() && !empty($response->getBody())) {
            $response = json_decode($response->getBody(), true);

            // throw exceptions for salsify errors
            if (array_key_exists('errors', $response)) {
                foreach ($response['errors'] as $error) {
                    throw new Exception($error);
                }
            }
        }

        return $response;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function startExportRun()
    {
        if ($this->config()->get('useLatest') !== true) {
            $response = $this->salsifyRequest($this->createChannelRunUrl(), 'POST');
            $this->channelRunID = $response['id'];
        }
        return $this;
    }

    /**
     * @throws Exception
     */
    private function checkExportUrl()
    {
        $exportRun = $this->salsifyRequest($this->channelRunUrl());
        $status = $exportRun['status'];
        if ($status === 'completed') {
            $this->channelRunDataUrl = $exportRun['product_export_url'];
        } elseif ($status === 'failed') {
            // this would be an internal error in Salsify
            throw new Exception('Salsify failed to produce an export.');
        }
    }

    /**
     * waits until salsify is done preparing the given export, and returns the URL when done.
     * Throws an exception if anything funky occurs.
     *
     * @return $this
     * @throws Exception
     */
    public function waitForExportRunToComplete()
    {
        $this->channelRunDataUrl = null;
        do {
            $this->checkExportUrl();
            sleep(5);
        } while (!$this->channelRunDataUrl);
        return $this;
    }

    /**
     * @return string
     */
    public function getExportUrl()
    {
        return $this->channelRunDataUrl;
    }
}
