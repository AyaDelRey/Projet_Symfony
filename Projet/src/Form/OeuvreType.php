<?php

// src/Form/OeuvreType.php

namespace App\Form;

use App\Entity\Oeuvre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class OeuvreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('artiste')
            ->add('date')
            ->add('type')
            ->add('technique')
            ->add('lieuCreation')
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
