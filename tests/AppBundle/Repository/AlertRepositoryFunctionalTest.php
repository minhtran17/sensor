<?php

namespace Tests\AppBundle\Repository;

use AppBundle\Entity\Alert;
use AppBundle\Entity\Sensor;
use AppBundle\Repository\AlertRepository;

/**
 * Class AlertRepositoryTest
 * @package Tests\AppBundle\Repository
 */
class AlertRepositoryFunctionalTest extends AbstractTest
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->removeTestData();
        $this->prepareData();
    }

    private function prepareData()
    {
        $alertData = [
            [
                'start' => '2020-11-11T10:10:10',
                'end' => '2020-11-12T10:10:10',
            ],
            [
                'start' => '2020-11-11T09:10:10',
                'end' => '2020-11-11T10:10:10',
            ],
            [
                'start' => '2020-11-12T10:10:10',
                'end' => '2020-11-12T11:10:10',
            ],
        ];

        $this->entityManager->persist($this->sensor);

        foreach ($alertData as $data) {
            $alert = $this->initializeAlertObject(new \DateTime($data['start']), new \DateTime($data['end']));
            $this->entityManager->persist($alert);
        }

        $this->entityManager->flush();
    }

    public function testListAlerts()
    {
        $filters = [
            'start' => '2020-11-11T10:10:10',
            'end' => '2020-11-12T10:10:10',
            'invalid_field' => 123,
        ];

        /** @var AlertRepository $repository */
        $repository = $this->entityManager->getRepository(Alert::class);
        $alerts = $repository->listAlerts($this->sensor, $filters);

        $this->assertCount(1, $alerts);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        $this->removeTestData();
        parent::tearDown();
    }

    private function removeTestData()
    {
        /** @var Sensor $sensor */
        $sensor = $this->entityManager->getRepository(Sensor::class)->find(self::TEST_SENSOR_ID);
        if (!$sensor) {
            return;
        }

        $alerts = $this->entityManager->getRepository(Alert::class)->findBy(['sensor' => $sensor]);
        $this->entityManager->remove($sensor);
        foreach ($alerts as $alert) {
            $this->entityManager->remove($alert);
        }
        $this->entityManager->flush();
    }
}
