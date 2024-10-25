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
use Dompdf\Dompdf;
use Dompdf\Options;

class MaterielController extends AbstractController
{
    //Main page
    #[Route('/', name: 'app_materiel')]
    public function index(): Response
    {
        return $this->render('materiel/index.html.twig');
    }

    //Data for DataTables
    #[Route('/data', name: 'materiel_data', methods: ['GET'])]
    public function data(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $start = $request->query->getInt('start', 0);
        $length = $request->query->getInt('length', 10);

        //search capabilities
        $search = $request->get('search'); 
        $filters = [ 
            'query' => @$search['value'] 
        ]; 
    
        $queryBuilder = $em->getRepository(Materiel::class)->createQueryBuilder('m');

        if (!empty($filters['query'])) {
            $queryBuilder->andWhere('m.nom LIKE :search')
                         ->setParameter('search', '%' . $filters['query'] . '%');
        }
    
        $totalRecords = $em->getRepository(Materiel::class)->count([]);
        $filteredRecords = $queryBuilder->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();
    
        $materiels = $queryBuilder
            ->select('m')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->getQuery()
            ->getResult();
    
        $data = [];
        foreach ($materiels as $materiel) {
            $tva = $materiel->getTva();
            $data[] = [
                'id' => $materiel->getId(),
                'nom' => $materiel->getNom(),
                'prixHT' => $materiel->getPrixHT(),
                'prixTTC' => $materiel->getPrixTTC(),
                'tvaLibelle' => $tva ? $tva->getLibelle() : 'N/A',
                'tvaValeur' => $tva ? $tva->getValeur() : 'N/A',
                'quantite' => $materiel->getQuantite(),
                'creationDate' => $materiel->getCreationDate()->format('Y-m-d H:i:s'),
            ];
        }
    
        return new JsonResponse([
            'draw' => $request->query->getInt('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    //Decrement quantity with Repository method
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

    //Increment quantity with Repository method
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
    
    //New Materiel form
    #[Route('/new', name: 'materiel_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $materiel = new Materiel();

        $form = $this->createForm(MaterielType::class, $materiel);
        $newTvaForm = $this->createForm(TvaType::class, new Tva());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $materiel->setCreationDate(new \DateTime());
            $entityManager->persist($materiel);
            $entityManager->flush();

            return $this->redirectToRoute('app_materiel');
        }

        return $this->render('materiel/new.html.twig', [
            'form' => $form->createView(),
            'newTvaForm' => $newTvaForm->createView(),
            'isEdit' => false,
        ]);
    }

    //Edit Materiel form
    #[Route('/edit/{id}', name: 'materiel_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, Materiel $materiel): Response
    {
        $form = $this->createForm(MaterielType::class, $materiel);
        $newTvaForm = $this->createForm(TvaType::class, new Tva());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_materiel');
        }

        return $this->render('materiel/new.html.twig', [
            'form' => $form->createView(),
            'newTvaForm' => $newTvaForm->createView(),
            'isEdit' => true,
        ]);
    }

    //Material details for pop-up
    #[Route('/materiel/{id}', name: 'materiel_show', methods: ['GET'])]
    public function show(Materiel $materiel): JsonResponse
    {
        return new JsonResponse([
            'nom' => $materiel->getNom(),
            'prixHT' => $materiel->getPrixHT(),
            'prixTTC' => $materiel->getPrixTTC(),
            'tvaLibelle' => $materiel->getTva()->getLibelle(),
            'tvaValeur' => $materiel->getTva()->getValeur(),
            'quantite' => $materiel->getQuantite(),
            'creationDate' => $materiel->getCreationDate()->format('Y-m-d H:i:s')
        ]);
    }

    //Generate PDF for Materiel
    #[Route('/materiel/{id}/pdf', name: 'materiel_pdf', methods: ['GET'])]
    public function generatePdf(Materiel $materiel): Response
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($pdfOptions);
        
        $html = $this->renderView('materiel/pdf.html.twig', [
            'materiel' => $materiel
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="materiel_'.$materiel->getId().'.pdf"',
        ]);
    }
}
