<?php

namespace Tests\AppBundle\Repository;

use AppBundle\Entity\Alert;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\Sensor;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class AbstractTest
 * @package Tests\AppBundle\Repository
 */
abstract class AbstractTest extends WebTestCase
{
    const TEST_SENSOR_ID = 'test_sensor_id';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Sensor
     */
    protected $sensor;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->sensor = $this->initializeSensorObject();
    }

    /**
     * @return Sensor
     */
    protected function initializeSensorObject(): Sensor
    {
        $sensor = new Sensor();
        $sensor->setId(self::TEST_SENSOR_ID);
        $sensor->setType(Sensor::TYPE_CO2);

        return $sensor;
    }

    /**
     * @param int $value
     * @return Measurement
     */
    protected function initializeMeasurementObject(int $value): Measurement
    {
        $measurement = new Measurement();
        $measurement->setSensor($this->sensor);
        $measurement->setValue($value);

        return $measurement;
    }

    /**
     * @param \DateTime|null $start
     * @param \DateTime|null $end
     * @return Alert
     */
    protected function initializeAlertObject(?\DateTime $start, ?\DateTime $end): Alert
    {
        $alert = new Alert();
        $alert->setSensor($this->sensor);
        $alert->setStart($start ?: new \DateTime());
        if ($end) {
            $alert->setEnd($end);
        }
        $alert->setCreated($start ?: new \DateTime());

        return $alert;
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks
    }
}
