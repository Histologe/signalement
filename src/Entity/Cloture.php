<?php

namespace App\Entity;

use App\Repository\ClotureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClotureRepository::class)]
class Cloture
{
    const TYPE_CLOTURE_PARTENAIRE = 1;
    const TYPE_CLOTURE_ALL = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Signalement::class, inversedBy: 'clotures')]
    #[ORM\JoinColumn(nullable: false)]
    private $signalement;

    #[ORM\ManyToOne(targetEntity: Partenaire::class, inversedBy: 'clotures')]
    #[ORM\JoinColumn(nullable: false)]
    private $partenaire;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'clotures')]
    #[ORM\JoinColumn(nullable: false)]
    private $closedBy;

    #[ORM\Column(type: 'datetime_immutable')]
    private $closedAt;

    #[ORM\ManyToOne(targetEntity: MotifCloture::class, inversedBy: 'clotures')]
    #[ORM\JoinColumn(nullable: false)]
    private $motif;

    #[ORM\Column(type: 'integer')]
    private $type;

    public function __construct()
    {
        $this->closedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(?Signalement $signalement): self
    {
        $this->signalement = $signalement;

        return $this;
    }

    public function getPartenaire(): ?Partenaire
    {
        return $this->partenaire;
    }

    public function setPartenaire(?Partenaire $partenaire): self
    {
        $this->partenaire = $partenaire;

        return $this;
    }

    public function getClosedBy(): ?User
    {
        return $this->closedBy;
    }

    public function setClosedBy(?User $closedBy): self
    {
        $this->closedBy = $closedBy;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(\DateTimeImmutable $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getMotif(): ?MotifCloture
    {
        return $this->motif;
    }

    public function setMotif(?MotifCloture $motif): self
    {
        $this->motif = $motif;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }
}
