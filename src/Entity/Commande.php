<?php

namespace App\Entity;

use App\Enum\StatutCommande;
use App\Repository\CommandeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'commande', cascade: ['persist', 'remove'])]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: Panier::class, cascade: ['persist'])]
    #[ORM\JoinColumn]
    private ?Panier $panier = null;

    #[ORM\Column]
    private ?float $prixHT = null;

    #[ORM\Column]
    private ?float $prixTTC = null;

    #[ORM\Column(enumType: StatutCommande::class)]
    private ?StatutCommande $status = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getPanier(): ?Panier
    {
        return $this->panier;
    }

    public function setPanier(?Panier $panier): static
    {
        $this->panier = $panier;

        return $this;
    }

    public function getPrixHT(): ?float
    {
        return $this->prixHT;
    }

    public function setPrixHT(float $prixHT): static
    {
        $this->prixHT = $prixHT;

        return $this;
    }

    public function getPrixTTC(): ?float
    {
        return $this->prixTTC;
    }

    public function setPrixTTC(float $prixTTC): static
    {
        $this->prixTTC = $prixTTC;

        return $this;
    }

    public function getStatus(): ?StatutCommande
    {
        return $this->status;
    }

    public function setStatus(StatutCommande $status): static
    {
        $this->status = $status;

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

    
}
