<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Measurement;
use AppBundle\Entity\Sensor;

/**
 * Class SensorRepository
 * @package AppBundle\Repository
 */
class SensorRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param string $sensorId
     * @param string $type
     * @param bool $flush
     * @return Sensor
     */
    public function upsert(string $sensorId, string $type, bool $flush = true): Sensor
    {
        $sensor = $this->find($sensorId);
        if (!$sensor) {
            $sensor = new Sensor();
            $sensor->setId($sensorId);
            $sensor->setType($type);

            $this->getEntityManager()->persist($sensor);
            if ($flush) {
                $this->getEntityManager()->flush();
            }
        }

        return $sensor;
    }

    /**
     * @param Sensor $sensor
     * @return string
     */
    public function getCurrentStatus(Sensor $sensor): string
    {
        if ($sensor->getAlert()) {
            return Sensor::STATUS_ALERT;
        }

        /** @var MeasurementRepository $measurementRepository */
        $measurementRepository = $this->getEntityManager()->getRepository(Measurement::class);

        $criticalValue = Measurement::getCo2CriticalValue();
        $measurements = $measurementRepository->getLatestMeasurements($sensor, 1);
        if ($measurements && MeasurementRepository::countExceededTimes($measurements, $criticalValue)) {
            return Sensor::STATUS_WARN;
        }

        return Sensor::STATUS_OK;
    }
}
