<?php
/**
 * Created by PhpStorm.
 * User: lenarsagiev
 * Date: 2019-02-14
 * Time: 18:07
 */

namespace Service;


use http\Exception;

class RangeService
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createRange(int $min, int $max): int
    {
        if ($max < $min) {
            throw new \RuntimeException('Cannot create range with max_value less than min_value');
        }

        $stmt = $this->pdo->prepare('INSERT INTO "ranges"("start", "end", "free") VALUES(:start, :end, :free);');
        $stmt->execute([
            ':start' => $min,
            ':end' => $max,
            ':free' => $max - $min + 1
        ]);
        $rangeId = $this->pdo->lastInsertId();
        return $rangeId;
    }

    public function allocate(int $rangeId): int
    {
        $stmt = $this->pdo->prepare('SELECT allocate_number(:rangeid);');
        $stmt->execute([':rangeid' => $rangeId]);
        if ($stmt->errorCode() !== "00000") {
            throw new \RuntimeException('Cannot allocate more numbers');
        }
        return $stmt->fetchColumn(0);
    }

    public function release(int $rangeId, int $number): void
    {
        $stmt = $this->pdo->prepare('SELECT release_number(:rangeid, :number);');
        $stmt->execute([':rangeid' => $rangeId, ':number' => $number]);
        if ($stmt->errorCode() !== '00000') {
            throw new \RuntimeException('Cannot release number');
        }
    }
}