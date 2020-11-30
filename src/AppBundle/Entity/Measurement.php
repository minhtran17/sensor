<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * Measurement
 *
 * @ORM\Table(name="measurement")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MeasurementRepository")
 */
class Measurement
{
    const CRITICAL_VALUE = [
        Sensor::TYPE_CO2 => 2000,
    ];

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
     * @ORM\ManyToOne( targetEntity="AppBundle\Entity\Sensor", inversedBy="measurements" )
     * @ORM\JoinColumn( name="sensor_id", referencedColumnName="id" )
     */
    private $sensor;

    /**
     * @var int
     *
     * @ORM\Column(name="value", type="integer")
     * @Assert\NotBlank(message="Measurement Value must not be blank")
     * @Assert\GreaterThan(value = 0, message="Measurement Value must be greater than 0")
     */
    private $value;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var Alert
     *
     * @ORM\ManyToOne( targetEntity="AppBundle\Entity\Alert", inversedBy="measurements" )
     * @ORM\JoinColumn( name="alert_id", referencedColumnName="id" )
     */
    private $alert;

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
     * @return int
     */
    public function getValue(): ?int
    {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function setValue(int $value)
    {
        $this->value = $value;
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
     * @return Alert
     */
    public function getAlert(): ?Alert
    {
        return $this->alert;
    }

    /**
     * @param Alert $alert
     */
    public function setAlert(Alert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * @return int
     */
    public static function getCo2CriticalValue(): int
    {
        return self::CRITICAL_VALUE[Sensor::TYPE_CO2];
    }
}
