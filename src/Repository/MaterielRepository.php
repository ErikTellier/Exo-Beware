<?php

namespace App\Repository;

use App\Entity\Materiel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * @extends ServiceEntityRepository<Materiel>
 */
class MaterielRepository extends ServiceEntityRepository
{
    private $mailer;
    private $adminEmail;

    public function __construct(ManagerRegistry $registry, MailerInterface $mailer, string $adminEmail)
    {
        parent::__construct($registry, Materiel::class);
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
    }

    public function decrement(Materiel $materiel): bool
    {
        $currentQuantity = $materiel->getQuantite();
        if ($currentQuantity > 0) {
            $materiel->setQuantite($currentQuantity - 1);

            // Si la quantité est à 0, supprimer le produit
            if ($materiel->getQuantite() == 0) {
                $this->getEntityManager()->remove($materiel);

                $this->sendDepletionEmail($materiel);
            }

            // Sauvegarder les changements
            $this->getEntityManager()->flush();

            return true;  // Indique que l'opération a été réussie
        }

        return false;  // Indique qu'aucune décrémentation n'a été effectuée
    }

    public function increment(Materiel $materiel): void
    {
        $materiel->setQuantite($materiel->getQuantite() + 1);
        $this->getEntityManager()->flush();
    }

    private function sendDepletionEmail(Materiel $materiel): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($this->adminEmail)
            ->subject('Produit épuisé')
            ->text("Le matériel '{$materiel->getNom()}' est maintenant épuisé.");

        $this->mailer->send($email);
    }
}
