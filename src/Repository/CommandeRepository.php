<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Enum\StatutCommande;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function getTotalSales(): float
    {
        $qb = $this->createQueryBuilder('c')
            ->select('SUM(c.prixTTC)')  // Supposons que 'total' soit le montant total de la commande
            ->getQuery();

        return (float) $qb->getSingleScalarResult();
    }

    // src/Repository/CommandeRepository.php

    public function getVentesParProduit(): array
    {
        return $this->createQueryBuilder('c')
            ->select('pr.libelle AS produit, SUM(pp.quantite) AS quantiteVendue, SUM(pp.quantite * pr.prixVente) AS chiffreAffaires')
            ->innerJoin('c.panier', 'p')  // Jointure avec Panier
            ->innerJoin('p.produits', 'pp')  // Jointure avec PanierProduit
            ->innerJoin('pp.produit', 'pr')  // Jointure avec Produit (via PanierProduit)
            ->where('c.status = :status')
            ->setParameter('status', StatutCommande::VALIDEE)  // Statut de commande validée
            ->groupBy('pr.id')  // Grouper par produit
            ->orderBy('quantiteVendue', 'DESC')  // Trier par quantité vendue
            ->getQuery()
            ->getResult();
    }
    

}
