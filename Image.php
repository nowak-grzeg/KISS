<?php

namespace SigniSoft\ImageSwap\Helper\Catalog;

//TODO: move to ext. config file, remove global consts
const URL = '{truncated, real url was used}';
const API_KEY = '{truncated, real key was used}';
const HTTP_SUCCESS_CODE = 2; //internal code
const TIMEOUT = 2000;
const PLACEHOLDER_DIR = 'images/';
const PLACEHOLDER_IMAGE = '404.jpg';

class Image extends \Magento\Catalog\Helper\Image
{       
    /**
     * @constructor
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\Product\ImageFactory $productImageFactory
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\View\ConfigInterface $viewConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Model\View\Asset\PlaceholderFactory $placeholderFactory
     */
    public function __construct(\Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Product\ImageFactory $productImageFactory,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\View\ConfigInterface $viewConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\View\Asset\PlaceholderFactory $placeholderFactory = null)
    {
        $this->_logger = $logger;
        parent::__construct($context, $productImageFactory, $assetRepo, $viewConfig, $placeholderFactory);
    }
    
    /**
     * @return string
     */
    public function getUrl(): string
    {
        try {
            $handleCurl = curl_init();
            $response = $this->_callCurl($handleCurl);
            $url=  json_decode($response, true);  
            curl_close($handleCurl);
            return $url['url'];

        } catch (\Exception $e) {
           $this->_logException($e->getMessage()); 
           return $this->getDefaultPlaceholderUrl();
        }
    }
     
    private function _logFailed(string $logMessage)
    {
        /* @TODO extend log method for module usage */
        $this->_logger->debug($logMessage);
    }
    
    private function _logException(string $logMessage)
    {
        /* @TODO extend log method for module usage */
        $this->_logger->error($logMessage);
    }
    
    /**
     * @TODO replace with other CURL handler
     * @return string
     */
    private function _callCurl(object $handleCurl): string
    {
        $dataGet = ['api_key' => API_KEY];           
        $messageCurl = http_build_query($dataGet);
        $apiUrl =  URL . '?' . $messageCurl;
        curl_setopt($handleCurl, CURLOPT_URL, $apiUrl);
        curl_setopt($handleCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handleCurl, CURLOPT_TIMEOUT_MS, TIMEOUT);
        $response = curl_exec($handleCurl);
        if (curl_errno($handleCurl)) {
            $this->_logException(curl_error($handleCurl));
        }
        $found = $this->_handleNotFound($handleCurl);
        if($found == HTTP_SUCCESS_CODE) {
            return $response;
        }
        $this->_logFailed($response);
        return $this->getDefaultPlaceholderUrl();       
    }
    
    /**
     * @TODO refactor, for testing
     * @return int
     */
    private function _handleNotFound(object $handle): mixed
    {
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $this->_logException($httpCode);
        if((string)$httpCode === "404")
        {
            return $this->getDefaultPlaceholderUrl();
        }
        return HTTP_SUCCESS_CODE;
    }
    
    /**
     * @TODO path from config after options move to cfg
     * @return int
     */
    public function getDefaultPlaceholderUrl(): string
    {       
        return $this->_assetRepo->getUrl("SigniSoft_ImageSwap::".PLACEHOLDER_DIR.PLACEHOLDER_IMAGE);
    }
}

