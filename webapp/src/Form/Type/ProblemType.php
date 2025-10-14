<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Executable;
use App\Entity\Problem;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProblemType extends AbstractExternalIdEntityType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addExternalIdField($builder, Problem::class);
        $builder->add('name', TextType::class, [
            'label' => '문제 이름',
            'empty_data' => ''
        ]);
        $builder->add('timelimit', NumberType::class, [
            'label' => '시간 제한',
            'input_group_after' => 'sec',
        ]);
        $builder->add('memlimit', IntegerType::class, [
            'required' => false,
            'label' => '메모리 제한',
            'help' => '비워두면 서버 기본값 사용',
            'input_group_after' => 'kB',
        ]);
        $builder->add('outputlimit', IntegerType::class, [
            'required' => false,
            'label' => '출력 제한',
            'help' => '비워두면 서버 기본값 사용',
            'input_group_after' => 'kB',
        ]);
        $builder->add('problemstatementFile', FileType::class, [
            'label' => '문제 설명 파일',
            'required' => false,
            'attr' => [
                'accept' => 'text/html,text/plain,application/pdf',
            ],
        ]);
        $builder->add('clearProblemstatement', CheckboxType::class, [
            'label' => '문제 설명 파일 삭제',
            'required' => false,
        ]);
        $builder->add('runExecutable', EntityType::class, [
            'label' => '실행 스크립트',
            'class' => Executable::class,
            'required' => false,
            'placeholder' => '-- 기본 실행 스크립트 --',
            'choice_label' => 'description',
            'query_builder' => fn(EntityRepository $er) => $er
                ->createQueryBuilder('e')
                ->where('e.type = :run')
                ->setParameter('run', 'run')
                ->orderBy('e.execid'),
        ]);
        $builder->add('compareExecutable', EntityType::class, [
            'label' => '비교 스크립트',
            'class' => Executable::class,
            'required' => false,
            'placeholder' => '-- 기본 비교 스크립트 --',
            'choice_label' => 'description',
            'query_builder' => fn(EntityRepository $er) => $er
                ->createQueryBuilder('e')
                ->where('e.type = :compare')
                ->setParameter('compare', 'compare')
                ->orderBy('e.execid'),
        ]);
        $builder->add('specialCompareArgs', TextType::class, [
            'label' => '비교 스크립트 인자',
            'required' => false,
        ]);
        $builder->add('combinedRunCompare', CheckboxType::class, [
            'label' => '실행 스크립트를 비교 스크립트로 사용 (Use run script as compare script.)',
            'required' => false,
        ]);
        $builder->add('save', SubmitType::class, ['label' => '저장']);

        // Remove clearProblemstatement field when we do not have a problem text.
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Problem|null $problem */
            $problem = $event->getData();
            $form    = $event->getForm();

            if ($problem && !$problem->getProblemstatement()) {
                $form->remove('clearProblemstatement');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Problem::class]);
    }
}
