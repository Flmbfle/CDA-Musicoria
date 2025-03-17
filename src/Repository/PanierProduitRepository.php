<?php

namespace App\Repository;

use App\Entity\PanierProduit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PanierProduit>
 */
class PanierProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PanierProduit::class);
    }

    public function getTopSellingProducts()
    {
        return $this->createQueryBuilder('pp')
            ->select('p.id, p.libelle, p.image, p.slug,  SUM(pp.quantite) AS totalSold')
            //->join('App\Entity\Produit','p',\Doctrine\ORM\Query\Expr\Join::WITH, 'p = p.produits')  // jointure avec 
            ->join('pp.produit', 'p')           // Jointure avec la table produit
            ->groupBy('pp.produit')                  // Groupement par produit
            ->orderBy('totalSold', 'DESC')     // Tri des produits par quantité totale
            ->setMaxResults(5)                 // Limiter aux 5 produits les plus vendus
            ->getQuery()
            ->getResult();
    }


}
