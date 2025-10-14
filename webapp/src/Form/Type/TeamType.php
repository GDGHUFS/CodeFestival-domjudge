<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Contest;
use App\Entity\Team;
use App\Entity\TeamAffiliation;
use App\Entity\TeamCategory;
use App\Entity\User;
use App\Service\DOMJudgeService;
use App\Service\EventLogService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class TeamType extends AbstractExternalIdEntityType
{
    public function __construct(
        EventLogService $eventLogService,
        protected readonly EntityManagerInterface $em,
        protected readonly DOMJudgeService $dj
    ) {
        parent::__construct($eventLogService);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addExternalIdField($builder, Team::class);
        $builder->add('name', TextType::class, [
            'label' => '팀 이름',
            'empty_data' => ''
        ]);
        $builder->add('icpcid', TextType::class, [
            'label'       => 'ICPC ID',
            'required'    => false,
            'help'        => '팀의 ICPC CMS ID(선택 사항).',
            'constraints' => [
                new Regex(
                    [
                        'pattern' => '/^[a-zA-Z0-9_-]+$/i',
                        'message' => '영문, 숫자, 대시(-), 밑줄(_)만 허용됩니다.',
                    ]
                )
            ]
        ]);
        $builder->add('label', TextType::class, [
            'label'       => '라벨',
            'required'    => false,
            'help'        => '선택 사항, 예: 좌석 번호.',
        ]);
        $builder->add('displayName', TextType::class, [
            'label'    => '표시 이름',
            'required' => false,
            'help'     => '제공하면 점수판과 같은 특정 위치에서 팀 이름 대신 표시됩니다.',
        ]);
        $builder->add('category', EntityType::class, [
            'label' => '팀 카테고리',
            'class' => TeamCategory::class,
        ]);
        $builder->add('publicdescription', TextareaType::class, [
            'label' => '공개 설명',
            'required' => false,
        ]);
        $builder->add('affiliation', EntityType::class, [
            'class'         => TeamAffiliation::class,
            'required'      => false,
            'choice_label'  => 'name',
            'label'         => '소속',
            'placeholder'   => '-- 소속 없음 --',
            'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('a')->orderBy('a.name'),
        ]);
        $builder->add('penalty', IntegerType::class, [
            'label' => '페널티 시간',
        ]);
        $builder->add('location', TextType::class, [
            'label'    => '위치',
            'required' => false,
        ]);
        $builder->add('internalcomments', TextareaType::class, [
            'label' => '내부 코멘트 (심사위원 전용)',
            'required' => false,
            'attr'     => [
                'rows' => 10,
            ]
        ]);
        $builder->add('contests', EntityType::class, [
            'class'         => Contest::class,
            'required'      => false,
            'choice_label'  => 'name',
            'multiple'      => true,
            'by_reference'  => false,
            'query_builder' => fn(EntityRepository $er) => $er
                ->createQueryBuilder('c')
                ->where('c.openToAllTeams = false')
                ->orderBy('c.name'),
        ]);
        $builder->add('enabled', ChoiceType::class, [
            'expanded' => true,
            'label' => '활성화 여부',
            'choices'  => [
                '예' => true,
                '아니오'  => false,
            ],
        ]);
        $builder->add('photoFile', FileType::class, [
            'label'    => '사진',
            'required' => false,
        ]);
        $builder->add('clearPhoto', CheckboxType::class, [
            'label'    => '사진 삭제',
            'required' => false,
        ]);
        $builder->add('addUserForTeam', ChoiceType::class, [
            'label'   => '팀에 사용자 추가',
            'choices' => [
                "사용자 추가 안함"    => Team::DONT_ADD_USER,
                '새 사용자 생성'   => Team::CREATE_NEW_USER,
                '기존 사용자 추가' => Team::ADD_EXISTING_USER,
            ],
        ]);
        $builder->add('existingUser', EntityType::class, [
            'class'        => User::class,
            'label'        => "사용자",
            'required'     => true,
            'choice_label' => 'name',
        ]);
        $builder->add('newUsername', TextType::class, [
            'label'    => '사용자 이름',
            'required' => true,
            'empty_data' => ''
        ]);

        $builder->add('save', SubmitType::class, ['label' => '저장']);

        // Remove ID field when doing an edit.
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Team|null $team */
            $team = $event->getData();
            $form = $event->getForm();

            if ($team && $team->getTeamid() !== null && $team->getUsers()->count()>0) {
                $form->remove('addUserForTeam');
                $form->remove('existingUser');
                $form->remove('newUsername');
            }

            if (!$team || !$this->dj->assetPath((string)$team->getTeamid(), 'team')) {
                $form->remove('clearPhoto');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Team::class]);
    }
}
