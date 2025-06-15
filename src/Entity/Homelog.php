<?php

namespace App\Entity;

use App\Repository\HomelogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HomelogRepository::class)]
class Homelog
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
    private ?float $dustDensity = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $datetime = null;

    #[ORM\Column(name: "co2value", nullable: true)]
    private ?float $co2Value = null;

    #[ORM\Column(nullable: true)]
    private ?float $co2temp = null;

    #[ORM\Column(nullable: true)]
    private ?float $tempOutside = null;

    #[ORM\Column(nullable: true)]
    private ?float $dustDensityAverage = null;

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

    public function getco2Value(): ?float
    {
        return $this->co2Value;
    }

    public function setco2Value(?float $co2Value): static
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

    public function getTempOutside(): ?float
    {
        return $this->tempOutside;
    }

    public function setTempOutside(?float $tempOutside): static
    {
        $this->tempOutside = $tempOutside;

        return $this;
    }

    public function getDustDensityAverage(): ?float
    {
        return $this->dustDensityAverage;
    }

    public function setDustDensityAverage(?float $dustDensityAverage): static
    {
        $this->dustDensityAverage = $dustDensityAverage;

        return $this;
    }
}
