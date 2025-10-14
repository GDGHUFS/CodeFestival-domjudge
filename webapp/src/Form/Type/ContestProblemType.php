<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\ContestProblem;
use App\Entity\Problem;
use App\Service\DOMJudgeService as DJS;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContestProblemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('problem', EntityType::class, [
            'class' => Problem::class,
            'required' => true,
            'choice_label' => fn(Problem $problem) => sprintf('p%d - %s', $problem->getProbid(), $problem->getName()),
            'query_builder' => fn(EntityRepository $er) => $er
                ->createQueryBuilder('p')
                ->orderBy('p.probid'),
        ]);

        $builder->add('shortname', TextType::class, [
            'label' => '짧은 이름 (약칭)',
            'empty_data' => '',
        ]);
        $builder->add('points', IntegerType::class, [
            'label' => '배점',
        ]);
        $builder->add('allowSubmit', ChoiceType::class, [
            'label' => '제출 허용',
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
        ]);
        $builder->add('allowJudge', ChoiceType::class, [
            'label' => '채점 허용',
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
        ]);
        $builder->add('color', TextType::class, [
            'required' => false,
            'label' => '색상',
            'attr' => [
                'data-color-picker' => '',
            ],
        ]);
        $builder->add('lazyEvalResults', ChoiceType::class, [
            'label' => '지연 평가 방식',
            'choices' => [
                '기본값' => DJS::EVAL_DEFAULT,
                '예' => DJS::EVAL_LAZY,
                '아니오' => DJS::EVAL_FULL,
                '요청 시' => DJS::EVAL_DEMAND,
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContestProblem::class]);
    }
}
