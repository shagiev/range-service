<?php
/**
 * Web service version of range service.
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';


$response = [];


try {
    $request = json_decode($_POST['request'], true);
    if (!isset($request['command'])) {
        throw new \Exception('Incorrect command');
    }
    $command = $request['command'];

    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->beginTransaction();

    $rangeService = new \Service\RangeService($pdo);


    if ($command === 'create_range') {
        if (!isset($request['min_value']) || !isset($request['max_value'])) {
            throw new \RuntimeException("create_range requires 2 arguments - min_value and max_value");
        }

        $rangeId = $rangeService->createRange((int)$request['min_value'], (int)$request['max_value']);
        $response = ['range_id' => $rangeId];

    } else if ($command === 'allocate') {
        if (!isset($request['range_id'])) {
            throw new \RuntimeException('range_id not found');
        }

        $rangeId = (int)$request['range_id'];
        $number = $rangeService->allocate($rangeId);
        $response = ['number' => $number];

    } else if ($command === 'release') {
        if (!isset($request['range_id']) || !isset($request['number'])) {
            throw new \RuntimeException('range_id not found');
        }

        $rangeId = (int) $request['range_id'];
        $number = (int) $request['number'];

        $rangeService->release($rangeId, $number);
        $response = ['success' => true];
    } else {
        throw new \Exception('Incorrect command');
    }
    $pdo->commit();
} catch (Exception $exception) {
    $response = ['error' => $exception->getMessage()];
}

echo json_encode($response);

