<?php declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class TsvImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('type', ChoiceType::class, [
            'label' => '유형',
            'choices' => [
                '그룹' => 'groups',
                '팀' => 'teams',
                '계정' => 'accounts',
            ],
        ]);
        $builder->add('file', FileType::class, [
            'label' => 'TSV 파일',
            'required' => true,
        ]);
        $builder->add('import', SubmitType::class, ['icon' => 'fa-upload', 'label' => '가져오기']);
    }
}
