<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Tva;
use App\Form\TvaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

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

            // Récupérer l'URL d'origine envoyée dans le formulaire
            $redirectUrl = $request->request->get('redirect_url');

            // Rediriger vers l'URL d'origine après la création de la TVA
            return new RedirectResponse($redirectUrl);
        }

        // Si le formulaire est invalide, renvoyer vers la vue avec le formulaire
        return $this->render('materiel/new.html.twig', [
            'form' => $form->createView(),
            'newTvaForm' => $form->createView(), // Passe aussi le formulaire de TVA à la vue
        ]);
    }
}
