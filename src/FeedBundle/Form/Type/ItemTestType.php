<?php

namespace Api43\FeedBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ItemTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('link', 'Symfony\Component\Form\Extension\Core\Type\UrlType')
            ->add('siteconfig', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', array('required' => false))
            ->add('parser', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => array_flip(array(
                    'internal' => 'Internal',
                    'external' => 'External',
                )),
                'choices_as_values' => true,
            ))
        ;
    }
}
