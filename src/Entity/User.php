<?php

namespace App\Entity;

use App\Repository\SignalementUserAffectationRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(type: 'string')]
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


    public function __construct()
    {
        $this->affectations = new ArrayCollection();
        $this->suivis = new ArrayCollection();
        $this->signalementsAccepted = new ArrayCollection();
        $this->signalementsRefused = new ArrayCollection();
        $this->userReports = new ArrayCollection();
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
    public function getPassword(): string
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

    /**
     * @return Collection|Signalement[]
     */
    public function getSignalementsAccepted(): Collection
    {
        return $this->signalementsAccepted;
    }

    public function addSignalementsAccepted(Signalement $signalementsAccepted): self
    {
        if (!$this->signalementsAccepted->contains($signalementsAccepted)) {
            $this->signalementsAccepted[] = $signalementsAccepted;
            $signalementsAccepted->addAcceptedBy($this);
        }

        return $this;
    }

    public function removeSignalementsAccepted(Signalement $signalementsAccepted): self
    {
        if ($this->signalementsAccepted->removeElement($signalementsAccepted)) {
            $signalementsAccepted->removeAcceptedBy($this);
        }

        return $this;
    }

    /**
     * @return Collection|Signalement[]
     */
    public function getSignalementsRefused(): Collection
    {
        return $this->signalementsRefused;
    }

    public function addSignalementsRefused(Signalement $signalementsRefused): self
    {
        if (!$this->signalementsRefused->contains($signalementsRefused)) {
            $this->signalementsRefused[] = $signalementsRefused;
            $signalementsRefused->addRefusedBy($this);
        }

        return $this;
    }

    public function removeSignalementsRefused(Signalement $signalementsRefused): self
    {
        if ($this->signalementsRefused->removeElement($signalementsRefused)) {
            $signalementsRefused->removeRefusedBy($this);
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

    public function isAffected(Signalement $signalement)
    {
        /** @var SignalementUserAffectation $affectation */
        foreach ($this->affectations as $affectation)
            if ($affectation->getSignalement() === $signalement)
                return true;
        return false;
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
}
