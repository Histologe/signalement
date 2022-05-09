<?php

namespace App\Entity;

use App\Repository\AffectationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AffectationRepository::class)]
class Affectation
{
    const STATUS_WAIT = 0;
    const STATUS_ACCEPTED = 1;
    const STATUS_REFUSED = 2;
    const STATUS_CLOSED = 3;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Signalement::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    private $signalement;

    #[ORM\ManyToOne(targetEntity: Partenaire::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    private $partenaire;

    #[ORM\Column(type: 'datetime_immutable',nullable: true)]
    private $answeredAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\Column(type: 'integer')]
    private $statut;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $answeredBy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $affectedBy;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $motifCloture;

    public function __construct()
    {
        $this->statut = Affectation::STATUS_WAIT;
        $this->createdAt = new \DateTimeImmutable();
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

    public function getAnsweredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(\DateTimeImmutable $answeredAt): self
    {
        $this->answeredAt = $answeredAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function setStatut(int $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getAnsweredBy(): ?User
    {
        return $this->answeredBy;
    }

    public function setAnsweredBy(?User $answeredBy): self
    {
        $this->answeredBy = $answeredBy;

        return $this;
    }


    public function getAffectedBy(): ?User
    {
        return $this->affectedBy;
    }

    public function setAffectedBy(?User $affectedBy): self
    {
        $this->affectedBy = $affectedBy;

        return $this;
    }

    public function getMotifCloture(): ?string
    {
        return $this->motifCloture;
    }

    public function setMotifCloture(?string $motifCloture): self
    {
        $this->motifCloture = $motifCloture;

        return $this;
    }
}
