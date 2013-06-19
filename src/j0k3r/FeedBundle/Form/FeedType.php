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
            ->add('host', 'text', array('attr' => array('placeholder' => 'www.website.com')))
            ->add('link', 'url', array('attr' => array('placeholder' => 'http://www.website.com/rss')))
            ->add('parser', 'choice', array(
                'choices' => array(
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
        return 'feedbundle_feedtype';
    }
}
