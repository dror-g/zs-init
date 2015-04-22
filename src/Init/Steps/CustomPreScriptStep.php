<?php
namespace Zend\Init\Steps;

use Zend\Log;
use Zend\State;
use Zend\Init\Result;

class CustomPreScriptStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct("custom pre-start script step");
    }

    public function execute(State $state)
    {
        $state->log->log(Log::INFO, "Starting {$this->name}");
        if (isset($state['ZEND_PRE_SCRIPT_URL'], $state['ZEND_PRE_SCRIPT_PATH'])) {
            if (substr($state['ZEND_PRE_SCRIPT_URL'], 0, 3) === "s3:") {
                $state->log->log(Log::INFO, "Setting up S3 stream wrapper");
                $region = substr(file_get_contents("http://169.254.169.254/latest/meta-data/placement/availability-zone"), 0, -1);
                $options = ['region' => $region];
                if (isset($state['AWS_ACCESS_KEY'], $state['AWS_SECRET_KEY'])) {
                    $options['key'] = $state['AWS_ACCESS_KEY'];
                    $options['secret'] = $state['AWS_SECRET_KEY'];
                }
                $s3 = S3Client::factory($options);
                $s3->registerStreamWrapper();
            }

            if (is_dir($state['ZEND_PRE_SCRIPT_PATH'])) {
                $filename = basename($state['ZEND_PRE_SCRIPT_URL']);
                $state->log->log(Log::WARNING, "ZEND_PRE_SCRIPT_PATH targets directory {$state['ZEND_PRE_SCRIPT_PATH']}, adding filename {$filename} from URL");
                $state['ZEND_PRE_SCRIPT_PATH'] .= DIRECTORY_SEPARATOR . $filename;
            }

            $state->log->log(Log::INFO, "Downloading custom script from {$state['ZEND_PRE_SCRIPT_URL']} to {$state['ZEND_PRE_SCRIPT_PATH']}");
            $scriptData = file_get_contents($state['ZEND_PRE_SCRIPT_URL']);
            file_put_contents($state['ZEND_PRE_SCRIPT_PATH'], $scriptData);
            chmod($state['ZEND_PRE_SCRIPT_PATH'], 0755);
            $state->log->log(Log::INFO, "Executing {$state['ZEND_PRE_SCRIPT_PATH']}");
            exec($state['ZEND_PRE_SCRIPT_PATH'], $output, $exitCode);
            if ($exitCode !== 0) {
                $state->log->log(Log::WARNING, "Custom script exit code {$exitCode}");
            }
            $output = implode("\n", $output);
            $state->log->log(Log::INFO, "Custom script output\n{$output}");
        }
        $state->log->log(Log::INFO, "Finished {$this->name}");
        return new Result(Result::STATUS_SUCCESS);
    }
}
