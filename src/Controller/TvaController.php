<?php

namespace App\Controller;

use App\Entity\Tva;
use App\Form\TvaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TvaController extends AbstractController
{
    #[Route('/tva/new', name: 'tva_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tva = new Tva();
        $form = $this->createForm(TvaType::class, $tva);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tva);
            $entityManager->flush();

            // Retourner une réponse JSON pour recharger la liste TVA
            return new Response(json_encode([
                'success' => true,
                'id' => $tva->getId(),
                'libelle' => $tva->getLibelle(),
                'valeur' => $tva->getValeur(),
            ]), 200, ['Content-Type' => 'application/json']);
        }

        return new Response(json_encode(['success' => false]), 400);
    }
}
