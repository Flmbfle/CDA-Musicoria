<?php

namespace App\Repository;

use App\Entity\Produit;
use App\Entity\Categorie;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    // Ajoutez une méthode pour récupérer les produits par sous-catégorie
    public function findByCategorie(Categorie $categorie)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.categorie = :categorie')
            ->setParameter('categorie', $categorie)
            ->getQuery()
            ->getResult();
    }

    // Méthode pour recuperer les produits les plus vendu
    public function getTopSellingProducts()
    {
        return $this->createQueryBuilder('p')
            ->select('p.nom, SUM(c.quantite) AS totalSold')
            ->join('p.commandes', 'c')
            ->groupBy('p.id')
            ->orderBy('totalSold', 'DESC')
            ->setMaxResults(5)  // Limiter aux 5 produits les plus vendus
            ->getQuery()
            ->getResult();
    }

}
