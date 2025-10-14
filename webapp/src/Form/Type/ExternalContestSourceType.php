<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\ExternalContestSource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExternalContestSourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('type', ChoiceType::class, [
            'label' => '소스 유형',
            'help' => '외부 대회 데이터를 불러올 방식입니다.',
            'choices' => [
                ExternalContestSource::readableType(ExternalContestSource::TYPE_CCS_API)         => ExternalContestSource::TYPE_CCS_API,
                ExternalContestSource::readableType(ExternalContestSource::TYPE_CONTEST_PACKAGE) => ExternalContestSource::TYPE_CONTEST_PACKAGE,
            ],
        ]);
        $builder->add('source', TextType::class, [
            'label' => '소스 경로 또는 URL',
            'help' => 'Contest package: 디스크의 디렉토리 경로를 입력하세요. CCS API: API의 대회 URL을 입력하세요.',
        ]);
        $builder->add('username', TextType::class, [
            'label' => '사용자 이름',
            'required' => false,
        ]);
        $builder->add('password', TextType::class, [
            'label' => '비밀번호',
            'required' => false,
        ]);
        $builder->add('save', SubmitType::class, ['label' => '저장']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ExternalContestSource::class]);
    }
}
