<?php

namespace App\Controller;

use App\Entity\Materiel;
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

    #[Route('/materiel/data', name: 'materiel_data', methods: ['GET'])]
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
            ->select('m')  // Ensure we are selecting the full entity
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->getQuery()
            ->getResult();
    
        // Prepare the data for the DataTable
        $data = [];
        foreach ($materiels as $materiel) {
            $data[] = [
                'nom' => $materiel->getNom(),
            ];
        }
    
        // Return the JSON response for DataTables
        return new JsonResponse([
            'draw' => $request->query->getInt('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,  // Since there is no filtering, use total records
            'data' => $data,
        ]);
    }
    
}
