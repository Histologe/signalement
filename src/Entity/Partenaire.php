<?php

namespace App\Entity;

use App\Repository\PartenaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity(repositoryClass: PartenaireRepository::class)]
class Partenaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $nom;

    #[ORM\OneToMany(mappedBy: 'partenaire', targetEntity: User::class)]
    private $users;

    #[ORM\OneToMany(mappedBy: 'partenaire', targetEntity: SignalementUserAffectation::class)]
    private $affectations;

    #[ORM\Column(type: 'boolean')]
    private $isArchive;

    #[ORM\Column(type: 'boolean')]
    private $isCommune;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private $insee;

    #[ORM\OneToMany(mappedBy: 'partenaire', targetEntity: Cloture::class, orphanRemoval: true)]
    private $clotures;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->isArchive = false;
        $this->clotures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users->filter(function (User $user){
            if($user->getStatut() !== User::STATUS_ARCHIVE)
                return $user;
        });
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setPartenaire($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getPartenaire() === $this) {
                $user->setPartenaire(null);
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

    public function getIsArchive(): ?bool
    {
        return $this->isArchive;
    }

    public function setIsArchive(bool $isArchive): self
    {
        $this->isArchive = $isArchive;

        return $this;
    }

    public function getIsCommune(): ?bool
    {
        return $this->isCommune;
    }

    public function setIsCommune(bool $isCommune): self
    {
        $this->isCommune = $isCommune;

        return $this;
    }

    public function getInsee(): ?string
    {
        return $this->insee;
    }

    public function setInsee(?string $insee): self
    {
        $this->insee = $insee;

        return $this;
    }

    public function getUsersAffectable(Signalement $signalement): ArrayCollection|PersistentCollection
    {
        $usersGenerique = $this->users->filter(function (User $user)use ($signalement){
            if($user->getIsGenerique() && !$user->isAffectedTo($signalement))
            {
                return $user;
            }

        });
        if(!$usersGenerique->isEmpty())
            return $usersGenerique;
        return $this->users->filter(function (User $user)use ($signalement){
            if(!$user->isAffectedTo($signalement) && !$this->hasGeneriqueUsers())
                return $user;
        });
    }

    public function getUsersAffected(Signalement $signalement): ArrayCollection|PersistentCollection
    {
        $usersGenerique = $this->users->filter(function (User $user)use ($signalement){
            if($user->getIsGenerique() && $user->isAffectedTo($signalement))
                return $user;
        });
        if(!$usersGenerique->isEmpty())
            return $usersGenerique;
        return $this->users->filter(function (User $user)use ($signalement){
            if($user->isAffectedTo($signalement))
                return $user;
        });
    }

    public function hasGeneriqueUsers(): bool
    {
        foreach ($this->users as $user)
            if($user->getIsGenerique())
                return  true;
        return false;
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
            $cloture->setPartenaire($this);
        }

        return $this;
    }

    public function removeCloture(Cloture $cloture): self
    {
        if ($this->clotures->removeElement($cloture)) {
            // set the owning side to null (unless already changed)
            if ($cloture->getPartenaire() === $this) {
                $cloture->setPartenaire(null);
            }
        }

        return $this;
    }
}
