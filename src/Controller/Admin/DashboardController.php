<? 

namespace App\Controller\Admin;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Enum\StatutCommande;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardController extends AbstractController
{
    
    #[Route('/admin', name: 'admin.dashboard')]
    public function index(CommandeRepository $commandeRepo, UtilisateurRepository $utilisateurRepo): Response
    {
        // Récupérer les statistiques (total commandes, utilisateurs, etc.)
        $totalCommandes = $commandeRepo->count([]);  // Compte le nombre total de commandes
        $totalUsers = $utilisateurRepo->count([]);  // Compte le nombre total d'utilisateurs
        $totalSales = $commandeRepo->getTotalSales();  // Exemple de méthode personnalisée pour récupérer les ventes totales

        // Tu peux aussi récupérer des alertes ou d'autres données
        $alerts = ['Nouveau produit ajouté', 'Commande non expédiée'];

        return $this->render('admin/dashboard.html.twig', [
            'totalCommandes' => $totalCommandes,
            'totalUsers' => $totalUsers,
            'totalSales' => $totalSales,
            'alerts' => $alerts,
        ]);
    }

    #[Route('/admin/utilisateurs', name: 'utilisateur.liste')]
    public function utilisateurListe(EntityManagerInterface $manager, Request $request, PaginatorInterface $paginator): Response
    {
        // Vérification des droits d'accès
        $this->denyAccessUnlessGranted('ROLE_ADMIN'); // Vérifie que l'utilisateur est un admin
    
        // Récupérer les critères de recherche et de tri depuis la requête GET
        $search = $request->query->get('search');
        $sortField = $request->query->get('sort', 'u.nom'); // Champ de tri (par défaut tri par nom)
        $sortDirection = $request->query->get('direction', 'ASC'); // Direction de tri (par défaut ASC)
    
        // Créer la requête de base
        $queryBuilder = $manager->getRepository(Utilisateur::class)->createQueryBuilder('u');
    
        // Ajouter un filtre de recherche si le paramètre 'search' est présent
        if ($search) {
            $queryBuilder->where('u.nom LIKE :search')
                         ->orWhere('u.email LIKE :search') // Recherche sur email aussi
                         ->setParameter('search', '%' . $search . '%');
        }
    
        // Ajouter la clause de tri en fonction des paramètres
        if ($sortField === 'u.nom') {
            $queryBuilder->orderBy('u.nom', $sortDirection);
        } elseif ($sortField === 'u.email') {
            $queryBuilder->orderBy('u.email', $sortDirection);
        } else {
            $queryBuilder->orderBy($sortField, $sortDirection); // Si c'est un autre champ
        }
    
        // Appliquer la pagination
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête à paginer
            $request->query->getInt('page', 1), // Le numéro de page
            10 // Nombre d'éléments par page
        );
    
        // Rendre la vue
        return $this->render('admin/utilisateur.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'sortField' => $sortField,
            'sortDirection' => $sortDirection,
        ]);
    }

    #[Route('/admin/commandes', name: 'commande.liste', methods: ['GET'])]
    public function listeCommandes(EntityManagerInterface $manager, Request $request, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN'); // Vérifie que l'utilisateur est un admin
    
        // Récupérer les critères de recherche et de tri depuis la requête GET
        $search = $request->query->get('search');
        $sortField = $request->query->get('sort', 'c.reference'); // Champ de tri (par défaut tri par référence)
        $sortDirection = $request->query->get('direction', 'ASC'); // Direction de tri (par défaut ASC)
    
        // Créer la requête de base
        $queryBuilder = $manager->getRepository(Commande::class)->createQueryBuilder('c')
            ->leftJoin('c.utilisateur', 'u'); // Join avec l'utilisateur
    
        // Ajouter un filtre de recherche si le paramètre 'search' est présent
        if ($search) {
            $queryBuilder->where('c.reference LIKE :search')
                         ->orWhere('u.nom LIKE :search')
                         ->orWhere('c.prixTTC LIKE :search')
                         ->orWhere('c.status LIKE :search') // On recherche directement sur c.status
                         ->setParameter('search', '%' . $search . '%');
        }
    
        // Ajouter la clause de tri en fonction des paramètres
        if ($sortField === 'c.status') {
            $queryBuilder->orderBy('c.status', $sortDirection);
        } else {
            $queryBuilder->orderBy($sortField, $sortDirection);
        }
    
        // Appliquer la pagination
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête à paginer
            $request->query->getInt('page', 1), // Le numéro de page
            10 // Nombre d'éléments par page
        );
    
        return $this->render('admin/commande.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'sortField' => $sortField,
            'sortDirection' => $sortDirection,
        ]);
    }
    
    
    #[Route('/admin/stats', name: 'admin.stats')]
    public function stats(CommandeRepository $commandeRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        // Récupérer les données nécessaires pour le dashboard
        $ventesParProduit = $commandeRepository->getVentesParProduit();
    
        // Récupérer le nombre total de commandes
        $totalCommandes = $commandeRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')  // Compte le nombre de commandes
            ->where('c.status = :status')
            ->setParameter('status', StatutCommande::VALIDEE)  // Commandes validées par exemple
            ->getQuery()
            ->getSingleScalarResult();  // Retourne un seul résultat (le total)
        
        // Récupérer le nombre total d'utilisateurs
        $totalUsers = $utilisateurRepository->createQueryBuilder('u')
        ->select('COUNT(u.id)')  // Compte le nombre d'utilisateurs
        ->getQuery()
        ->getSingleScalarResult();  // Retourne un seul résultat (le total)
    
        // Organiser les données pour Chart.js
        $labels = [];
        $dataQuantite = [];
        $dataChiffreAffaires = [];
    
        foreach ($ventesParProduit as $vente) {
            $labels[] = $vente['produit'];
            $dataQuantite[] = $vente['quantiteVendue'];
            $dataChiffreAffaires[] = $vente['chiffreAffaires'];
        }
    
        // Passer les variables à la vue
        return $this->render('admin/stats.html.twig', [
            'labels' => $labels,
            'dataQuantite' => $dataQuantite,
            'dataChiffreAffaires' => $dataChiffreAffaires,
            'totalCommandes' => $totalCommandes,  // Passer la variable au template
            'totalUsers' => $totalUsers,  // Passer la variable au template
        ]);
    }
    
}