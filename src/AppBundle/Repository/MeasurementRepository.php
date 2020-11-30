<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Alert;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\Sensor;

/**
 * Class MeasurementRepository
 * @package AppBundle\Repository
 */
class MeasurementRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param Sensor $sensor
     * @param int $limit
     * @return array
     */
    public function getLatestMeasurements(Sensor $sensor, int $limit): array
    {
        return $this->findBy(['sensor' => $sensor], ['id' => 'DESC'], $limit);
    }

    /**
     * @param Sensor $sensor
     * @param int $minExceed
     */
    public function detectCriticalStatus(Sensor $sensor, int $minExceed = 3)
    {
        $criticalValue = Measurement::getCo2CriticalValue();
        $measurements = $this->getLatestMeasurements($sensor, $minExceed);
        $exceededCount = self::countExceededTimes($measurements, $criticalValue);

        // Set alert if not yet alert status & at least 3 consecutive measurements exceeds
        $alert = $sensor->getAlert();
        if (!$alert && $exceededCount === $minExceed) {
            $this->setAlertCritical($sensor, $measurements);
            return;
        }

        // Set alert if already alert & submitted measurement exceeds critical value
        /** @var Measurement $measurement */
        $measurement = reset($measurements);
        if ($alert && $measurement && $measurement->getValue() > $criticalValue && !$measurement->getAlert()) {
            $measurement->setAlert($alert);
            $this->getEntityManager()->merge($measurement);
            $this->getEntityManager()->flush();
            return;
        }

        // Reset status if at least 3 consecutive measurements does not exceed
        if ($exceededCount === 0) {
            $this->setAlertOK($sensor);
        }
    }

    /**
     * @param Sensor $sensor
     * @param int $days
     * @return array
     */
    public function getMaxAndAvgMeasurementByDays(Sensor $sensor, int $days): array
    {
        $timeParam = (new \DateTime(sprintf('-%s days', $days)))->format('Y-m-d');
        $sql = 'SELECT sensor_id, MAX(value) as max, AVG(value) as avg
                FROM measurement
                WHERE sensor_id = :sensor_id
                    AND created >= :created
                GROUP BY sensor_id';
        $statement = $this->getEntityManager()->getConnection()->prepare($sql);
        $statement->bindValue('sensor_id', $sensor->getId());
        $statement->bindValue('created', $timeParam);
        $statement->execute();
        $result = $statement->fetch();

        return [
            'max' => $result['max'] ?? null,
            'avg' => $result['avg'] ?? null,
        ];
    }

    /**
     * @param Sensor $sensor
     * @param array $measurements
     */
    private function setAlertCritical(Sensor $sensor, array $measurements)
    {
        $alert = new Alert();
        $alert->setSensor($sensor);
        $alert->setStart(new \DateTime());
        $this->getEntityManager()->persist($alert);

        foreach ($measurements as $measurement) {
            $alert->addMeasurements($measurement);
        }

        $sensor->setAlert($alert);
        $this->getEntityManager()->merge($sensor);

        $this->getEntityManager()->flush();
    }

    /**
     * @param Sensor $sensor
     */
    private function setAlertOK(Sensor $sensor)
    {
        $alert = $sensor->getAlert();

        // Set Alert end time
        if ($alert) {
            $alert->setEnd(new \DateTime());
            $this->getEntityManager()->merge($alert);

            $sensor->setAlert(null);
            $this->getEntityManager()->merge($sensor);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param array $measurements
     * @param int $criticalValue
     * @return int
     */
    public static function countExceededTimes(array $measurements, int $criticalValue): int
    {
        return array_sum(
            array_map(
                function (Measurement $measurement) use ($criticalValue) {
                    return $measurement->getValue() > $criticalValue ? 1 : 0;
                },
                $measurements
            )
        );
    }
}
