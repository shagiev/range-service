<?php
/**
 * CLI version of range service.
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

const HELP_MESSAGE = "There is no command. Use: php range-service.php command params\n".
        'Availacommands: "create_range min_value max_value", "allocate range_id", "release range_id"\n';

if ($argc < 3 || in_array($argv[0],['-h', '--help'])) {
    echo HELP_MESSAGE;
    die(1);
}

$command = $argv[1];

try {
    $rangeService = new \Service\RangeService(new PDO(DB_DSN, DB_USER, DB_PASS));

    if ($command === 'create_range') {
        if ($argc < 3) {
            throw new RuntimeException("create_range requires 2 arguments - min_value and max_value");
        }
        $min = (int)$argv[2];
        $max = (int)$argv[3];

        $rangeId = $rangeService->createRange($min, $max);
        echo "Range id: $rangeId \n";

    } else if ($command === 'allocate') {
        $rangeId = (int)$argv[2];
        $number = $rangeService->allocate($rangeId);
        echo "Number: $number \n";
    } else if ($command === 'release') {
        $rangeId = (int) $argv[2];
        $number = (int) $argv[3];

        $rangeService->release($number);
        echo "Number $number successfully released";
    } else {
        throw new Exception(HELP_MESSAGE);
    }
} catch (Exception $exception) {
    echo $exception->getMessage();
    die(1);
}

