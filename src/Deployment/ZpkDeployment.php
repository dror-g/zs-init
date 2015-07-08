<?php
namespace Zend\Deployment;

use Zend\Log;
use Zend\ZSWebApiClient;

class ZpkDeployment extends AbstractDeployment
{
    const DELAY=2;

    public function __construct($path, Log $log, $url, $name, $params, $webApiKeyName, $webApiKeyHash)
    {
        parent::__construct($path, $log, false);
        date_default_timezone_set("UTC");
        $parts = parse_url($url);
        if (!is_array($params)) {
            $params = array();
        }
        $this->name = $name;
        $this->params = $params;
        $this->filename = basename($parts['path']);
        $this->data = file_get_contents($url);
        $this->webApiKeyName = $webApiKeyName;
        $this->webApiKeyHash = $webApiKeyHash;
    }

    public function deploy()
    {
        $client = new ZSWebApiClient($this->webApiKeyName, $this->webApiKeyHash);
        $response = $client->applicationDeploy([
            'baseUrl' => "http://localhost{$this->path}",
            'defaultServer' => true,
            'userAppName' => $this->name,
            'userParams' => $this->params,
        ], [
            $this->filename => [
                'formname' => 'appPackage',
                'filename' => $this->filename,
                'ctype' => 'application/vnd.zend.applicationpackage',
                'data' => $this->data,
            ],
        ]);
        if ($response['error'] !== null) {
            $this->setError("{$response['code']} {$response['error']['errorCode']} {$response['error']['errorMessage']}");
            return false;
        }

        $appId = $response['data']['applicationInfo']['id'];
        $this->log->log(Log::INFO, "Started deploying application ID {$appId}");
        $client = new ZSWebApiClient($this->webApiKeyName, $this->webApiKeyHash);
        $waiting = true;
        while ($waiting) {
            $this->log->log(Log::INFO, "Waiting for application ID {$appId} deployment to finish");
            sleep(self::DELAY);
            $response = $client->applicationGetStatus([], [], true);
            if ($response['error'] === null && is_array($response['data']) && is_array($response['data']['applicationsList'])) {
                foreach ($response['data']['applicationsList'] as $app) {
                    if ($app['id'] == $appId) {
                        if ($app['status'] == 'deployed') {
                            $waiting = false;
                        }
                        break;
                    }
                }
            } else {
                $this->log->log(Log::WARNING, "Failed retrieving application status");
            }
        }

        $this->log->log(Log::INFO, "Application ID {$appId} deployment finished");
        return true;
    }
}
