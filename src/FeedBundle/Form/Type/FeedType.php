<?php

namespace Api43\FeedBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, ['required' => false])
            ->add('host', TextType::class, ['attr' => ['placeholder' => 'www.website.com']])
            ->add('link', UrlType::class, ['attr' => ['placeholder' => 'http://www.website.com/rss']])
            ->add('logo', UrlType::class, ['required' => false])
            ->add('color', TextType::class, ['required' => false])
            ->add('parser', ChoiceType::class, [
                'choices' => [
                    'Internal' => 'internal',
                    'External' => 'external',
                ],
                'choices_as_values' => true,
            ])
            ->add('formatter', ChoiceType::class, [
                'choices' => [
                    'RSS'  => 'rss',
                    'Atom' => 'atom',
                ],
                'choices_as_values' => true,
            ])
            ->add('sort_by', ChoiceType::class, [
                'choices' => [
                    'Published (when item arrive in the original feed)' => 'published_at',
                    'Created (when feed item are fetched)'              => 'created_at',
                ],
                'choices_as_values' => true,
            ])
            ->add('is_private', CheckboxType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Api43\FeedBundle\Document\Feed',
        ]);
    }
}
