<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}
