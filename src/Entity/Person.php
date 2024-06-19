<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
class Person
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
    #[ORM\OneToMany(targetEntity: Award::class, mappedBy: 'person', orphanRemoval: true)]
    private Collection $awards;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birth = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $death = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageLicense = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $imageCreator = null;

    #[ORM\Column]
    private ?int $countAwards = 0;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Country>
     */
    #[ORM\ManyToMany(targetEntity: Country::class, inversedBy: 'persons')]
    private Collection $countries;

    public function __construct()
    {
        $this->awards = new ArrayCollection();
        $this->countries = new ArrayCollection();
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
        $iterator = $this->awards->getIterator();
        $iterator->uasort(fn (Award $a, Award $b) => $a->getDisplayDate() <=> $b->getDisplayDate());
        // Return a new ArrayCollection
        return new ArrayCollection(iterator_to_array($iterator));
    }

    public function addAward(Award $award): static
    {
        if (!$this->awards->contains($award)) {
            $this->awards->add($award);
            $award->setPerson($this);
        }

        return $this;
    }

    public function removeAward(Award $award): static
    {
        if ($this->awards->removeElement($award)) {
            // set the owning side to null (unless already changed)
            if ($award->getPerson() === $this) {
                $award->setPerson(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getImageThumbnailUrl(int $width = 200): ?string
    {
        if (is_null($this->getImage())) {
            return null;
        }
        $filenameUnderscores = $this->getImage();
        $filenameUnderscores = urldecode($filenameUnderscores);
        $filenameUnderscores = str_replace(' ', '_', $filenameUnderscores);
        $filenameUnderscores = urldecode($filenameUnderscores);
        $md5 = md5($filenameUnderscores);
        $output = sprintf(
            'https://upload.wikimedia.org/wikipedia/commons/thumb/%s/%s/%s/%spx-%s',
            substr($md5, 0, 1),
            substr($md5, 0, 2),
            $filenameUnderscores,
            $width,
            $filenameUnderscores
        );

        if (!str_ends_with($output, '.jpg')) {
            $output .= '.jpg';
        }
        return $output;
    }

    public function getCommonsUrl(): ?string {
        return "https://commons.wikimedia.org/wiki/File:" . $this->getImage();
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getBirth(): ?\DateTimeInterface
    {
        return $this->birth;
    }

    public function setBirth(?\DateTimeInterface $birth): static
    {
        $this->birth = $birth;

        return $this;
    }

    public function getDeath(): ?\DateTimeInterface
    {
        return $this->death;
    }

    public function setDeath(?\DateTimeInterface $death): static
    {
        $this->death = $death;

        return $this;
    }

    public function getImageLicense(): ?string
    {
        return $this->imageLicense;
    }

    public function setImageLicense(?string $imageLicense): static
    {
        $this->imageLicense = $imageLicense;

        return $this;
    }

    public function getImageCreator(): ?string
    {
        return $this->imageCreator;
    }

    public function setImageCreator(?string $imageCreator): static
    {
        $this->imageCreator = $imageCreator;

        return $this;
    }

    public function getCountAwards(): ?int
    {
        return $this->countAwards;
    }

    public function setCountAwards(int $countAwards): static
    {
        $this->countAwards = $countAwards;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getDisplayGender(): ?string {
        if (is_null($this->gender)) {
            return "?";
        } elseif ($this->gender == "Q48270") {
            return "⚪︎";
        } elseif ($this->gender == "Q6581072") {
            return "♀";
        } elseif ($this->gender == "Q6581097") {
            return "♂";
        }
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Country>
     */
    public function getCountries(): Collection
    {
        return $this->countries;
    }

    public function addCountry(Country $country): static
    {
        if (!$this->countries->contains($country)) {
            $this->countries->add($country);
        }

        return $this;
    }

    public function removeCountry(Country $country): static
    {
        $this->countries->removeElement($country);

        return $this;
    }
}
