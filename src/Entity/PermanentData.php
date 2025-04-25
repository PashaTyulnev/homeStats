<?php

namespace App\Entity;

use App\Repository\HomelogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PermanentData::class)]
class PermanentData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $temperature = null;

    #[ORM\Column(nullable: true)]
    private ?float $humidity = null;

    #[ORM\Column(nullable: true)]
    private ?float $dustValue = null;

    #[ORM\Column(nullable: true)]
    private ?float $dustVoltage = null;

    #[ORM\Column(nullable: true)]
    private ?float $dustDensity = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $datetime = null;

    #[ORM\Column(nullable: true)]
    private ?float $co2Value = null;

    #[ORM\Column(nullable: true)]
    private ?float $co2temp = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getHumidity(): ?float
    {
        return $this->humidity;
    }

    public function setHumidity(?float $humidity): static
    {
        $this->humidity = $humidity;

        return $this;
    }

    public function getDustValue(): ?float
    {
        return $this->dustValue;
    }

    public function setDustValue(?float $dustValue): static
    {
        $this->dustValue = $dustValue;

        return $this;
    }

    public function getDustVoltage(): ?float
    {
        return $this->dustVoltage;
    }

    public function setDustVoltage(?float $dustVoltage): static
    {
        $this->dustVoltage = $dustVoltage;

        return $this;
    }

    public function getDustDensity(): ?float
    {
        return $this->dustDensity;
    }

    public function setDustDensity(?float $dustDensity): static
    {
        $this->dustDensity = $dustDensity;

        return $this;
    }

    public function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeInterface $datetime): static
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getCo2Value(): ?float
    {
        return $this->co2Value;
    }

    public function setCo2Value(?float $co2Value): static
    {
        $this->co2Value = $co2Value;

        return $this;
    }

    public function getCo2temp(): ?float
    {
        return $this->co2temp;
    }

    public function setCo2temp(?float $co2temp): static
    {
        $this->co2temp = $co2temp;

        return $this;
    }
}
