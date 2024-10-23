<?php

namespace App\Controller;

use App\Entity\Materiel;
use App\Entity\Tva;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MaterielController extends AbstractController
{
    #[Route('/', name: 'app_materiel')]
    public function index(): Response
    {
        return $this->render('materiel/index.html.twig');
    }

    #[Route('/data', name: 'materiel_data', methods: ['GET'])]
    public function data(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $start = $request->query->getInt('start', 0);
        $length = $request->query->getInt('length', 10);
    
        // Query to get the list of all materials with pagination
        $queryBuilder = $em->getRepository(Materiel::class)->createQueryBuilder('m');
    
        // Get total records count
        $totalRecords = $queryBuilder->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();
    
        // Fetch paginated results
        $materiels = $queryBuilder
            ->select('m')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->getQuery()
            ->getResult();
    
        // Prepare the data for the DataTable
        $data = [];
        foreach ($materiels as $materiel) {
            $tva = $materiel->getTva();
            $data[] = [
                'id' => $materiel->getId(),
                'nom' => $materiel->getNom(),
                'prixHT' => $materiel->getPrixHT(),
                'prixTTC' => $materiel->getPrixTTC(),
                'tvaLibelle' => $tva ? $tva->getLibelle() : 'N/A',  // Display TVA libelle
                'tvaValeur' => $tva ? $tva->getValeur() : 'N/A',    // Display TVA valeur
                'quantite' => $materiel->getQuantite(),
                'creationDate' => $materiel->getCreationDate()->format('Y-m-d H:i:s'),  // Format date
            ];
        }
    
        // Return the JSON response for DataTables
        return new JsonResponse([
            'draw' => $request->query->getInt('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    #[Route('/decrement/{id}', name: 'decrement_materiel', methods: ['POST'])]
    public function decrementMateriel(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer le produit via le repository
        $materiel = $entityManager->getRepository(Materiel::class)->find($id);

        if (!$materiel) {
            return new JsonResponse(['status' => 'Produit non trouvé'], 404);
        }

        // Décrémenter le stock
        $currentQuantity = $materiel->getQuantite();
        if ($currentQuantity > 0) {
            $materiel->setQuantite($currentQuantity - 1);

            // Si le stock est à 0, supprimer le produit
            if ($materiel->getQuantite() == 0) {
                $entityManager->remove($materiel);
            }

            // Sauvegarder les changements
            $entityManager->flush();
        }

        return new JsonResponse(['status' => 'Quantité décrémentée avec succès']);
    }
    
}