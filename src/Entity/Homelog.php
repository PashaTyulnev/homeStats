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

    #[ORM\Column(nullable: true)]
    private ?float $co2value = null;

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

    public function getCo2value(): ?float
    {
        return $this->co2value;
    }

    public function setCo2value(?float $co2value): static
    {
        $this->co2value = $co2value;

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
