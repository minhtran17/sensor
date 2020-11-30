<?php

namespace Tests\AppBundle\Controller\Api\v1;

use AppBundle\AppBundle;
use AppBundle\Entity\Alert;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\Sensor;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CO2SensorControllerTest
 * @package Tests\AppBundle\Controller\Api\v1
 */
class CO2SensorControllerTest extends WebTestCase
{
    const TEST_SENSOR_ID = 'test_sensor_id';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Sensor
     */
    private $sensor;

    public function setUp()
    {
        $this->client = static::createClient();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->removeTestData();
        $this->prepareData();
    }

    private function prepareData()
    {
        $this->sensor = new Sensor();
        $this->sensor->setId(self::TEST_SENSOR_ID);
        $this->sensor->setType(Sensor::TYPE_CO2);

        $this->entityManager->persist($this->sensor);
        $this->entityManager->flush();
    }

    /**
     * @dataProvider notFoundSensorProvider
     *
     * @param string $endpoint
     */
    public function testNotFoundSensor(string $endpoint)
    {
        $this->client->request('GET', $endpoint);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('Sensor Not found', json_decode($response->getContent()));
    }

    /**
     * @return array
     */
    public function notFoundSensorProvider(): array
    {
        $dumpId = 'non_existing_id_1234567890';
        return [
            [sprintf('/api/v1/sensors/%s/measurements', $dumpId)],
            [sprintf('/api/v1/sensors/%s', $dumpId)],
            [sprintf('/api/v1/sensors/%s/alerts', $dumpId)],
            [sprintf('/api/v1/sensors/%s/metrics', $dumpId)],
        ];
    }

    /**
     * @param int $value
     * @param bool $flush
     * @return Measurement
     */
    private function addMeasurement(int $value, bool $flush = true): Measurement
    {
        $measurement = new Measurement();
        $measurement->setSensor($this->sensor);
        $measurement->setValue($value);

        $this->entityManager->persist($measurement);
        if ($flush) {
            $this->entityManager->flush();
        }

        return $measurement;
    }

    /**
     * @dataProvider measurementsCollectionActionProvider
     *
     * @param bool $measurementFound
     */
    public function testMeasurementsCollectionAction(bool $measurementFound)
    {
        if ($measurementFound) {
            $measurement = $this->addMeasurement(1000);
        }
        $this->client->request('GET', sprintf('/api/v1/sensors/%s/measurements', $this->sensor->getId()));
        $response = $this->client->getResponse();

        if (!empty($measurement)) {
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $this->assertEquals(
                [
                    $measurement->getSensor()->getType() => $measurement->getValue(),
                    'time' => $measurement->getCreated()->format(AppBundle::DATETIME_FORMAT),
                ],
                json_decode($response->getContent(), true)
            );

            return;
        }
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('Measurement Not found', json_decode($response->getContent(), true));
    }

    /**
     * @dataProvider measurementsCreateActionProvider
     *
     * @param int $value
     */
    public function testMeasurementsCreateAction(int $value)
    {
        $this->client->request(
            'POST',
            sprintf('/api/v1/sensors/%s/measurements', $this->sensor->getId()),
            ['value' => $value]
        );
        $response = $this->client->getResponse();

        if ($value) {
            /** @var Measurement $measurement */
            $measurements = $this->entityManager->getRepository(Measurement::class)
                ->getLatestMeasurements($this->sensor, 1);
            $measurement = reset($measurements);
            $this->assertNotEmpty($measurement);
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $this->assertEquals(
                [
                    $measurement->getSensor()->getType() => $measurement->getValue(),
                    'time' => $measurement->getCreated()->format(AppBundle::DATETIME_FORMAT),
                ],
                json_decode($response->getContent(), true)
            );

            return;
        }
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(
            [
                'message' => 'Wrong data submitted',
                'error' => 'ERROR: Measurement Value must be greater than 0',
            ],
            json_decode($response->getContent(), true)
        );
    }

    /**
     * @dataProvider statusActionProvider
     *
     * @param array $values
     * @param string $status
     */
    public function testStatusAction(array $values, string $status)
    {
        foreach ($values as $value) {
            $this->addMeasurement($value, false);
        }
        if (Sensor::STATUS_ALERT === $status) {
            $this->initializeAlert(null, null,false);
        }
        $this->entityManager->flush();
        $this->client->request('GET', sprintf('/api/v1/sensors/%s', $this->sensor->getId()));
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(
            ['status' => $status],
            json_decode($response->getContent(), true)
        );
    }

    /**
     * @dataProvider alertListActionProvider
     *
     * @param string $start
     * @param string $end
     * @param array $filters
     * @param bool $found
     */
    public function testAlertListAction(string $start, string $end, array $filters, bool $found)
    {
        $alert = $this->initializeAlert(new \DateTime($start), new \DateTime($end));
        foreach ([3000, 3000, 3000] as $value) {
            $measurement = $this->addMeasurement($value);
            $alert->addMeasurements($measurement);
            $this->entityManager->merge($measurement);
        }
        $this->entityManager->merge($alert);
        $this->entityManager->flush();

        $this->client->request('GET', sprintf('/api/v1/sensors/%s/alerts', $this->sensor->getId()), $filters);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        if ($found) {
            $this->assertEquals(
                [
                    [
                        'startTime' => $alert->getStart()->format(AppBundle::DATETIME_FORMAT),
                        'endTime' => $alert->getEnd()->format(AppBundle::DATETIME_FORMAT),
                        'mesurement1' => 3000,
                        'mesurement2' => 3000,
                        'mesurement3' => 3000,
                    ],
                ],
                json_decode($response->getContent(), true)
            );
            return;
        }
        $this->assertEmpty(json_decode($response->getContent(), true));
    }

    public function testMetricsAction()
    {
        foreach ([4000, 2000, 3000] as $value) {
            $this->addMeasurement($value, false);
        }
        $this->entityManager->flush();

        $this->client->request('GET', sprintf('/api/v1/sensors/%s/metrics', $this->sensor->getId()));
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(
            [
                'maxLast30Days' => 4000,
                'avgLast30Days' => 3000,
            ],
            json_decode($response->getContent(), true)
        );
    }

    /**
     * @return array
     */
    public function alertListActionProvider(): array
    {
        return [
            [
                '2020-11-11T10:10:10',
                '2020-11-11T12:10:10',
                ['start' => '2020-11-11T10:10:10', 'end' => '2020-11-11T12:10:10'],
                true
            ],
            [
                '2020-11-11T10:10:10',
                '2020-11-11T12:10:10',
                ['start' => '2020-11-13T10:10:10'],
                false
            ],
        ];
    }

    /**
     * @return array
     */
    public function statusActionProvider(): array
    {
        return [
            [
                [1000],
                Sensor::STATUS_OK,
            ],
            [
                [1000, 2000, 2000],
                Sensor::STATUS_OK,
            ],
            [
                [3000, 3000, 3000],
                Sensor::STATUS_ALERT,
            ],
            [
                [2000, 3000],
                Sensor::STATUS_WARN,
            ],
        ];
    }

    /**
     * @return array
     */
    public function measurementsCreateActionProvider(): array
    {
        return [
            [1000],
            [2000],
            [0],
        ];
    }

    /**
     * @return array
     */
    public function measurementsCollectionActionProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        $this->removeTestData();
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks
    }

    private function removeTestData()
    {
        $sensor = $this->entityManager->getRepository(Sensor::class)->find(self::TEST_SENSOR_ID);
        if (!$sensor) {
            return;
        }

        $sensor->setAlert(null);
        $this->entityManager->merge($sensor);
        $this->entityManager->flush();

        $alerts = $this->entityManager->getRepository(Alert::class)->findBy(['sensor' => $sensor]);
        $measurements = $this->entityManager->getRepository(Measurement::class)->findBy(['sensor' => $sensor]);
        foreach (array_merge($measurements, $alerts) as $object) {
            $this->entityManager->remove($object);
        }

        $this->entityManager->remove($sensor);
        $this->entityManager->flush();
    }

    /**
     * @param \DateTime|null $start
     * @param \DateTime|null $end
     * @param bool $flush
     * @return Alert
     */
    private function initializeAlert(?\DateTime $start = null, ?\DateTime $end = null, bool $flush = true): Alert
    {
        $alert = new Alert();
        $alert->setSensor($this->sensor);
        $alert->setStart($start ?: new \DateTime());
        if ($end) {
            $alert->setEnd($end);
        }
        $this->entityManager->persist($alert);
        $this->sensor->setAlert($alert);
        $this->entityManager->merge($this->sensor);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $alert;
    }
}
