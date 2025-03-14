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
    
    public function getVentesParClient(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.utilisateur AS utilisateur, SUM(c.prixTTC) AS chiffreAffaires')
            ->where('c.status = :status')
            ->setParameter('status', StatutCommande::VALIDEE)// Statut de commande validée
            ->groupBy('c.utilisateur') // Grouper par utilisateur
            ->orderBy('chiffreAffaires', 'DESC')// Trier par chiffre d'affaires
            ->getQuery()
            ->getResult();
    }

    public function getNombreCommandesParUtilisateur(): array
    {
        return $this->createQueryBuilder('c')
            ->select('u.id AS clientId, COUNT(c.id) AS nombreCommandes')
            ->innerJoin('c.utilisateur', 'u')
            ->where('c.status = :status')
            ->setParameter('status', StatutCommande::VALIDEE)
            ->groupBy('u.id')
            ->orderBy('nombreCommandes', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    

    public function getChiffreAffairesMensuel(): array
    {
        $sql = "SELECT MONTH(c.created_at) AS mois, YEAR(c.created_at) AS annee, 
                    SUM(pp.quantite * pr.prix_vente) AS chiffreAffaires
                FROM commande c
                INNER JOIN panier p ON c.panier_id = p.id
                INNER JOIN panier_produit pp ON p.id = pp.panier_id
                INNER JOIN produit pr ON pp.produit_id = pr.id
                WHERE c.status = :status
                GROUP BY mois, annee
                ORDER BY annee DESC, mois DESC";
    
    $conn = $this->getEntityManager()->getConnection();
    $stmt = $conn->executeQuery($sql, ['status' => StatutCommande::VALIDEE->value]);
    
    // Toujours utiliser 'fetchAllAssociative()' ou 'fetchAll()' selon la version de Doctrine
    return $stmt->fetchAllAssociative();  // ou $stmt->fetchAll() si nécessaire
    
    }
    

    

    public function getTopProduitsVendus(): array
    {
    return $this->createQueryBuilder('c')
        ->select('pr.libelle AS produit, SUM(pp.quantite) AS quantiteVendue')
        ->innerJoin('c.panier', 'p')
        ->innerJoin('p.produits', 'pp')
        ->innerJoin('pp.produit', 'pr')
        ->where('c.status = :status')
        ->setParameter('status', StatutCommande::VALIDEE)
        ->groupBy('pr.id')
        ->orderBy('quantiteVendue', 'DESC')
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();
    }
    
    public function getTopProduitsRemunerateurs(): array
    {
        return $this->createQueryBuilder('c')
            ->select('pr.libelle AS produit, SUM(pp.quantite * pr.prixVente) AS chiffreAffaires')
            ->innerJoin('c.panier', 'p')
            ->innerJoin('p.produits', 'pp')
            ->innerJoin('pp.produit', 'pr')
            ->where('c.status = :status')
            ->setParameter('status', StatutCommande::VALIDEE)
            ->groupBy('pr.id')
            ->orderBy('chiffreAffaires', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
    
    public function getTopClients(): array
    {
    return $this->createQueryBuilder('c')
        ->select('u.nom AS client, COUNT(c.id) AS nombreCommandes, SUM(pp.quantite * pr.prixVente) AS chiffreAffaires')
        ->innerJoin('c.utilisateur', 'u')
        ->innerJoin('c.panier', 'p')
        ->innerJoin('p.produits', 'pp')
        ->innerJoin('pp.produit', 'pr')
        ->where('c.status = :status')
        ->setParameter('status', StatutCommande::VALIDEE)
        ->groupBy('u.id')
        ->orderBy('chiffreAffaires', 'DESC')
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();
    }

    public function getVentesParTypeClient()
    {
        return $this->createQueryBuilder('c')
        ->innerJoin('c.utilisateur', 'u')
        ->innerJoin('c.panier', 'p')
        ->innerJoin('p.produits', 'pp')
        ->innerJoin('pp.produit', 'pr')
        ->where('c.status = :status')
        ->setParameter('status', StatutCommande::VALIDEE)
        ->select('u.typeUtilisateur', 'SUM(pp.quantite * pr.prixVente) AS chiffreAffaires')
        ->groupBy('u.typeUtilisateur')
        ->getQuery()
        ->getResult();
    }

    public function getCommandesEnCoursLivraison(): int
    {
    return $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->where('c.status = :status')
        ->setParameter('status', StatutCommande::ENVOYE)
        ->getQuery()
        ->getSingleScalarResult();
    }

    public function countByRole(string $role): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"' . $role . '"%')
            ->getQuery()
            ->getSingleScalarResult();
    }


}
 