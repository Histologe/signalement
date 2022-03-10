<?php

namespace App\Entity;

use App\Repository\ConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConfigRepository::class)]
class Config
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $nomTerritoire;

    #[ORM\Column(type: 'string', length: 255)]
    private $urlTerritoire;

    #[ORM\Column(type: 'string', length: 255)]
    private $nomDpo;

    #[ORM\Column(type: 'string', length: 255)]
    private $mailDpo;

    #[ORM\Column(type: 'string', length: 255)]
    private $nomResponsable;

    #[ORM\Column(type: 'string', length: 255)]
    private $mailResponsable;

    #[ORM\Column(type: 'string', length: 255)]
    private $adresseDpo;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\File(maxSize: '2048k',mimeTypes: "image/*")]
    private $logotype;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $emailReponse;

    #[ORM\Column(type: 'text', nullable: true)]
    private $trackingCode;

    #[ORM\Column(type: 'text', nullable: true)]
    private $tagManagerCode;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomTerritoire(): ?string
    {
        return $this->nomTerritoire;
    }

    public function setNomTerritoire(string $nomTerritoire): self
    {
        $this->nomTerritoire = $nomTerritoire;

        return $this;
    }

    public function getUrlTerritoire(): ?string
    {
        return $this->urlTerritoire;
    }

    public function setUrlTerritoire(string $urlTerritoire): self
    {
        $this->urlTerritoire = $urlTerritoire;

        return $this;
    }

    public function getNomDpo(): ?string
    {
        return $this->nomDpo;
    }

    public function setNomDpo(string $nomDpo): self
    {
        $this->nomDpo = $nomDpo;

        return $this;
    }

    public function getMailDpo(): ?string
    {
        return $this->mailDpo;
    }

    public function setMailDpo(string $mailDpo): self
    {
        $this->mailDpo = $mailDpo;

        return $this;
    }

    public function getNomResponsable(): ?string
    {
        return $this->nomResponsable;
    }

    public function setNomResponsable(string $nomResponsable): self
    {
        $this->nomResponsable = $nomResponsable;

        return $this;
    }

    public function getMailResponsable(): ?string
    {
        return $this->mailResponsable;
    }

    public function setMailResponsable(string $mailResponsable): self
    {
        $this->mailResponsable = $mailResponsable;

        return $this;
    }

    public function getAdresseDpo(): ?string
    {
        return $this->adresseDpo;
    }

    public function setAdresseDpo(string $adresseDpo): self
    {
        $this->adresseDpo = $adresseDpo;

        return $this;
    }

    public function getLogotype(): ?string
    {
        return $this->logotype;
    }

    public function setLogotype(?string $logotype): self
    {
        $this->logotype = $logotype;

        return $this;
    }

    public function getEmailReponse(): ?string
    {
        return $this->emailReponse;
    }

    public function setEmailReponse(?string $emailReponse): self
    {
        $this->emailReponse = $emailReponse;

        return $this;
    }

    public function getTrackingCode(): ?string
    {
        return $this->trackingCode;
    }

    public function setTrackingCode(?string $trackingCode): self
    {
        $this->trackingCode = $trackingCode;

        return $this;
    }

    public function getTagManagerCode(): ?string
    {
        return $this->tagManagerCode;
    }

    public function setTagManagerCode(?string $tagManagerCode): self
    {
        $this->tagManagerCode = $tagManagerCode;

        return $this;
    }
}
