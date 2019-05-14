<?php

namespace Dynamic\Salsify\Model;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * Class Importer
 * @package Dynamic\Salsify\Model
 *
 * Based off https://github.com/XinV/salsify-php-api/blob/master/lib/Salsify/API.php
 */
class Fetcher
{
    use Configurable;
    use Extensible;
    use Injectable;

    /**
     * @var string
     */
    const API_BASE_URL = 'https://app.salsify.com/api/';

    /**
     * @var string
     */
    protected $channelID;

    /**
     * @var string
     */
    protected $channelRunID;

    /**
     * @var string
     */
    protected $channelRunDataUrl;

    /**
     * @var bool
     */
    protected $useLatest;

    /**
     * Importer constructor.
     * @param string|int $channelID
     * @param bool $useLatest
     */
    public function __construct($channelID = '', $useLatest = false)
    {
        $this->channelID = $channelID;
        $this->useLatest = $useLatest;
    }

    /**
     * @param string|int $channelID
     * @return $this
     */
    public function setChannelID($channelID) {
        $this->channelID = $channelID;
        return $this;
    }

    /**
     * @param bool useLatest
     * @return $this
     */
    public function setUseLatest($useLatest) {
        $this->useLatest = $useLatest;
        return $this;
    }

    /**
     * @return string
     */
    private function channelUrl()
    {
        return self::API_BASE_URL . 'channels/' . $this->channelID;
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
        if ($this->useLatest) {
            return $this->channelRunsBaseUrl() . '/latest';
        }
        return $this->channelRunsBaseUrl() . '/' . $this->channelRunID;
    }

    /**
     * @return string
     */
    private function apiUrlSuffix()
    {
        return '?format=json&auth_token=' . $this->config()->get('apiKey');
    }

    /**
     * @param string $url
     * @param string $method
     * @param string|null $postBody
     * @return array|string
     */
    private function salsifyRequest($url, $method = 'GET', $postBody = null)
    {
        $defaultCurlOptions = array(
            CURLOPT_URL => $url . $this->apiUrlSuffix(),
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            // seemed reasonable settings
            CURLOPT_TIMEOUT => $this->config()->get('timeout'),
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_FORBID_REUSE => true,
        );
        if ($method === 'POST' && is_array($postBody)) {
            $postBody = json_encode($postBody);
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, $defaultCurlOptions);
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response && !empty($response)) {
            $response = json_decode($response, true);
        }
        return $response;
    }

    /**
     * @return $this
     */
    public function startExportRun()
    {
        if (!$this->useLatest) {
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
    public function getExportUrl() {
        return $this->channelRunDataUrl;
    }
}
