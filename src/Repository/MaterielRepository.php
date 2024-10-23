<?php

namespace App\Repository;

use App\Entity\Materiel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Materiel>
 */
class MaterielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Materiel::class);
    }

public function decrement(Materiel $materiel): bool
{
    $currentQuantity = $materiel->getQuantite();
    if ($currentQuantity > 0) {
        $materiel->setQuantite($currentQuantity - 1);

        // Si la quantité est à 0, supprimer le produit
        if ($materiel->getQuantite() == 0) {
            $this->getEntityManager()->remove($materiel);
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
}
