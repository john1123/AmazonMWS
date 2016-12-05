<?php

class AmazonMWS
{
    protected $accessKeyId;
    protected $secretAccessKey;
    protected $merchantId;
    protected $applicationName    = 'AmazonMWS app';
    protected $applicationVersion = 'v3';
    protected $service;

    function __construct($accessKeyId, $secretAccessKey, $merchantId)
    {
        $this->applicationVersion = $this->applicationVersion . '_' . date('YmdHis');
        $this->accessKeyId = $accessKeyId;
        $this->secretAccessKey = $secretAccessKey;
        $this->merchantId = $merchantId;
    }

    public function call($action, $params, $path='/',  $host = 'mws.amazonservices.com') {
        $params['AWSAccessKeyId'] = $this->accessKeyId;
        $params['SellerId'] = $this->merchantId;
        $params['Timestamp'] = gmdate('Y-m-d\TH:i:s.\\0\\0\\0\\Z', time());
        $params['Action'] = $action;
        $params['Version'] = array_key_exists('Version', $params) ? $params['Version'] : '2009-01-01';

        $params['SignatureMethod'] = 'HmacSHA256';
        $params['SignatureVersion'] = '2';
        // Sort the URL parameters
        $url_parts = array();
        foreach(array_keys($params) as $key)
            $url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($params[$key]));

        sort($url_parts);

        // Construct the string to sign
        $url_string = implode("&", $url_parts);
        $string_to_sign = "GET\n" . $host . "\n" . $path . "\n" . $url_string;

        // Sign the request
        $signature = hash_hmac("sha256", $string_to_sign, $this->secretAccessKey, TRUE);

        // Base64 encode the signature and make it URL safe
        $signature = urlencode(base64_encode($signature));

        $url = 'https://' . $host . $path . '?' . $url_string . '&Signature=' . $signature;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->applicationName . ': ' . $this->applicationVersion);
        //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) chrome/39.0.2171.71 Safari/537.36')
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        return curl_exec($ch);
    }
}
