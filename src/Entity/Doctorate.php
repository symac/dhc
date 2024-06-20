<?php

namespace App\Entity;

use App\Repository\DoctorateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctorateRepository::class)]
class Doctorate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 12)]
    private ?string $qid = null;

    #[ORM\Column(length: 512)]
    private ?string $label = null;

    /**
     * @var Collection<int, Award>
     */
    #[ORM\OneToMany(targetEntity: Award::class, mappedBy: 'doctorate', orphanRemoval: true)]
    private Collection $awards;

    #[ORM\OneToOne(mappedBy: 'doctorate', cascade: ['persist', 'remove'])]
    private ?University $university = null;
    public function __construct()
    {
        $this->awards = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQid(): ?string
    {
        return $this->qid;
    }

    public function setQid(string $qid): static
    {
        $this->qid = $qid;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection<int, Award>
     */
    public function getAwards(): Collection
    {
        // Sort by displayDate
        $iterator = $this->awards->getIterator();
        $iterator->uasort(function ($a, $b) {
            if ($a->getDisplayDate() === $b->getDisplayDate()) {
                return $a->getPerson()->getLabel() <=> $b->getPerson()->getLabel();
            }

            return $a->getDisplayDate() <=> $b->getDisplayDate();
        });
        // Return a new ArrayCollection
        return new ArrayCollection(iterator_to_array($iterator));
    }

    public function addAward(Award $award): static
    {
        if (!$this->awards->contains($award)) {
            $this->awards->add($award);
            $award->setDoctorate($this);
        }

        return $this;
    }

    public function removeAward(Award $award): static
    {
        if ($this->awards->removeElement($award)) {
            // set the owning side to null (unless already changed)
            if ($award->getDoctorate() === $this) {
                $award->setDoctorate(null);
            }
        }

        return $this;
    }

    public function getUniversity(): ?University
    {
        return $this->university;
    }

    public function setUniversity(?University $university): static
    {
        // unset the owning side of the relation if necessary
        if ($university === null && $this->university !== null) {
            $this->university->setDoctorate(null);
        }

        // set the owning side of the relation if necessary
        if ($university !== null && $university->getDoctorate() !== $this) {
            $university->setDoctorate($this);
        }

        $this->university = $university;

        return $this;
    }

    public function percent(string $qid = null) {
        // Q6581072 : féminin
        // Q6581097 : masculin
        $total = 0;
        $match = 0;

        foreach ($this->awards as $award) {
            if ( ($award->getPerson()->getGender() == "Q6581072") or ($award->getPerson()->getGender() == "Q6581097") ) {
                $total++;
                if ($award->getPerson()->getGender() == $qid) {
                    $match++;
                }
            } elseif (is_null($award->getPerson()->getGender()) && is_null($qid)) {
                $match++;
            }
        }
        if (is_null($qid)) {
            return $match;
        }

        return floor(($match/$total) * 100);
    }

}
