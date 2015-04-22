<?php
namespace Zend;

use Zend\Http\Client;
use Zend\Http\Request;

class ZSWebApiClient
{
    const VERSION='0.1';

    private $webApiKeyName;
    private $webApiKeyHash;
    private $url;
    private $host;
    private $port;
    private $path;
    private $userAgent;
    private $client;

    public function __construct($webApiKeyName, $webApiKeyHash, $timeout = 600, $url = "http://localhost:10081/ZendServer")
    {
        $this->webApiKeyName = $webApiKeyName;
        $this->webApiKeyHash = $webApiKeyHash;
        $this->url = $url;
        $this->userAgent = "ZsInit/".self::VERSION;
        $this->client = new Client();
        $this->client->setOptions([
            'timeout' => $timeout,
        ]);
        $this->client->setEncType(Client::ENC_FORMDATA);

        $parsedUrl = parse_url($url);
        if ($url !== false) {
            $this->host = $parsedUrl['host'];
            $this->port = $parsedUrl['port'];
            $this->path = $parsedUrl['path'];
        }
    }

    private function getSignature($date, $action)
    {
        return "{$this->webApiKeyName};".hash_hmac('sha256', "{$this->host}:{$this->port}:{$this->path}/Api/{$action}:{$this->userAgent}:{$date}", $this->webApiKeyHash);
    }

    public function __call($name, $arguments)
    {
        $date = gmdate('D, d M Y H:i:s') . ' GMT';
        $headers = [
            'Date'                 => $date,
            'User-Agent'           => $this->userAgent,
            'X-Zend-Signature'    => $this->getSignature($date, $name),
            'Accept'            => 'application/vnd.zend.serverapi+json',
        ];

        $request = new Request();
        if (@$arguments[2] === true) {
            $request->setMethod(Request::METHOD_GET);
        } else {
            $request->setMethod(Request::METHOD_POST);
        }
        $request->setUri("{$this->url}/Api/{$name}");
        $request->getHeaders()->addHeaders($headers);
        foreach ($arguments[0] as $name => $value) {
            if (is_bool($value)) {
                $value = $value ? "TRUE" : "FALSE";
            }
            $request->getPost()->set($name, $value);
        }
        if (!is_array(@$arguments[1])) {
            $arguments[1] = array();
        }
        foreach ($arguments[1] as $name => $file) {
            $request->getFiles()->set($name, $file);
        }

        $response = $this->client->send($request);
        $responseBody = json_decode($response->getBody(), true);
        $data = @$responseBody['responseData'];
        $error = @$responseBody['errorData'];
        return ['code' => $response->getStatusCode(), 'reason' => $response->getReasonPhrase(), 'data' => $data, 'error' => $error];
    }
}
