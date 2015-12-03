<?php

namespace Api43\FeedBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ItemTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('link', UrlType::class)
            ->add('siteconfig', TextareaType::class, array('required' => false))
            ->add('parser', ChoiceType::class, array(
                'choices' => array_flip(array(
                    'internal' => 'Internal',
                    'external' => 'External',
                )),
                'choices_as_values' => true,
            ))
        ;
    }
}
