<? 

namespace App\Controller\Admin;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
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
    public function utilisateurListe(EntityManagerInterface $manager): Response
    {
        // Récupère tous les utilisateurs
        $utilisateurs = $manager->getRepository(Utilisateur::class)->findAll();

        return $this->render('admin/utilisateur.html.twig', [
            'utilisateurs' => $utilisateurs,
        ]);
    }

    #[Route('/admin/commandes', name: 'commande.liste', methods: ['GET'])]
    public function listeCommandes(EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN'); // Vérifie que l'utilisateur est un admin

        $commandes = $manager->getRepository(Commande::class)->findAll();

        return $this->render('admin/commande.html.twig', [
            'commandes' => $commandes,
        ]);
    }
}