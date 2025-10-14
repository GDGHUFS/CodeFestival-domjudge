<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Contest;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ProblemUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('contest', EntityType::class, [
            'class' => Contest::class,
            'required' => false,
            'label' => '대회',
            'placeholder' => '대회 데이터를 추가/업데이트하지 않음',
            'choice_label' => fn(Contest $contest) => sprintf(
                'c%d: %s - %s', $contest->getCid(), $contest->getShortname(), $contest->getName()
            ),
        ]);
        $builder->add('archive', FileType::class, [
            'required' => true,
            'label' => 'Problem archive',
            'label' => '문제 압축 파일',
            'attr' => [
                'accept' => 'application/zip',
            ],
        ]);
        $builder->add('upload', SubmitType::class, ['label' => '가져오기', 'icon' => 'fa-upload']);
    }
}
