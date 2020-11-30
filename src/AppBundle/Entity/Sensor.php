<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * Alert
 *
 * @ORM\Table(name="sensor", indexes={@ORM\Index(name="sensor_search_idx", columns={"type"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SensorRepository")
 */
class Sensor
{
    const TYPE_CO2 = 'co2';

    const STATUS_OK = 'OK';
    const STATUS_WARN = 'WARN';
    const STATUS_ALERT = 'ALERT';

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", nullable=false)
     * @Assert\Choice({"co2"})
     */
    private $type;

    /**
     * @var Alert
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Alert")
     * @ORM\JoinColumn(name="alert_id", referencedColumnName="id")
     */
    private $alert;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var Alert[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Alert", mappedBy="sensor")
     */
    private $alerts;

    /**
     * @var Measurement[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Measurement", mappedBy="sensor")
     */
    private $measurements;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return Alert|null
     */
    public function getAlert(): ?Alert
    {
        return $this->alert;
    }

    /**
     * @param Alert|null $alert
     */
    public function setAlert(?Alert $alert)
    {
        $this->alert = $alert;
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
     * @return \DateTime
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return Alert[]
     */
    public function getAlerts(): array
    {
        return $this->alerts;
    }

    /**
     * @return Measurement[]
     */
    public function getMeasurements(): array
    {
        return $this->measurements;
    }
}

