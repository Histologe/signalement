<?php

namespace App\Entity;

use App\Repository\SignalementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

#[ORM\Entity(repositoryClass: SignalementRepository::class)]
class Signalement
{
    const STATUS_NEW = 'new';
    const STATUS_AWAIT = 'await';
    const STATUS_NEED_REVIEW = 'review';
    const STATUS_CLOSED = 'closed';
    const STATUS_ARCHIVED = 'archive';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $uuid;

    #[ORM\ManyToMany(targetEntity: Situation::class, inversedBy: 'signalements')]
    private $situations;

    #[ORM\ManyToMany(targetEntity: Critere::class, inversedBy: 'signalements')]
    private $criteres;

    #[ORM\ManyToMany(targetEntity: Criticite::class, inversedBy: 'signalements')]
    private $criticites;

    #[ORM\Column(type: 'text')]
    private $details;

    #[ORM\Column(type: 'json', nullable: true)]
    private $photos = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private $documents = [];

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isProprioAverti;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbAdultes;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbEnfantsM6;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbEnfantsP6;

    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    private $isAllocataire;

    #[ORM\Column(type: 'string', length: 25, nullable: true)]
    private $numAllocataire;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private $natureLogement;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private $typeLogement;

    #[ORM\Column(type: 'float', nullable: true)]
    private $superficie;

    #[ORM\Column(type: 'float', nullable: true)]
    private $loyer;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isBailEnCours;

    #[ORM\Column(type: 'date', nullable: true)]
    private $dateEntree;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $nomProprio;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $adresseProprio;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private $telProprio;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mailProprio;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isLogementSocial;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isPreavisDepart;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isRelogement;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isRefusIntervention;

    #[ORM\Column(type: 'text', nullable: true)]
    private $raisonRefusIntervention;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isNotOccupant;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $nomDeclarant;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $prenomDeclarant;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private $telDeclarant;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $mailDeclarant;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $structureDeclarant;

    #[ORM\Column(type: 'string', length: 50)]
    private $nomOccupant;

    #[ORM\Column(type: 'string', length: 50)]
    private $prenomOccupant;

    #[ORM\Column(type: 'string', length: 15)]
    private $telOccupant;

    #[ORM\Column(type: 'string', length: 50)]
    private $mailOccupant;

    #[ORM\Column(type: 'string', length: 100)]
    private $adresseOccupant;

    #[ORM\Column(type: 'integer')]
    private $cpOccupant;

    #[ORM\Column(type: 'string', length: 100)]
    private $villeOccupant;

    #[ORM\Column(type: 'boolean')]
    private $isCguAccepted;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $modifiedAt;


    #[ORM\Column(type: 'string', length: 25)]
    private $statut;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private $reference;

    #[ORM\Column(type: 'json')]
    private $jsonContent = [];

    #[ORM\Column(type: 'json')]
    private $geoloc = [];

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $dateVisite;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isOccupantPresentVisite;

    #[ORM\Column(type: 'float', nullable: true)]
    private $montantAllocation;

    #[ORM\Column(type: 'boolean')]
    private $isSituationHandicap;

    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: SignalementUserAffectation::class)]
    private $affectations;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'signalementsModified')]
    private $modifiedBy;

    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: Suivi::class, orphanRemoval: true)]
    private $suivis;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $codeProcedure;

    #[ORM\Column(type: 'float')]
    private $scoreCreation;

    #[ORM\Column(type: 'float', nullable: true)]
    private $scoreCloture;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $etageOccupant;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $escalierOccupant;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $numAppartOccupant;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $adresseAutreOccupant;

    #[ORM\Column(type: 'json', nullable: true)]
    private $modeContactProprio = [];

    public function __construct()
    {
        $this->situations = new ArrayCollection();
        $this->criteres = new ArrayCollection();
        $this->criticites = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->statut = self::STATUS_NEW;
        $this->affectations = new ArrayCollection();
        $this->uuid = uniqid();
        $this->isSituationHandicap = false;
        $this->isOccupantPresentVisite = false;
        $this->suivis = new ArrayCollection();
        $this->scoreCreation = 0;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Situation[]
     */
    public function getSituations(): Collection
    {
        return $this->situations;
    }

    public function addSituation(Situation $situation): self
    {
        if (!$this->situations->contains($situation)) {
            $this->situations[] = $situation;
        }

        return $this;
    }

    public function removeSituation(Situation $situation): self
    {
        $this->situations->removeElement($situation);

        return $this;
    }

    /**
     * @return Collection|Critere[]
     */
    public function getCriteres(): Collection
    {
        return $this->criteres;
    }

    public function addCritere(Critere $critere): self
    {
        if (!$this->criteres->contains($critere)) {
            $this->criteres[] = $critere;
        }

        return $this;
    }

    public function removeCritere(Critere $critere): self
    {
        $this->criteres->removeElement($critere);

        return $this;
    }

    /**
     * @return Collection|Criticite[]
     */
    public function getCriticites(): Collection
    {
        return $this->criticites;
    }

    public function addCriticite(Criticite $criticite): self
    {
        if (!$this->criticites->contains($criticite)) {
            $this->criticites[] = $criticite;
        }

        return $this;
    }

    public function removeCriticite(Criticite $criticite): self
    {
        $this->criticites->removeElement($criticite);

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getPhotos(): ?array
    {
        return $this->photos;
    }

    public function setPhotos(?array $photos): self
    {
        $this->photos = $photos;

        return $this;
    }

    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    public function setDocuments(?array $documents): self
    {
        $this->documents = $documents;

        return $this;
    }


    public function getIsProprioAverti(): ?bool
    {
        return $this->isProprioAverti;
    }

    public function setIsProprioAverti(bool $isProprioAverti): self
    {
        $this->isProprioAverti = $isProprioAverti;

        return $this;
    }

    public function getNbAdultes(): ?int
    {
        return $this->nbAdultes;
    }

    public function setNbAdultes(?int $nbAdultes): self
    {
        $this->nbAdultes = $nbAdultes;

        return $this;
    }

    public function getNbEnfantsM6(): ?int
    {
        return $this->nbEnfantsM6;
    }

    public function setNbEnfantsM6(?int $nbEnfantsM6): self
    {
        $this->nbEnfantsM6 = $nbEnfantsM6;

        return $this;
    }

    public function getNbEnfantsP6(): ?int
    {
        return $this->nbEnfantsP6;
    }

    public function setNbEnfantsP6(?int $nbEnfantsP6): self
    {
        $this->nbEnfantsP6 = $nbEnfantsP6;

        return $this;
    }

    public function getIsAllocataire(): ?string
    {
        return $this->isAllocataire;
    }

    public function setIsAllocataire(?string $isAllocataire)
    {
        $this->isAllocataire = $isAllocataire;

        return $this;
    }

    public function getNumAllocataire(): ?string
    {
        return $this->numAllocataire;
    }

    public function setNumAllocataire(?string $numAllocataire): self
    {
        $this->numAllocataire = $numAllocataire;

        return $this;
    }

    public function getNatureLogement(): ?string
    {
        return $this->natureLogement;
    }

    public function setNatureLogement(?string $natureLogement): self
    {
        $this->natureLogement = $natureLogement;

        return $this;
    }

    public function getTypeLogement(): ?string
    {
        return $this->typeLogement;
    }

    public function setTypeLogement(?string $typeLogement): self
    {
        $this->typeLogement = $typeLogement;

        return $this;
    }

    public function getSuperficie(): ?float
    {
        return $this->superficie;
    }

    public function setSuperficie(?float $superficie): self
    {
        $this->superficie = $superficie;

        return $this;
    }

    public function getLoyer(): ?float
    {
        return $this->loyer;
    }

    public function setLoyer(?float $loyer): self
    {
        $this->loyer = $loyer;

        return $this;
    }

    public function getIsBailEnCours(): ?bool
    {
        return $this->isBailEnCours;
    }

    public function setIsBailEnCours(?bool $isBailEnCours): self
    {
        $this->isBailEnCours = $isBailEnCours;

        return $this;
    }

    public function getDateEntree(): ?\DateTimeInterface
    {
        return $this->dateEntree;
    }

    public function setDateEntree(?\DateTimeInterface $dateEntree): self
    {
        $this->dateEntree = $dateEntree;

        return $this;
    }

    public function getNomProprio(): ?string
    {
        return $this->nomProprio;
    }

    public function setNomProprio(?string $nomProprio): self
    {
        $this->nomProprio = $nomProprio;

        return $this;
    }

    public function getAdresseProprio(): ?string
    {
        return $this->adresseProprio;
    }

    public function setAdresseProprio(?string $adresseProprio): self
    {
        $this->adresseProprio = $adresseProprio;

        return $this;
    }

    public function getTelProprio(): ?string
    {
        return $this->telProprio;
    }

    public function setTelProprio(?string $telProprio): self
    {
        $this->telProprio = $telProprio;

        return $this;
    }

    public function getMailProprio(): ?string
    {
        return $this->mailProprio;
    }

    public function setMailProprio(?string $mailProprio): self
    {
        $this->mailProprio = $mailProprio;

        return $this;
    }

    public function getIsLogementSocial(): ?bool
    {
        return $this->isLogementSocial;
    }

    public function setIsLogementSocial(?bool $isLogementSocial): self
    {
        $this->isLogementSocial = $isLogementSocial;

        return $this;
    }

    public function getIsPreavisDepart(): ?bool
    {
        return $this->isPreavisDepart;
    }

    public function setIsPreavisDepart(?bool $isPreavisDepart): self
    {
        $this->isPreavisDepart = $isPreavisDepart;

        return $this;
    }

    public function getIsRelogement(): ?bool
    {
        return $this->isRelogement;
    }

    public function setIsRelogement(?bool $isRelogement): self
    {
        $this->isRelogement = $isRelogement;

        return $this;
    }

    public function getIsRefusIntervention(): ?bool
    {
        return $this->isRefusIntervention;
    }

    public function setIsRefusIntervention(?bool $isRefusIntervention): self
    {
        $this->isRefusIntervention = $isRefusIntervention;

        return $this;
    }

    public function getRaisonRefusIntervention(): ?string
    {
        return $this->raisonRefusIntervention;
    }

    public function setRaisonRefusIntervention(?string $raisonRefusIntervention): self
    {
        $this->raisonRefusIntervention = $raisonRefusIntervention;

        return $this;
    }

    public function getIsNotOccupant(): ?bool
    {
        return $this->isNotOccupant;
    }

    public function setIsNotOccupant(?bool $isNotOccupant): self
    {
        $this->isNotOccupant = $isNotOccupant;

        return $this;
    }

    public function getNomDeclarant(): ?string
    {
        return $this->nomDeclarant;
    }

    public function setNomDeclarant(?string $nomDeclarant): self
    {
        $this->nomDeclarant = $nomDeclarant;

        return $this;
    }

    public function getPrenomDeclarant(): ?string
    {
        return $this->prenomDeclarant;
    }

    public function setPrenomDeclarant(?string $prenomDeclarant): self
    {
        $this->prenomDeclarant = $prenomDeclarant;

        return $this;
    }

    public function getTelDeclarant(): ?string
    {
        return $this->telDeclarant;
    }

    public function setTelDeclarant(?string $telDeclarant): self
    {
        $this->telDeclarant = $telDeclarant;

        return $this;
    }

    public function getMailDeclarant(): ?string
    {
        return $this->mailDeclarant;
    }

    public function setMailDeclarant(?string $mailDeclarant): self
    {
        $this->mailDeclarant = $mailDeclarant;

        return $this;
    }

    public function getStructureDeclarant(): ?string
    {
        return $this->structureDeclarant;
    }

    public function setStructureDeclarant(?string $structureDeclarant): self
    {
        $this->structureDeclarant = $structureDeclarant;

        return $this;
    }

    public function getNomOccupant(): ?string
    {
        return $this->nomOccupant;
    }

    public function setNomOccupant(string $nomOccupant): self
    {
        $this->nomOccupant = $nomOccupant;

        return $this;
    }

    public function getPrenomOccupant(): ?string
    {
        return $this->prenomOccupant;
    }

    public function setPrenomOccupant(string $prenomOccupant): self
    {
        $this->prenomOccupant = $prenomOccupant;

        return $this;
    }

    public function getTelOccupant(): ?string
    {
        return $this->telOccupant;
    }

    public function setTelOccupant(string $telOccupant): self
    {
        $this->telOccupant = $telOccupant;

        return $this;
    }

    public function getMailOccupant(): ?string
    {
        return $this->mailOccupant;
    }

    public function setMailOccupant(string $mailOccupant): self
    {
        $this->mailOccupant = $mailOccupant;

        return $this;
    }

    public function getAdresseOccupant(): ?string
    {
        return $this->adresseOccupant;
    }

    public function setAdresseOccupant(string $adresseOccupant): self
    {
        $this->adresseOccupant = $adresseOccupant;

        return $this;
    }

    public function getCpOccupant(): ?int
    {
        return $this->cpOccupant;
    }

    public function setCpOccupant(int $cpOccupant): self
    {
        $this->cpOccupant = $cpOccupant;

        return $this;
    }

    public function getVilleOccupant(): ?string
    {
        return $this->villeOccupant;
    }

    public function setVilleOccupant(string $villeOccupant): self
    {
        $this->villeOccupant = $villeOccupant;

        return $this;
    }

    public function getIsCguAccepted(): ?bool
    {
        return $this->isCguAccepted;
    }

    public function setIsCguAccepted(bool $isCguAccepted): self
    {
        $this->isCguAccepted = $isCguAccepted;

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

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?\DateTimeImmutable $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    public function getModifiedBy(): ?User
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(?User $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }


    public function getJsonContent(): ?array
    {
        return $this->jsonContent;
    }

    public function setJsonContent(array $jsonContent): self
    {
        $this->jsonContent = $jsonContent;

        return $this;
    }

    public function getGeoloc(): ?array
    {
        return $this->geoloc;
    }

    public function setGeoloc(array $geoloc): self
    {
        $this->geoloc = $geoloc;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getDateVisite(): ?\DateTimeImmutable
    {
        return $this->dateVisite;
    }

    public function setDateVisite(?\DateTimeImmutable $dateVisite): self
    {
        $this->dateVisite = $dateVisite;

        return $this;
    }

    public function getIsOccupantPresentVisite(): ?bool
    {
        return $this->isOccupantPresentVisite;
    }

    public function setIsOccupantPresentVisite(?bool $isOccupantPresentVisite): self
    {
        $this->isOccupantPresentVisite = $isOccupantPresentVisite;

        return $this;
    }

    public function getMontantAllocation(): ?float
    {
        return $this->montantAllocation;
    }

    public function setMontantAllocation(?float $montantAllocation): self
    {
        $this->montantAllocation = $montantAllocation;

        return $this;
    }

    public function getIsSituationHandicap(): ?bool
    {
        return $this->isSituationHandicap;
    }

    public function setIsSituationHandicap(bool $isSituationHandicap): self
    {
        $this->isSituationHandicap = $isSituationHandicap;

        return $this;
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
            $suivi->setSignalement($this);
        }

        return $this;
    }

    public function removeSuivi(Suivi $suivi): self
    {
        if ($this->suivis->removeElement($suivi)) {
            // set the owning side to null (unless already changed)
            if ($suivi->getSignalement() === $this) {
                $suivi->setSignalement(null);
            }
        }

        return $this;
    }

    public function getCodeProcedure(): ?string
    {
        return $this->codeProcedure;
    }

    public function setCodeProcedure(?string $codeProcedure): self
    {
        $this->codeProcedure = $codeProcedure;

        return $this;
    }


    public function getAffectationStatusByPartenaire()
    {
        $result = [];
        foreach ($this->affectations as $affectation) {
            if (!array_keys($result, $affectation->getPartenaire()->getNom()))
                if (!isset($result[$affectation->getPartenaire()->getNom()]['statut'])) {
                    $result[$affectation->getPartenaire()->getId()]['partenaire'] = $affectation->getPartenaire()->getNom();
                    $result[$affectation->getPartenaire()->getId()]['statuses'][] = $affectation->getStatut();
                }
            $result[$affectation->getPartenaire()->getId()]['statut'] = max($result[$affectation->getPartenaire()->getId()]['statuses']);
        }
        return $result;
    }

    public function getScoreCreation(): ?float
    {
        return $this->scoreCreation;
    }

    public function setScoreCreation(float $scoreCreation): self
    {
        $this->scoreCreation = $scoreCreation;

        return $this;
    }

    public function getScoreCloture(): ?float
    {
        return $this->scoreCloture;
    }

    public function setScoreCloture(?float $scoreCloture): self
    {
        $this->scoreCloture = $scoreCloture;

        return $this;
    }

    public function getEtageOccupant(): ?int
    {
        return $this->etageOccupant;
    }

    public function setEtageOccupant(?int $etageOccupant): self
    {
        $this->etageOccupant = $etageOccupant;

        return $this;
    }

    public function getEscalierOccupant(): ?int
    {
        return $this->escalierOccupant;
    }

    public function setEscalierOccupant(?int $escalierOccupant): self
    {
        $this->escalierOccupant = $escalierOccupant;

        return $this;
    }

    public function getNumAppartOccupant(): ?int
    {
        return $this->numAppartOccupant;
    }

    public function setNumAppartOccupant(?int $numAppartOccupant): self
    {
        $this->numAppartOccupant = $numAppartOccupant;

        return $this;
    }

    public function getAdresseAutreOccupant(): ?string
    {
        return $this->adresseAutreOccupant;
    }

    public function setAdresseAutreOccupant(?string $adresseAutreOccupant): self
    {
        $this->adresseAutreOccupant = $adresseAutreOccupant;

        return $this;
    }

    public function getModeContactProprio(): ?array
    {
        return $this->modeContactProprio;
    }

    public function setModeContactProprio(?array $modeContactProprio): self
    {
        $this->modeContactProprio = $modeContactProprio;

        return $this;
    }

}
