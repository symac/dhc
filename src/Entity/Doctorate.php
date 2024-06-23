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

    /**
     * @var Collection<int, University>
     */
    #[ORM\ManyToMany(targetEntity: University::class, inversedBy: 'doctorates')]
    private Collection $universities;

    public function __construct()
    {
        $this->awards = new ArrayCollection();
        $this->universities = new ArrayCollection();
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

    public function countAwards(): int {
        return $this->awards->count();
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

    /**
     * @return Collection<int, University>
     */
    public function getUniversities(): Collection
    {
        return $this->universities;
    }

    public function getUniversity(): University
    {
        return $this->universities->first();
    }


    public function addUniversity(University $university): static
    {
        if (!$this->universities->contains($university)) {
            $this->universities->add($university);
        }

        return $this;
    }

    public function removeUniversity(University $university): static
    {
        $this->universities->removeElement($university);

        return $this;
    }

}
