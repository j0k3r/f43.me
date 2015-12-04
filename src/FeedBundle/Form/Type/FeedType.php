<?php

namespace Api43\FeedBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'Symfony\Component\Form\Extension\Core\Type\TextType')
            ->add('description', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', array('required' => false))
            ->add('host', 'Symfony\Component\Form\Extension\Core\Type\TextType', array('attr' => array('placeholder' => 'www.website.com')))
            ->add('link', 'Symfony\Component\Form\Extension\Core\Type\UrlType', array('attr' => array('placeholder' => 'http://www.website.com/rss')))
            ->add('logo', 'Symfony\Component\Form\Extension\Core\Type\UrlType', array('required' => false))
            ->add('color', 'Symfony\Component\Form\Extension\Core\Type\TextType', array('required' => false))
            ->add('parser', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => array(
                    'Internal' => 'internal',
                    'External' => 'external',
                ),
                'choices_as_values' => true,
            ))
            ->add('formatter', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => array(
                    'RSS' => 'rss',
                    'Atom' => 'atom',
                ),
                'choices_as_values' => true,
            ))
            ->add('sort_by', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => array(
                    'Published (when item arrive in the original feed)' => 'published_at',
                    'Created (when feed item are fetched)' => 'created_at',
                ),
                'choices_as_values' => true,
            ))
            ->add('is_private', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Api43\FeedBundle\Document\Feed',
        ));
    }
}
