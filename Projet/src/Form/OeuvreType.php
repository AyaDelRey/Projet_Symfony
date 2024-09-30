<?php

// src/Form/OeuvreType.php

namespace App\Form;

use App\Entity\Oeuvre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class OeuvreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('artiste')
            ->add('date', DateType::class, [
                'widget' => 'single_text', // Utiliser un champ texte pour simplifier
                'format' => 'yyyy', // Afficher seulement l'année
                'years' => range(1900, date('Y')), // Plage d'années
                'html5' => false, // Désactiver le champ date HTML5 pour avoir un champ texte
                'attr' => [
                    'placeholder' => 'Année',
                ],
            ])
            ->add('type')
            ->add('technique')
            ->add('lieu_creation')
            ->add('dimensions')
            ->add('mouvement')
            ->add('collection')
            ->add('description')
            ->add('image', FileType::class, [
                'label' => 'Image de l\'œuvre (PNG, JPG)',

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the file
                // every time you edit the Oeuvre details
                'required' => false,

                // constraints to validate the file input
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (PNG ou JPG)',
                    ])
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Ajouter l\'œuvre',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Oeuvre::class,
        ]);
    }
}
