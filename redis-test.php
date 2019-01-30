<?php
declare(strict_types=1);

// usage php redis-test.php hostname port [numberOfIterations]

require_once __DIR__ . '/vendor/autoload.php';

function runTest(string $host, int $port, int $numIterations): void
{
    $key = 'kv:product_abstract:key:de_de:17714';
    setupData($host, $port, $key);
    for ($i = 0; $i < $numIterations; $i++) {
        $client = createRedisClient($host, $port);
        $client->connect();

        $productDataJson = $client->get($key);

        printResult($productDataJson, $numIterations, $i);

        $client->disconnect();
    }
}

function setupData(string $host, int $port, string $key): void {
    $client = createRedisClient($host, $port);
    $client->connect();

    $client->set($key, '{"userId": 1,"id": 1,"title": "delectus aut autem","completed": false}');

    $client->disconnect();
}

function createRedisClient(string $host, int $port): \Predis\ClientInterface
{
    return new \Predis\Client(
        [
            'protocol' => 'tcp',
            'port' => $port,
            'host' => $host,
            'database' => 0,
        ]
    );
}

function printResult(?string $productDataJson, int $numIterations, int $iterationCursor): void
{
    static $startTime;

    if (!$startTime) {
        $startTime = microtime(true);
    }

    echo !empty($productDataJson) ? '.' : 'x';

    if (isPrintLineEnd($iterationCursor, $numIterations)) {
        $duration = (microtime(true) - $startTime);
        echo  sprintf(
            ' [%s / %s / %sms / %sms]',
            ($iterationCursor + 1),
            $numIterations,
            round($duration * 1000, 2),
            round($duration * 1000 / 80, 2)
        );
        echo PHP_EOL;

        $startTime = 0;
    }
}

function isPrintLineEnd(int $iterationCursor, int $numIterations): bool
{
    static $lineLength = 80;

    if ($iterationCursor === 0) {
        return false;
    }

    if (($iterationCursor + 1) % $lineLength === 0) {
        return true;
    }

    if (($iterationCursor + 1) === $numIterations) {
        return true;
    }

    return false;
}

$numIterations = (int)($argv[3] ?? 1000);
runTest($argv[1], (int) $argv[2], $numIterations);
