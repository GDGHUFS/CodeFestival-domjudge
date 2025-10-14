<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\TeamCategory;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class TeamCategoryType extends AbstractExternalIdEntityType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addExternalIdField($builder, TeamCategory::class);
        $builder->add('icpcid', TextType::class, [
            'label'       => 'ICPC ID',
            'required'    => false,
            'help'        => '카테고리의 ICPC CMS ID(선택 사항).',
            'constraints' => [
                new Regex(
                    [
                        'pattern' => '/^[a-zA-Z0-9_-]+$/i',
                        'message' => '영문, 숫자, 대시(-), 밑줄(_)만 허용됩니다.',
                    ]
                )
            ]
        ]);
        $builder->add('name', null, ['empty_data' => '']);
        $builder->add('sortorder', IntegerType::class, ['label' => '정렬 순서']);
        $builder->add('color', TextType::class, [
            'label' => '색',
            'required' => false,
            'attr' => [
                'data-color-picker' => '',
            ],
            'help' => '<a target="_blank" href="https://en.wikipedia.org/wiki/Web_colors"><i class="fas fa-question-circle"></i></a>',
            'help_html' => true,
        ]);
        $builder->add('visible', ChoiceType::class, [
            'expanded' => true,
            'label' => '보이기',
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
        ]);
        $builder->add('allow_self_registration', ChoiceType::class, [
            'label' => '사용자 등록 허용 (Allow self-registration)',
            'expanded' => true,
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
        ]);
        $builder->add('save', SubmitType::class, ['label' => '저장']);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TeamCategory::class]);
    }
}
