<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\TeamAffiliation;
use App\Service\ConfigurationService;
use App\Service\DOMJudgeService;
use App\Service\EventLogService;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class TeamAffiliationType extends AbstractExternalIdEntityType
{
    public function __construct(
        EventLogService $eventLogService,
        protected readonly ConfigurationService $configuration,
        protected readonly DOMJudgeService $dj
    ) {
        parent::__construct($eventLogService);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $countries = [];
        foreach (Countries::getAlpha3Codes() as $alpha3) {
            $name = Countries::getAlpha3Name($alpha3);
            $countries["$name ($alpha3)"] = $alpha3;
        }

        $this->addExternalIdField($builder, TeamAffiliation::class);
        $builder->add('icpcid', TextType::class, [
            'label'       => 'ICPC ID',
            'required'    => false,
            'help'        => '조직의 ICPC CMS ID(선택 사항).',
            'constraints' => [
                new Regex(
                    [
                        'pattern' => '/^[a-zA-Z0-9_-]+$/i',
                        'message' => '영문, 숫자, 대시(-), 밑줄(_)만 허용됩니다.',
                    ]
                )
            ]
        ]);
        $builder->add('shortname', null, ['empty_data' => '']);
        $builder->add('name', null, ['empty_data' => '']);
        if ($this->configuration->get('show_flags')) {
            $builder->add('country', ChoiceType::class, [
                'required' => false,
                'choices'  => $countries,
                'placeholder' => '국가 없음',
            ]);
        }
        $builder->add('internalcomments', TextareaType::class, [
            'label' => '내부 코멘트 (심사위원 전용)',
            'required' => false,
            'attr' => [
                'rows' => 6,
            ],
        ]);
        $builder->add('logoFile', FileType::class, [
            'label' => '로고',
            'required' => false,
        ]);
        $builder->add('clearLogo', CheckboxType::class, [
            'label' => '로고 삭제',
            'required' => false,
        ]);
        $builder->add('save', SubmitType::class, ['label' => '저장']);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var TeamAffiliation|null $affiliation */
            $affiliation = $event->getData();
            $form = $event->getForm();

            $id = $affiliation?->getApiId($this->eventLogService);

            if (!$affiliation || !$this->dj->assetPath($id, 'affiliation')) {
                $form->remove('clearLogo');
            }
        });
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TeamAffiliation::class]);
    }
}
