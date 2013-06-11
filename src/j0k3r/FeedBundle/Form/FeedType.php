<?php

namespace j0k3r\FeedBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FeedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description', 'textarea', array('required' => false))
            ->add('link', 'url')
            ->add('parser', 'choice', array(
                'choices' => array(
                    'default'  => 'Default',
                    'internal' => 'Internal',
                    'external' => 'External'
                ),
            ))
            ->add('formatter', 'choice', array(
                'choices' => array(
                    'rss'  => 'RSS',
                    'atom' => 'Atom'
                ),
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'j0k3r\FeedBundle\Document\Feed'
        ));
    }

    public function getName()
    {
        return 'j0k3r_feedbundle_feedtype';
    }
}
