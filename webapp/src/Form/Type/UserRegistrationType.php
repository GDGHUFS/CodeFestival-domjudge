<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Role;
use App\Entity\Team;
use App\Entity\TeamAffiliation;
use App\Entity\TeamCategory;
use App\Entity\User;
use App\Service\ConfigurationService;
use App\Service\DOMJudgeService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContext;

class UserRegistrationType extends AbstractType
{
    public function __construct(
        protected readonly DOMJudgeService $dj,
        protected readonly ConfigurationService $config,
        protected readonly EntityManagerInterface $em
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => '사용자 이름',
                    'autocomplete' => 'username',
                ],
            ])
            ->add('name', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => '이름 (선택)',
                    'autocomplete' => 'name',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => '이메일 주소 (선택)',
                    'autocomplete' => 'email',
                ],
                'constraints' => new Email(),
            ])
            ->add('teamName', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => '팀 이름',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Callback(function ($teamName, ExecutionContext $context) {
                        if ($this->em->getRepository(Team::class)->findOneBy(['name' => $teamName])) {
                            $context->buildViolation('이미 사용 중인 팀 이름입니다.')
                                ->addViolation();
                        }
                    }),
                ],
                'mapped' => false,
            ]);

        $selfRegistrationCategoriesCount = $this->em->getRepository(TeamCategory::class)->count(['allow_self_registration' => 1]);
        if ($selfRegistrationCategoriesCount > 1) {
            $builder
                ->add('teamCategory', EntityType::class, [
                    'class' => TeamCategory::class,
                    'label' => false,
                    'mapped' => false,
                    'choice_label' => 'name',
                    'placeholder' => '-- 카테고리 선택 --',
                    'query_builder' => fn(EntityRepository $er) => $er
                        ->createQueryBuilder('c')
                        ->where('c.allow_self_registration = 1')
                        ->orderBy('c.sortorder'),
                    'attr' => [
                        'placeholder' => '카테고리',
                    ],
                    'constraints' => [
                        new NotBlank(),
                    ],
                ]);
        }

        if ($this->config->get('show_affiliations')) {
            $countries = [];
            foreach (Countries::getAlpha3Codes() as $alpha3) {
                $name = Countries::getAlpha3Name($alpha3);
                $countries["$name ($alpha3)"] = $alpha3;
            }

            $builder
                ->add('affiliation', ChoiceType::class, [
                    'choices' => [
                        '기존 소속 사용' => 'existing',
                        '새 소속 추가' => 'new',
                        '소속 없음' => 'none',
                    ],
                    'expanded' => true,
                    'mapped' => false,
                    'label' => false,
                ])
                ->add('affiliationName', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'placeholder' => '소속 이름',
                    ],
                    'mapped' => false,
                ])
                ->add('affiliationShortName', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'placeholder' => '소속 약칭',
                        'maxlength' => '32',
                    ],
                    'mapped' => false,
                ]);
            if ($this->config->get('show_flags')) {
                $builder->add('affiliationCountry', ChoiceType::class, [
                    'label' => false,
                    'required' => false,
                    'mapped' => false,
                    'choices' => $countries,
                    'placeholder' => '국가 없음',
                ]);
            }
            $builder->add('existingAffiliation', EntityType::class, [
                    'class' => TeamAffiliation::class,
                    'label' => false,
                    'required' => false,
                    'mapped' => false,
                    'choice_label' => 'name',
                    'placeholder' => '-- 소속 선택 --',
                    'query_builder' => fn(EntityRepository $er) => $er
                        ->createQueryBuilder('a')
                        ->orderBy('a.name'),
                    'attr' => [
                        'placeholder' => '소속',
                    ],
                ]);
        }

        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => '비밀번호가 일치하지 않습니다.',
                'first_options' => [
                    'label' => false,
                    'attr' => [
                        'placeholder' => '비밀번호',
                        'autocomplete' => 'new-password',
                        'spellcheck' => 'false',
                    ],
                ],
                'second_options' => [
                    'label' => false,
                    'attr' => [
                        'placeholder' => '비밀번호 확인',
                        'autocomplete' => 'new-password',
                        'spellcheck' => 'false',
                    ],
                ],
                'mapped' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => '등록',
                'attr' => [
                    'class' => 'btn btn-lg btn-primary btn-block',
                ],
            ]);

        // Make sure the user has the team role to make validation work
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var User $user */
            $user = $event->getData();
            /** @var Role $role */
            $role = $this->em->createQueryBuilder()
                ->from(Role::class, 'r')
                ->select('r')
                ->andWhere('r.dj_role = :team')
                ->setParameter('team', 'team')
                ->getQuery()
                ->getOneOrNullResult();
            $user->addUserRole($role);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $validateAffiliation = function ($data, ExecutionContext $context) {
            if ($this->config->get('show_affiliations')) {
                /** @var Form $form */
                $form = $context->getRoot();
                switch ($form->get('affiliation')->getData()) {
                    case 'new':
                        foreach (['Name','ShortName'] as $identifier) {
                            $name = $form->get('affiliation'.$identifier)->getData();
                            if (empty($name)) {
                                $context->buildViolation('값을 입력하세요.')
                                    ->atPath('affiliation'.$identifier)
                                    ->addViolation();
                            }
                            if ($this->em->getRepository(TeamAffiliation::class)->findOneBy([strtolower($identifier) => $name])) {
                                $context->buildViolation('소속 '.strtolower($identifier).'은 이미 사용 중입니다.')
                                    ->atPath('affiliation'.$identifier)
                                    ->addViolation();
                            }
                        }
                        break;
                    case 'existing':
                        if (empty($form->get('existingAffiliation')->getData())) {
                            $context->buildViolation('값을 선택하세요.')
                                ->atPath('existingAffiliation')
                                ->addViolation();
                        }
                        break;
                }
            }
        };
        $resolver->setDefaults(
            [
                'data_class' => User::class,
                'constraints' => new Callback($validateAffiliation)
            ]
        );
    }
}
