<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Contest;
use App\Entity\Judgehost;
use App\Entity\Language;
use App\Entity\Problem;
use App\Entity\Team;
use App\Entity\User;
use App\Service\DOMJudgeService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Regex;

class RejudgingType extends AbstractType
{
    public function __construct(protected readonly DOMJudgeService $dj, protected readonly EntityManagerInterface $em)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('reason', TextType::class, ['label' => '사유']);
        $builder->add('priority', ChoiceType::class,
            [
                'label' => '우선 순위',
                'choices' => [
                    '낮음' => 'low',
                    '기본' => 'default',
                    '높음' => 'high',
                ],
                'data' => 'default',
            ]
        );
        $builder->add('repeat', IntegerType::class, [
            'label' => '재채점 반복 횟수',
            'data' => 1,
            'attr' => ['min' => 1, 'max' => 99]
        ]);
        $builder->add('contests', EntityType::class, [
            'label' => '대회 목록',
            'class' => Contest::class,
            'required' => false,
            'multiple' => true,
            'choice_label' => 'name',
            'query_builder' => fn(EntityRepository $er) => $er
                ->createQueryBuilder('c')
                ->where('c.enabled = 1')
                ->orderBy('c.cid'),
        ]);
        $builder->add('problems', EntityType::class, [
            'multiple' => true,
            'label' => '문제',
            'class' => Problem::class,
            'required' => false,
            'choice_label' => 'name',
            'choices' => [],
        ]);
        $builder->add('languages', EntityType::class, [
            'multiple' => true,
            'label' => '언어',
            'class' => Language::class,
            'required' => false,
            'choice_label' => 'name',
            'query_builder' => fn(EntityRepository $er) => $er
                ->createQueryBuilder('l')
                ->where('l.allowSubmit = 1')
                ->orderBy('l.name'),
        ]);
        $builder->add('teams', EntityType::class, [
            'multiple' => true,
            'label' => '팀 목록',
            'class' => Team::class,
            'required' => false,
            'choice_label' => 'name',
            'choices' => [],
        ]);
        $builder->add('users', EntityType::class, [
            'label' => '사용자 목록',
            'class' => User::class,
            'required' => false,
            'multiple' => true,
            'choice_label' => 'name',
            'query_builder' => fn(EntityRepository $er) => $er
                ->createQueryBuilder('u')
                ->where('u.enabled = 1')
                ->orderBy('u.name'),
        ]);
        $builder->add('judgehosts', EntityType::class, [
            'multiple' => true,
            'label' => 'Judgehost',
            'class' => Judgehost::class,
            'required' => false,
            'choice_label' => 'hostname',
            'query_builder' => fn(EntityRepository $er) => $er
                ->createQueryBuilder('j')
                ->orderBy('j.hostname'),
        ]);

        $verdicts = array_keys($this->dj->getVerdicts());
        $builder->add('verdicts', ChoiceType::class, [
            'label' => '판정',
            'multiple' => true,
            'required' => false,
            'choices' => array_combine($verdicts, $verdicts),
        ]);
        $relativeTimeConstraints = [
            new Regex([
                'pattern' => '/^[+-][0-9]+:[0-9]{2}(:[0-9]{2}(\.[0-9]{0,6})?)?$/',
                'message' => '잘못된 상대 시간 형식입니다.'
            ])
        ];
        $builder->add('after', TextType::class, [
            'label' => '이후',
            'required' => false,
            'constraints' => $relativeTimeConstraints,
            'help' => '형식 ±[HHH]H:MM[:SS[.uuuuuu]], 대회의 상대 시간',
        ]);
        $builder->add('before', TextType::class, [
            'label' => '이전',
            'required' => false,
            'constraints' => $relativeTimeConstraints,
            'help' => '형식 ±[HHH]H:MM[:SS[.uuuuuu]], 대회의 상대 시간',
        ]);

        $builder->add('save', SubmitType::class, ['label' => '저장']);

        $formProblemModifier = function (FormInterface $form, $contests = []) {
            /** @var Contest[] $contests */
            $problems = $this->em->createQueryBuilder()
                ->from(Problem::class, 'p')
                ->join('p.contest_problems', 'cp')
                ->select('p')
                ->andWhere('cp.contest IN (:contests)')
                ->setParameter('contests', $contests)
                ->addOrderBy('p.name')
                ->getQuery()
                ->getResult();

            $form->add('problems', EntityType::class, [
                'multiple' => true,
                'label' => '문제',
                'class' => Problem::class,
                'required' => false,
                'choice_label' => 'name',
                'choices' => $problems,
            ]);

            $teamsQueryBuilder = $this->em->createQueryBuilder()
                ->from(Team::class, 't')
                ->select('t')
                ->andWhere('t.enabled = 1')
                ->addOrderBy('t.name');

            $selectAllTeams = false;
            foreach ($contests as $contest) {
                if ($contest->isOpenToAllTeams()) {
                    $selectAllTeams = true;
                    break;
                }
            }

            if (!$selectAllTeams) {
                $teamsQueryBuilder
                    ->leftJoin('t.contests', 'c')
                    ->join('t.category', 'cat')
                    ->leftJoin('cat.contests', 'cc')
                    ->andWhere('c IN (:contests) OR cc IN (:contests)')
                    ->setParameter('contests', $contests);
            }

            $teams = $teamsQueryBuilder->getQuery()->getResult();

            $form->add('teams', EntityType::class, [
                'multiple' => true,
                'label' => '팀 목록',
                'class' => Team::class,
                'required' => false,
                'choice_label' => 'name',
                'choices' => $teams,
            ]);
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formProblemModifier) {
                $data = $event->getData();
                $formProblemModifier($event->getForm(), $data['contests'] ?? []);
            }
        );

        $builder->get('contests')->addEventListener(FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formProblemModifier) {
                $contests = $event->getForm()->getData();
                $formProblemModifier($event->getForm()->getParent(), $contests);
            }
        );
    }
}
