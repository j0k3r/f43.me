<?php

namespace Api43\FeedBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ItemTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('link', 'url')
            ->add('siteconfig', 'textarea')
            ->add('parser', 'choice', array(
                'choices' => array(
                    'internal' => 'Internal',
                    'external' => 'External',
                ),
            ))
        ;
    }

    public function getName()
    {
        return 'feedbundle_itemtesttype';
    }
}
