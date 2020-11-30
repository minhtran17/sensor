<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Alert
 *
 * @ORM\Table(name="alert")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AlertRepository")
 */
class Alert
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Sensor
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Sensor", inversedBy="alerts")
     * @ORM\JoinColumn(name="sensor_id", referencedColumnName="id")
     */
    private $sensor;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start", type="datetime")
     */
    private $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end", type="datetime", nullable=true)
     */
    private $end;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var Measurement[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Measurement", mappedBy="alert")
     */
    private $measurements = [];

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Sensor
     */
    public function getSensor(): Sensor
    {
        return $this->sensor;
    }

    /**
     * @param Sensor $sensor
     */
    public function setSensor(Sensor $sensor)
    {
        $this->sensor = $sensor;
    }

    /**
     * @return \DateTime
     */
    public function getStart(): \DateTime
    {
        return $this->start;
    }

    /**
     * @param \DateTime $start
     */
    public function setStart(\DateTime $start)
    {
        $this->start = $start;
    }

    /**
     * @return \DateTime
     */
    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    /**
     * @param \DateTime $end
     */
    public function setEnd(\DateTime $end)
    {
        $this->end = $end;
    }

    /**
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * @return Measurement[]
     */
    public function getMeasurements(): array
    {
        return $this->measurements;
    }

    /**
     * @param Measurement $measurement
     * @return $this
     */
    public function addMeasurements(Measurement $measurement)
    {
        foreach ($this->measurements as $item) {
            /** @var Measurement $item */
            if ($item->getId() === $measurement->getId()) {
                return $this;
            }
        }

        $this->measurements[] = $measurement;
        $measurement->setAlert($this);
        return $this;
    }
}
