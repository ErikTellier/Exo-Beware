<?php

namespace App\Controller;

use App\Entity\Materiel;
use App\Entity\Tva;
use App\Form\MaterielType;
use App\Form\TvaType;
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
        $materiel = $entityManager->getRepository(Materiel::class)->find($id);

        if (!$materiel) {
            return new JsonResponse(['status' => 'Produit non trouvé'], 404);
        }

        $decrementSuccess = $entityManager->getRepository(Materiel::class)->decrement($materiel);

        if ($decrementSuccess) {
            return new JsonResponse(['status' => 'Quantité décrémentée avec succès']);
        }

        return new JsonResponse(['status' => 'Quantité déjà à 0'], 400);
    }

    #[Route('/increment/{id}', name: 'increment_materiel', methods: ['POST'])]
    public function incrementMateriel(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $materiel = $entityManager->getRepository(Materiel::class)->find($id);

        if (!$materiel) {
            return new JsonResponse(['status' => 'Produit non trouvé'], 404);
        }

        $entityManager->getRepository(Materiel::class)->increment($materiel);

        return new JsonResponse(['status' => 'Quantité incrémentée avec succès']);
    }
    
    #[Route('/new', name: 'materiel_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer une nouvelle instance de Materiel
        $materiel = new Materiel();

        // Créer le formulaire pour Materiel
        $form = $this->createForm(MaterielType::class, $materiel);

        // Créer le formulaire pour TVA
        $newTvaForm = $this->createForm(TvaType::class, new Tva());

        // Traiter la requête du formulaire de Materiel
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $materiel->setCreationDate(new \DateTime());
            $entityManager->persist($materiel);
            $entityManager->flush();

            // Redirection après la soumission
            return $this->redirectToRoute('app_materiel'); // Remplace 'materiel_list' par ta route
        }

        // Rendre le formulaire de Materiel et celui de TVA
        return $this->render('materiel/new.html.twig', [
            'form' => $form->createView(),
            'newTvaForm' => $newTvaForm->createView(),  // Passer le formulaire de TVA à la vue
        ]);
    }

    #[Route('/edit/{id}', name: 'materiel_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, Materiel $materiel): Response
    {
        // Créer le formulaire pour Materiel avec l'entité existante (chargée depuis l'URL avec l'id)
        $form = $this->createForm(MaterielType::class, $materiel);

        // Créer le formulaire pour TVA
        $newTvaForm = $this->createForm(TvaType::class, new Tva());

        // Traiter la requête du formulaire de Materiel
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Pas besoin de définir la date de création, mais vous pouvez mettre à jour les autres champs si nécessaire
            $entityManager->flush(); // Mettre à jour l'entité dans la base de données

            // Redirection après la soumission
            return $this->redirectToRoute('app_materiel'); // Remplace par ta route de redirection
        }

        // Rendre le formulaire de Materiel et celui de TVA
        return $this->render('materiel/new.html.twig', [
            'form' => $form->createView(),
            'newTvaForm' => $newTvaForm->createView(), // Passer le formulaire de TVA à la vue
        ]);
    }
}
