<?php

namespace Tests\AppBundle\Repository;

use AppBundle\AppBundle;
use AppBundle\Entity\Alert;
use AppBundle\Entity\Measurement;
use AppBundle\Repository\MeasurementRepository;
use Doctrine\ORM\EntityManager;

/**
 * Class MeasurementRepositoryTest
 * @package Tests\AppBundle\Repository
 */
class MeasurementRepositoryTest extends AbstractTest
{
    public function testGetLatestMeasurements()
    {
        $limit = 1;
        $expected = ['correct response'];
        $mock = $this->getMockBuilder(MeasurementRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findBy'])
            ->getMock();

        $mock->expects($this->once())
            ->method('findBy')
            ->with(['sensor' => $this->sensor], ['id' => 'DESC'], $limit)
            ->willReturn($expected);

        $response = $mock->getLatestMeasurements($this->sensor, $limit);
        $this->assertEquals($expected, $response);
    }

    public function testDetectCriticalStatusAlert()
    {
        $minExceed = 3;
        $measurementData = [
            [
                'id' => 1,
                'value' => 3000,
            ],
            [
                'id' => 2,
                'value' => 4000,
            ],
            [
                'id' => 3,
                'value' => 5000,
            ],
        ];

        $measurements = $this->prepareMeasurements($measurementData);

        $mock = $this->getMockBuilder(MeasurementRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLatestMeasurements', 'getEntityManager'])
            ->getMock();

        $mock->expects($this->once())
            ->method('getLatestMeasurements')
            ->with($this->sensor, $minExceed)
            ->willReturn(array_reverse($measurements));

        $entityMock = $this->createMock(EntityManager::class);
        $mock->expects($this->exactly(3))->method('getEntityManager')->willReturn($entityMock);

        $mock->detectCriticalStatus($this->sensor, $minExceed);

        $alert = $this->sensor->getAlert();
        $this->assertNotEmpty($alert);
        $this->assertEquals(
            (new \DateTime())->format(AppBundle::DATETIME_FORMAT),
            $alert->getStart()->format(AppBundle::DATETIME_FORMAT)
        );
        $this->assertEmpty($alert->getEnd());
    }

    public function testDetectCriticalStatusAlertFor4thMeasurement()
    {
        $minExceed = 3;
        $measurementData = [
            [
                'id' => 1,
                'value' => 3000,
            ],
            [
                'id' => 2,
                'value' => 4000,
            ],
            [
                'id' => 3,
                'value' => 5000,
            ],
            [
                'id' => 4,
                'value' => 1000,
            ],
            [
                'id' => 5,
                'value' => 2400,
            ],
        ];

        $measurements = $this->prepareMeasurements($measurementData);

        $alert = $this->initializeAlertObject(new \DateTime('-3 hours'), null);
        $alert->addMeasurements($measurements[0]);
        $alert->addMeasurements($measurements[1]);
        $alert->addMeasurements($measurements[2]);
        $this->sensor->setAlert($alert);

        $mock = $this->getMockBuilder(MeasurementRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLatestMeasurements', 'getEntityManager'])
            ->getMock();

        $mock->expects($this->once())
            ->method('getLatestMeasurements')
            ->with($this->sensor, $minExceed)
            ->willReturn(array_reverse(array_splice($measurements, 2)));

        $entityMock = $this->createMock(EntityManager::class);
        $mock->expects($this->exactly(2))->method('getEntityManager')->willReturn($entityMock);

        $mock->detectCriticalStatus($this->sensor, $minExceed);

        $alert = $this->sensor->getAlert();
        $this->assertSame($alert, end($measurements)->getAlert());
        $this->assertEmpty($alert->getEnd());
    }

    public function testDetectCriticalStatusOK()
    {
        $minExceed = 3;
        $measurementData = [
            [
                'id' => 1,
                'value' => 1000,
            ],
            [
                'id' => 2,
                'value' => 2000,
            ],
            [
                'id' => 3,
                'value' => 1200,
            ],
        ];
        $measurements = $this->prepareMeasurements($measurementData);
        $alert = $this->initializeAlertObject(new \DateTime('-3 hours'), null);
        $this->sensor->setAlert($alert);

        $mock = $this->getMockBuilder(MeasurementRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLatestMeasurements', 'getEntityManager'])
            ->getMock();

        $mock->expects($this->once())
            ->method('getLatestMeasurements')
            ->with($this->sensor, $minExceed)
            ->willReturn(array_reverse($measurements));

        $entityMock = $this->createMock(EntityManager::class);
        $mock->expects($this->exactly(3))->method('getEntityManager')->willReturn($entityMock);

        $mock->detectCriticalStatus($this->sensor, $minExceed);

        $this->assertNotEmpty($alert->getEnd());
        $this->assertEmpty($this->sensor->getAlert());
    }

    public function testCountExceededTimes()
    {
        $criticalValue = 1000;
        $measurementData = [
            [
                'id' => 1,
                'value' => 1000,
            ],
            [
                'id' => 2,
                'value' => 2000,
            ],
            [
                'id' => 3,
                'value' => 1200,
            ],
        ];
        $measurements = $this->prepareMeasurements($measurementData);
        $this->assertEquals(2, MeasurementRepository::countExceededTimes($measurements, $criticalValue));
    }

    /**
     * @param array $measurementData
     * @return array
     */
    private function prepareMeasurements(array $measurementData)
    {
        $measurements = [];
        foreach ($measurementData as $data) {
            $measurement = $this->initializeMeasurementObject($data['value']);

            $reflection = new \ReflectionClass($measurement);
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($measurement, $data['id']);

            $measurements[] = $measurement;
        }

        return $measurements;
    }
}
