<?php

namespace App\Entity;

use App\Repository\MotifClotureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MotifClotureRepository::class)]
class MotifCloture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $label;

    #[ORM\OneToMany(mappedBy: 'motif', targetEntity: Cloture::class, orphanRemoval: true)]
    private $clotures;

    public function __construct()
    {
        $this->clotures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection|Cloture[]
     */
    public function getClotures(): Collection
    {
        return $this->clotures;
    }

    public function addCloture(Cloture $cloture): self
    {
        if (!$this->clotures->contains($cloture)) {
            $this->clotures[] = $cloture;
            $cloture->setMotif($this);
        }

        return $this;
    }

    public function removeCloture(Cloture $cloture): self
    {
        if ($this->clotures->removeElement($cloture)) {
            // set the owning side to null (unless already changed)
            if ($cloture->getMotif() === $this) {
                $cloture->setMotif(null);
            }
        }

        return $this;
    }
}
