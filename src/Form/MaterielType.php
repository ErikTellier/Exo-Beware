<?php

namespace App\Form;

use App\Entity\Materiel;
use App\Entity\Tva;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class MaterielType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('prixHT')
            ->add('prixTTC')
            ->add('quantite', null, [
                'attr' => ['min' => 0],
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0, // Pour s'assurer que la quantité est positive ou égale à zéro
                        'message' => 'La quantité doit être un nombre positif ou égal à zéro.',
                    ]),
                ],
            ])
            ->add('tva', EntityType::class, [
                'class' => Tva::class,
                'choice_label' => function (Tva $tva) {
                    // Affiche le libellé et la valeur dans la liste déroulante
                    return sprintf('%s (%.2f%%)', $tva->getLibelle(), $tva->getValeur());
                },
                'choice_attr' => function (Tva $tva) {
                    // Ajoute l'attribut data-tva contenant la valeur de la TVA
                    return ['data-tva' => $tva->getValeur()];
                },
                'placeholder' => 'Sélectionnez une TVA',
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Materiel::class,
        ]);
    }
}
