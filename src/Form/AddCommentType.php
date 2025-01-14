<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AddCommentType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'label.comment.title',
            ])
            ->add('text', CkEditorType::class, [
                'label' => 'label.comment.text',
                'attr' => [
                    'class' => 'editor',
                ],
            ])
            ->add('CommunityNewsCommentCreate', SubmitType::class, [
                'label' => 'label.communitynews.comment.create',
                'attr' => [
                    'class' => 'btn-primary',
                ],
            ])
            ->setAttribute('novalidate', 'novalidate')
            ->setAttribute('test', 'otto')
        ;
    }
}
