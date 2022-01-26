<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_ARCHIVE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(type: 'string', nullable: true)]
    private $password;

    #[ORM\OneToMany(mappedBy: 'modifiedBy', targetEntity: Signalement::class)]
    private $signalementsModified;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: SignalementUserAffectation::class)]
    private $affectations;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Suivi::class, orphanRemoval: true)]
    private $suivis;

    #[ORM\ManyToOne(targetEntity: Partenaire::class, inversedBy: 'users')]
    private $partenaire;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $nom;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $prenom;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: UserReport::class)]
    private $userReports;

    #[ORM\Column(type: 'integer')]
    private $statut;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $lastLoginAt;

    private $newsActivitiesSinceLastLogin;

    #[ORM\Column(type: 'boolean')]
    private $isGenerique;

    #[ORM\Column(type: 'boolean')]
    private $isMailingActive;

    public function __construct()
    {
        $this->affectations = new ArrayCollection();
        $this->suivis = new ArrayCollection();
        $this->userReports = new ArrayCollection();
        $this->statut = self::STATUS_INACTIVE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string|null
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|Signalement[]
     */
    public function getSignalementsModified(): Collection
    {
        return $this->signalementsModified;
    }

    public function addSignalementModified(Signalement $signalement): self
    {
        if (!$this->signalementsModified->contains($signalement)) {
            $this->signalementsModified[] = $signalement;
            $signalement->setModifiedBy($this);
        }

        return $this;
    }

    public function removeSignalementModified(Signalement $signalement): self
    {
        if ($this->signalementsModified->removeElement($signalement)) {
            // set the owning side to null (unless already changed)
            if ($signalement->getModifiedBy() === $this) {
                $signalement->setModifiedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Signalement[]
     */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    /**
     * @return Collection|Suivi[]
     */
    public function getSuivis(): Collection
    {
        return $this->suivis;
    }

    public function addSuivi(Suivi $suivi): self
    {
        if (!$this->suivis->contains($suivi)) {
            $this->suivis[] = $suivi;
            $suivi->setCreatedBy($this);
        }

        return $this;
    }

    public function removeSuivi(Suivi $suivi): self
    {
        if ($this->suivis->removeElement($suivi)) {
            // set the owning side to null (unless already changed)
            if ($suivi->getCreatedBy() === $this) {
                $suivi->setCreatedBy(null);
            }
        }

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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNomComplet()
    {
        return mb_strtoupper($this->nom) . ' ' . ucfirst($this->prenom);
    }

    /**
     * @return Collection|UserReport[]
     */
    public function getUserReports(): Collection
    {
        return $this->userReports;
    }

    public function addUserReport(UserReport $userReport): self
    {
        if (!$this->userReports->contains($userReport)) {
            $this->userReports[] = $userReport;
            $userReport->setCreatedBy($this);
        }

        return $this;
    }

    public function removeUserReport(UserReport $userReport): self
    {
        if ($this->userReports->removeElement($userReport)) {
            // set the owning side to null (unless already changed)
            if ($userReport->getCreatedBy() === $this) {
                $userReport->setCreatedBy(null);
            }
        }

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

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getIsGenerique(): ?bool
    {
        return $this->isGenerique;
    }

    public function setIsGenerique(bool $isGenerique): self
    {
        $this->isGenerique = $isGenerique;

        return $this;
    }

    public function isAffectedTo(Signalement $signalement): bool
    {
        foreach ($this->affectations as $affectation)
            if ($affectation->getSignalement()->getId() === $signalement->getId())
                return true;
        return false;
    }

    public function getIsMailingActive(): ?bool
    {
        return $this->isMailingActive;
    }

    public function setIsMailingActive(bool $isMailingActive): self
    {
        $this->isMailingActive = $isMailingActive;

        return $this;
    }


}
