<?php
require(__DIR__ . '/vendor/autoload.php');

if ($argc != 2) {
    die("Usage: {$argv[0]} <path-to-nothing> [optional parameters to nothing]\n");
}

$nothing = $argv[1];

exec(__DIR__ . "/init.php", $output, $exitCode);

if ($exitCode !== 0) {
    echo "Warning: failed running init.php".PHP_EOL;
    echo implode(PHP_EOL, $output);
    echo PHP_EOL;
}

$nothingArgs = array_slice($argv, 2);

echo "Executing nothing".PHP_EOL;
pcntl_exec($nothing, $nothingArgs);
