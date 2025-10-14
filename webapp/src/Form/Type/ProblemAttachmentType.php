<?php declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ProblemAttachmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', FileType::class, [
            'label' => '첨부 파일',
            'required' => true,
        ]);
        $builder->add('add', SubmitType::class, [
            'label' => '추가',
            'attr' => [
                'class' => 'btn-sm btn-primary',
            ],
        ]);
    }
}
