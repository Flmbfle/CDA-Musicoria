<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[UniqueEntity('libelle')]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2, max: 50)]
    private ?string $libelle = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank()]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    private ?string $slug = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank()]
    private ?string $image = null;

    #[ORM\Column]
    #[Assert\NotNull()]
    private ?int $stock = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    private ?Categorie $categorie = null;

    #[ORM\Column]
    #[Assert\Positive()]
    #[Assert\NotNull()]
    private ?float $prixAchat = null;

    #[ORM\Column]
    #[Assert\Positive()]
    #[Assert\NotNull()]
    private ?float $prixVente = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    // Nouveau getter enrichi
    public function getImageUrl(): string
    {
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            // Si l'image est une URL, on la retourne telle quelle
            return $this->image;
        }
    
        // Si l'image est un chemin local, on ajoute le chemin relatif du dossier public
        return '/images/' . $this->image;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getfournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setfournisseur(?Fournisseur $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getcategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setcategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getPrixAchat(): ?float
    {
        return $this->prixAchat;
    }

    public function setPrixAchat(float $prixAchat): static
    {
        $this->prixAchat = $prixAchat;

        return $this;
    }

    public function getPrixVente(): ?float
    {
        return $this->prixVente;
    }

    public function setPrixVente(float $prixVente): static
    {
        $this->prixVente = $prixVente;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
}
