<?php
/**
 * Tests for RangeService.
 */

use Service\RangeService;
require_once '../config/test-config.php';

class RangeServiceTest extends PHPUnit\Framework\TestCase
{
    private $pdo;

    public function setUp()
    {
        parent::setUp();

        $this->pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $this->pdo->beginTransaction();
    }

    /**
     * Test range 1-10.
     */
    public function testSmallRange(): void
    {
        $rangeService = new RangeService($this->pdo);
        $rangeId = $rangeService->createRange(1, 10);

        $numbers = [];
        for ($i = 0; $i<10; $i++) {
            $numbers[] = $rangeService->allocate($rangeId);
        }

        $exceptionRaised = false;
        try {
            $rangeService->allocate($rangeId);
        } catch (Exception $exception) {
            $exceptionRaised = true;
        }
        $this->assertTrue($exceptionRaised, 'Allocate too many numbers');

        sort($numbers);
        $this->assertEquals(range(1, 10), $numbers, 'Incorrect values');
    }


    /**
     * Try to create incorrect ranges.
     */
    public function testIncorrectRanges()
    {
        $rangeService = new RangeService($this->pdo);

        $exceptionRaised = false;
        try {
            $rangeService->createRange(0, -100);
        } catch (Exception $exception) {
            $exceptionRaised = true;
        }
        $this->assertTrue($exceptionRaised, 'Created range with max_value more than min_value');
    }

    /**
     * Try to allocate and release numbers many times.
     */
    public function testAllocateWithReleases()
    {
        $rangeService = new RangeService($this->pdo);

        $rangeId = $rangeService->createRange(100, 109);

        $numbers = [];
        $numbers[] = $rangeService->allocate($rangeId);
        $numbers[] = $rangeService->allocate($rangeId);
        $numbers[] = $rangeService->allocate($rangeId);

        for ($i = 0; $i<20; $i++) {
            shuffle($numbers);
            $rangeService->release($rangeId, array_pop($numbers));
            $numbers[] = $rangeService->allocate($rangeId);
        }

        for ($i = 0; $i<7; $i++) {
            $numbers[] = $rangeService->allocate($rangeId);
        }

        // Finally we should get the same numbers range.
        sort($numbers);
        $this->assertEquals(range(100, 109), $numbers, 'Incorrect values');
    }

    /**
     * Try to release free numbers or numbers out of range.
     */
    public function testUncorrectReleases()
    {
        $rangeService = new RangeService($this->pdo);
        $rangeId = $rangeService->createRange(0, 100);

        $exceptionRaised = 0;
        foreach ([-100, 0, 0, 101] as $number) {
            try {
                $rangeService->release($rangeId, $number);
            } catch (Exception $exception) {
                $exceptionRaised++;
            }
        }
        $this->assertEquals(4, $exceptionRaised, 'Not corrupted on incorrect number release');
    }


    /**
     * Try to load service on huge numbers and 10 000 repeats.
     * My result is 3 sec.
     */
    public function testHugeRange()
    {
        $rangeService = new RangeService($this->pdo);
        $rangeId = $rangeService->createRange(-900000000000000000, 900000000000000000);
        $numbers = [];
        for($i=0; $i<10000; $i++) {
            $numbers[] = $rangeService->allocate($rangeId);
        }

        $this->assertEquals(count($numbers), count(array_unique($numbers)), 'Check all numbers are unique');
    }

    /**
     * Rollback database after all tests.
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->pdo->rollBack();
    }
}
