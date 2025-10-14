<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Executable;
use App\Entity\Language;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageType extends AbstractExternalIdEntityType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addExternalIdField($builder, Language::class);
        $builder->add('langid', TextType::class, [
            'label' => '언어 ID',
        ]);
        $builder->add('name', TextType::class, [
            'label' => '언어 이름',
            'empty_data' => ''
        ]);
        $builder->add('requireEntryPoint', ChoiceType::class, [
            'label' => 'EntryPoint 필요 여부',
            'expanded' => true,
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
        ]);
        $builder->add('entryPointDescription', TextType::class, [
            'label' => 'EntryPoint 설명',
            'required' => false,
        ]);
        $builder->add('allowSubmit', ChoiceType::class, [
            'label' => '제출 허용 여부',
            'expanded' => true,
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
        ]);
        $builder->add('allowJudge', ChoiceType::class, [
            'label' => '채점 허용 여부',
            'expanded' => true,
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
        ]);
        $builder->add('timeFactor', TextType::class, [
            'label' => '시간 배율 (Time factor)',
            'input_group_after' => '&times;',
        ]);
        $builder->add('compileExecutable', EntityType::class, [
            'label' => '컴파일 스크립트',
            'class' => Executable::class,
            'required' => false,
            'placeholder' => '-- 실행 파일 없음 --',
            'choice_label' => 'execid',
            'query_builder' => fn(EntityRepository $er) => $er
                ->createQueryBuilder('e')
                ->where('e.type = :compile')
                ->setParameter('compile', 'compile')
                ->orderBy('e.execid'),
        ]);
        $builder->add('extensions', CollectionType::class, [
            'label' => '허용 확장자 목록',
            'error_bubbling' => false,
            'entry_type' => TextType::class,
            'entry_options' => ['label' => false],
            'allow_add' => true,
            'allow_delete' => true,
        ]);
        $builder->add('filterCompilerFiles', ChoiceType::class, [
            'label' => '확장자 목록으로 컴파일러 입력 파일 필터링 (Filter files passed to compiler by extension list)',
            'expanded' => true,
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
        ]);
        $builder->add('compilerVersionCommand', TextType::class, [
            'label' => '컴파일러 버전 확인 명령어',
            'required' => false,
        ]);
        $builder->add('runnerVersionCommand', TextType::class, [
            'label' => '실행기 버전 확인 명령어',
            'required' => false,
        ]);
        $builder->add('save', SubmitType::class, ['label' => '저장']);

        // Remove ID field when doing an edit.
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Language|null $language */
            $language = $event->getData();
            $form     = $event->getForm();

            if ($language && $language->getLangid() !== null) {
                $form->remove('langid');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Language::class]);
    }
}
