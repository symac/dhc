<?php

namespace App\Entity;

use App\Repository\AwardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AwardRepository::class)]
class Award
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'awards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Person $person = null;

    #[ORM\ManyToOne(inversedBy: 'awards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Doctorate $doctorate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $p585 = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $P6949 = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $displayDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): static
    {
        $this->person = $person;

        return $this;
    }

    public function getDoctorate(): ?Doctorate
    {
        return $this->doctorate;
    }

    public function setDoctorate(?Doctorate $doctorate): static
    {
        $this->doctorate = $doctorate;

        return $this;
    }

    public function getP585(): ?\DateTimeInterface
    {
        return $this->p585;
    }

    public function setP585(?\DateTimeInterface $p585): static
    {
        $this->p585 = $p585;

        return $this;
    }

    public function getP6949(): ?\DateTimeInterface
    {
        return $this->P6949;
    }

    public function setP6949(?\DateTimeInterface $P6949): static
    {
        $this->P6949 = $P6949;

        return $this;
    }

    public function getDisplayDate(): ?\DateTimeInterface
    {
        return $this->displayDate;
    }

    public function setDisplayDate(?\DateTimeInterface $displayDate): static
    {
        $this->displayDate = $displayDate;

        return $this;
    }
}
