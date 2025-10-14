<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Controller\Jury\UserController;
use App\Entity\Role;
use App\Entity\Team;
use App\Entity\User;
use App\Service\EventLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractExternalIdEntityType
{
    public function __construct(protected readonly EntityManagerInterface $em, EventLogService $eventLogService)
    {
        parent::__construct($eventLogService);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addExternalIdField($builder, Team::class);
        /** @var Team[] $teams */
        $teams = $this->em->createQueryBuilder()
            ->from(Team::class, 't', 't.teamid')
            ->select('t')
            ->getQuery()
            ->getResult();
        uasort(
            $teams,
            static fn(Team $a, Team $b) => $a->getEffectiveName() <=> $b->getEffectiveName()
        );

        $builder->add('username', TextType::class, [
            'empty_data' => ''
        ]);
        $builder->add('name', TextType::class, [
            'label' => '이름',
            'required' => false,
            'help' => '사용자의 전체 이름 (선택)',
            'empty_data' => ''
        ]);
        $builder->add('email', EmailType::class, [
            'required' => false,
            'attr' => [
                'autocomplete' => 'user-email',
            ],
        ]);
        $builder->add('plainPassword', PasswordType::class, [
            'required' => false,
            'label' => '비밀번호',
        ]);
        $builder->add('ipAddress', TextType::class, [
            'required' => false,
            'label' => 'IP 주소',
        ]);
        $builder->add('enabled', ChoiceType::class, [
            'expanded' => true,
            'label' => '활성화 여부',
            'choices' => [
                '사용' => true,
                '비사용' => false,
            ],
        ]);
        $builder->add('team', ChoiceType::class, [
            'choice_label' => 'effective_name',
            'required' => false,
            'label' => '팀',
            'placeholder' => '-- 팀 없음 --',
            'choices' => $teams,
        ]);
        $builder->add('user_roles', EntityType::class, [
            'label' => '권한',
            'class' => Role::class,
            'choice_label' => 'description',
            'required' => false,
            'multiple' => true,
            'expanded' => true,
        ]);
        $builder->add('save', SubmitType::class, ['label' => '저장']);

        // Remove ID field when doing an edit
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var User|null $user */
            $user = $event->getData();
            $form = $event->getForm();

            if ($user && $user->getUserid() !== null) {
                $form->remove('username');
            }

            $set = $user->getPassword() ? '설정됨' : '설정되지 않음';
            $form->add('plainPassword', PasswordType::class, [
                'required' => false,
                'label' => '비밀번호',
                'help' => sprintf('현재 비밀번호: %s - 변경하려면 입력하세요. 사용자의 기존 로그인 세션은 종료됩니다.', $set),
                'attr' => [
                    'autocomplete' => 'new-password',
                    'minlength' => UserController::MIN_PASSWORD_LENGTH,
                ],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
