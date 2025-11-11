<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Contest;
use App\Entity\ContestProblem;
use App\Entity\Team;
use App\Entity\TeamCategory;
use App\Service\DOMJudgeService;
use App\Service\EventLogService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContestType extends AbstractExternalIdEntityType
{
    public function __construct(EventLogService $eventLogService, protected readonly DOMJudgeService $dj)
    {
        parent::__construct($eventLogService);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addExternalIdField($builder, Contest::class);
        $builder->add('shortname', TextType::class, [
            'label' => '짧은 이름 (약칭)',
            'help' => '화면 오른쪽 상단에 표시될 대회 이름입니다.',
            'empty_data' => ''
        ]);
        $builder->add('name', TextType::class, [
            'label' => '전체 이름',
            'help' => '점수판에 전체 이름으로 표시될 대회명입니다.',
            'empty_data' => ''
        ]);
        $builder->add('activatetimeString', TextType::class, [
            'label' => '활성화 시간',
            'help' => '팀에게 대회가 표시되기 시작하는 시간입니다. 심사 제출을 허용하려면 이 시간이 과거여야 합니다.',
        ]);
        $builder->add('starttimeString', TextType::class, [
            'label' => '시작 시간',
            'help' => '대회가 실제로 시작되는 절대 시간입니다.',
        ]);
        $builder->add('starttimeEnabled', ChoiceType::class, [
            'label' => '시작 카운트다운 활성화',
            'expanded' => true,
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
            'help' => '비활성화하면 대회 시작이 지연되고 카운트다운이 중지됩니다. (새 시작 시간을 설정한 후 다시 활성화하세요.)',
        ]);
        $builder->add('freezetimeString', TextType::class, [
            'label' => '점수판 동결 시간',
            'required' => false,
            'help' => '동결이 시작되는 시간 이후에 제출 결과는 점수판에 표시되지 않으며, 동결 해제 시간이 지난 후 공개됩니다.',
        ]);
        $builder->add('endtimeString', TextType::class, [
            'label' => '종료 시간',
            'help' => '이 시간 이후의 제출은 채점되지만 점수에는 반영되지 않으며 (팀과 대중에게) "너무 늦음(too-late)"으로 표시됩니다.',
        ]);
        $builder->add('unfreezetimeString', TextType::class, [
            'label' => '점수판 동결 해제 시간',
            'required' => false,
            'help' => '최종 점수판이 공개되는 시간입니다. 보통 시상식 후 몇 시간 뒤로 설정합니다.',
        ]);
        $builder->add('deactivatetimeString', TextType::class, [
            'label' => '비활성화 시간',
            'required' => false,
            'help' => '대회와 점수판이 다시 숨겨지는 시간입니다. 보통 대회 종료 후 몇 시간 또는 며칠 뒤로 설정합니다.',
        ]);
        $builder->add('allowSubmit', ChoiceType::class, [
            'expanded' => true,
            'label' => '제출 허용',
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
            'help' => '비활성화하면 참가자는 제출할 수 없으며 경고 메시지가 표시됩니다.',
        ]);
        $builder->add('processBalloons', ChoiceType::class, [
            'expanded' => true,
            'label' => 'Ballons 기록',
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
            'help' => 'Ballons 기록을 중단하려면 비활성화하세요. 일반적으로 활성 상태로 두면 됩니다.',
        ]);
        $builder->add('runtimeAsScoreTiebreaker', ChoiceType::class, [
            'expanded' => true,
            'label' => '실행 시간을 동점자 구분 기준으로 사용',
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
            'help' => '활성화하면 점수판에 실행 시간을 초 단위로 표시하고, 패널티 대신 동점자 구분에 사용됩니다. 제출물의 실행 시간은 모든 테스트 케이스 중 최대값입니다.',
        ]);
        $builder->add('medalsEnabled', ChoiceType::class, [
            'expanded' => true,
            'label' => '메달 사용',
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
            'help' => '이 대회에서 금, 은, 동 메달 기능을 사용할지 여부입니다.',
        ]);
        $builder->add('medalCategories', EntityType::class, [
            'required' => false,
            'class' => TeamCategory::class,
            'multiple' => true,
            'choice_label' => fn(TeamCategory $category) => $category->getName(),
            'label' => '메달 카테고리',
            'help' => '이 대회에서 메달을 수여할 팀 카테고리 목록입니다.',
        ]);
        foreach (['gold', 'silver', 'bronze'] as $medalType) {
            $help = "이 대회에서 수여할 $medalType 메달의 개수입니다.";
            if ($medalType === 'bronze') {
                $help .= ' 대회가 마무리될 때 "추가 동메달" 설정이 여기에 더해진다는 점에 유의하세요.';
            }
            $builder->add($medalType . 'Medals', IntegerType::class, [
                'required' => false,
                'help'     => $help,
            ]);
        }
        $builder->add('public', ChoiceType::class, [
            'expanded' => true,
            'label' => '공개 점수판 사용',
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
            'help' => '활성화하면 로그인하지 않은 사용자도 점수판을 볼 수 있습니다. 비활성화하면 로그인한 사용자/팀만 볼 수 있습니다.',
        ]);
        $builder->add('openToAllTeams', ChoiceType::class, [
            'expanded' => true,
            'label' => '모든 팀에게 대회 공개',
            'choices' => [
                'Yes' => true,
                'No' => false,
            ],
            'help' => '활성화하면 모든 로그인한 팀이 자동으로 참가합니다. 비활성화하면 아래에 지정한 팀/카테고리만 참가합니다.',
        ]);
        $builder->add('teams', EntityType::class, [
            'required' => false,
            'class' => Team::class,
            'multiple' => true,
            'choice_label' => fn(Team $team) => sprintf('%s (t%d)', $team->getEffectiveName(), $team->getTeamid()),
            'label' => '팀 목록',
            'help' => '대회를 모든 팀에 공개하지 않을 때 이 대회에 참가할 팀 목록입니다.',
        ]);
        $builder->add('teamCategories', EntityType::class, [
            'required' => false,
            'class' => TeamCategory::class,
            'multiple' => true,
            'choice_label' => fn(TeamCategory $category) => $category->getName(),
            'label' => '팀 카테고리',
            'help' => '대회를 모든 팀에 공개하지 않을 때 이 대회에 참가할 팀 카테고리 목록입니다.',
        ]);
        $builder->add('enabled', ChoiceType::class, [
            'expanded' => true,
            'label' => '대회 활성화',
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
            'help' => '비활성화 시 대회가 진행 중이더라도 팀에게 대회가 표시되지 않으며 채점도 중단됩니다. 이 설정은 다른 설정을 변경하지 않고도 빠르게 접근을 차단할 때 유용합니다.',
        ]);
        $builder->add('bannerFile', FileType::class, [
            'label' => '배너 이미지',
            'required' => false,
        ]);
        $builder->add('clearBanner', CheckboxType::class, [
            'label' => '배너 삭제',
            'required' => false,
        ]);
        $builder->add('contestProblemsetFile', FileType::class, [
            'label' => 'Problemset 문서',
            'required' => false,
            'attr' => [
                'accept' => 'text/html,text/plain,application/pdf',
            ],
        ]);
        $builder->add('clearContestProblemset', CheckboxType::class, [
            'label' => '대회 Problemset 문서 삭제',
            'required' => false,
        ]);
        $builder->add('warningMessage', TextType::class, [
            'required' => false,
            'label' => '점수판 경고 메시지',
            'help' => '설정하면 이 대회의 모든 점수판 상단에 경고 메시지가 표시됩니다.',
        ]);
        $builder->add('problems', CollectionType::class, [
            'entry_type' => ContestProblemType::class,
            'prototype' => true,
            'prototype_data' => new ContestProblem(),
            'entry_options' => ['label' => false],
            'allow_add' => true,
            'allow_delete' => true,
            'label' => false,
        ]);

        $builder->add('save', SubmitType::class, ['label' => '저장']);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Contest|null $contest */
            $contest = $event->getData();
            $form = $event->getForm();

            $id = $contest?->getApiId($this->eventLogService);

            if (!$contest || !$this->dj->assetPath($id, 'contest')) {
                $form->remove('clearBanner');
            }

            if ($contest && !$contest->getContestProblemset()) {
                $form->remove('clearContestProblemset');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Contest::class]);
    }
}
