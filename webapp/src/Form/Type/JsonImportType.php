<?php declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class JsonImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('type', ChoiceType::class, [
            'label' => '가져올 데이터 종류',
            'help' => '가져올 데이터 유형을 선택하세요.',
            'choices' => [
                '그룹 (groups)' => 'groups',
                '기관 (organizations)' => 'organizations',
                '팀 (teams)' => 'teams',
                '계정 (accounts)' => 'accounts',
            ],
        ]);
        $builder->add('file', FileType::class, [
            'required' => true,
        ]);
        $builder->add('import', SubmitType::class, ['icon' => 'fa-upload', 'label' => '가져오기']);
    }
}
