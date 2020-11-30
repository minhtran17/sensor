<?php

namespace Tests\AppBundle\Repository;

use AppBundle\Entity\Alert;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\Sensor;
use AppBundle\Repository\MeasurementRepository;
use AppBundle\Repository\SensorRepository;
use Doctrine\ORM\EntityManager;

/**
 * Class SensorRepositoryTest
 * @package Tests\AppBundle\Repository
 */
class SensorRepositoryTest extends AbstractTest
{
    /**
     * @dataProvider upsertProvider
     *
     * @param bool $found
     * @param bool $flush
     */
    public function testUpsert(bool $found, bool $flush)
    {
        $emMock = $this->createMock(EntityManager::class);

        $emCallCount = 0;
        if (!$found) {
            $emMock->expects($this->once())->method('persist');
            $emCallCount = 1;
            if ($flush) {
                $emMock->expects($this->once())->method('flush');
                $emCallCount = 2;
            }
        }

        $mock = $this->getMockBuilder(SensorRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getEntityManager'])
            ->getMock();
        $mock->expects($this->once())->method('find')->willReturn($found ? $this->sensor : null);
        $mock->expects($this->exactly($emCallCount))->method('getEntityManager')->willReturn($emMock);

        $result = $mock->upsert($this->sensor->getId(), Sensor::TYPE_CO2, $flush);

        $this->assertEquals($this->sensor, $result);
    }

    /**
     * @dataProvider getCurrentStatusProvider
     *
     * @param Sensor $sensor
     * @param null|Measurement $measurement
     */
    public function testGetCurrentStatus(Sensor $sensor, ?Measurement $measurement)
    {
        $mock = $this->getMockBuilder(SensorRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager'])
            ->getMock();

        if ($sensor->getAlert()) {
            $mock->expects($this->never())->method('getEntityManager');
            $this->assertEquals(Sensor::STATUS_ALERT, $mock->getCurrentStatus($sensor));
            return;
        }

        $measurementRepoMock = $this->createMock(MeasurementRepository::class);
        $measurementRepoMock->expects($this->once())->method('getLatestMeasurements')
            ->with($this->sensor, 1)->willReturn([$measurement]);

        $emMock = $this->createMock(EntityManager::class);
        $emMock->expects($this->once())->method('getRepository')
            ->with(Measurement::class)->willReturn($measurementRepoMock);

        $mock->expects($this->once())->method('getEntityManager')->willReturn($emMock);

        $status = Sensor::STATUS_OK;
        if ($measurement->getValue() > Measurement::getCo2CriticalValue()) {
            $status = Sensor::STATUS_WARN;
        }

        $this->assertEquals($status, $mock->getCurrentStatus($sensor));
    }

    /**
     * @return array
     */
    public function upsertProvider(): array
    {
        return [
            [false, false],
            [false, true],
            [true, true],
            [true, false],
        ];
    }

    /**
     * @return array
     */
    public function getCurrentStatusProvider(): array
    {
        $this->sensor = $this->initializeSensorObject();

        return [
            [
                (function () {
                    $sensor = clone $this->sensor;
                    $sensor->setAlert(new Alert());
                    return $sensor;
                })(),
                null,
            ],
            [
                $this->sensor,
                $this->initializeMeasurementObject(Measurement::getCo2CriticalValue()),
            ],
            [
                $this->sensor,
                $this->initializeMeasurementObject(Measurement::getCo2CriticalValue() + 1),
            ],
            [
                $this->sensor,
                $this->initializeMeasurementObject(Measurement::getCo2CriticalValue() - 1),
            ],
        ];
    }
}
