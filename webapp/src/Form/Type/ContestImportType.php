<?php declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ContestImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, [
            'required' => true,
            'label' => '파일',
            'help' => '대회를 가져오면 일부 설정(예: 페널티 시간, 질의 분류, 질의 응답 등)이 덮어쓰여질 수 있습니다. 이 작업은 되돌릴 수 없습니다.',
        ]);
        $builder->add('import', SubmitType::class, ['icon' => 'fa-upload', 'label' => '가져오기']);
    }
}
