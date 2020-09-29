<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Url;

class ItemTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('link', UrlType::class, [
                'default_protocol' => null,
                'constraints' => new Url(),
            ])
            ->add('siteconfig', TextareaType::class, [
                'required' => false,
            ])
            ->add('parser', ChoiceType::class, [
                'choices' => array_flip([
                    'internal' => 'Internal',
                    'external' => 'External',
                ]),
            ])
        ;
    }
}
