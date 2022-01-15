<?php

namespace App\Entity;

use App\Repository\UserReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserReportRepository::class)]
class UserReport
{

    const STATUS_AWAIT = 0;
    const STATUS_VIEWED = 1;
    const STATUS_CORRECTED = 2;
    const STATUS_IS_NOT_BUG = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userReports')]
    #[ORM\JoinColumn(nullable: false)]
    private $createdBy;

    #[ORM\Column(type: 'text')]
    private $content;

    #[ORM\Column(type: 'integer')]
    private $statut;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $capture;

    #[ORM\Column(type: 'string', length: 255)]
    private $url;

    #[ORM\Column(type: 'string', length: 255)]
    private $route;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->statut = self::STATUS_AWAIT;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

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

    public function getCapture(): ?string
    {
        return $this->capture;
    }

    public function setCapture(?string $capture): self
    {
        $this->capture = $capture;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;

        return $this;
    }
}
