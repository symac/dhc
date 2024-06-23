<?php

namespace App\Entity;

use App\Repository\UniversityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UniversityRepository::class)]
class University
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 12)]
    private ?string $qid = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    /**
     * @var Collection<int, Doctorate>
     */
    #[ORM\ManyToMany(targetEntity: Doctorate::class, mappedBy: 'universities')]
    private Collection $doctorates;

    public function __construct()
    {
        $this->doctorates = new ArrayCollection();
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
     * @return Collection<int, Doctorate>
     */
    public function getDoctorates(): Collection
    {
        return $this->doctorates;
    }

    public function getDoctorate(): Doctorate {
        return $this->doctorates->first();
    }

    public function addDoctorate(Doctorate $doctorate): static
    {
        if (!$this->doctorates->contains($doctorate)) {
            $this->doctorates->add($doctorate);
            $doctorate->addUniversity($this);
        }

        return $this;
    }

    public function removeDoctorate(Doctorate $doctorate): static
    {
        if ($this->doctorates->removeElement($doctorate)) {
            $doctorate->removeUniversity($this);
        }

        return $this;
    }

    public function countAwards(): int
    {
        $count = 0;
        foreach ($this->doctorates as $doctorate) {
            $count += $doctorate->countAwards();
        }
        return $count;
    }

    public function percent(string $qid = null) {
        // Q6581072 : féminin
        // Q6581097 : masculin
        $total = 0;
        $match = 0;

        foreach ($this->doctorates as $doctorate) {
            foreach ($doctorate->getAwards() as $award) {
                if ( ($award->getPerson()->getGender() == "Q6581072") or ($award->getPerson()->getGender() == "Q6581097") ) {
                    $total++;
                    if ($award->getPerson()->getGender() == $qid) {
                        $match++;
                    }
                } elseif (is_null($award->getPerson()->getGender()) && is_null($qid)) {
                    $match++;
                }
            }

        }
        if (is_null($qid)) {
            return $match;
        }

        return floor(($match/$total) * 100);
    }

}
