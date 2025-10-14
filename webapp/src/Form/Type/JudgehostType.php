<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Contest;
use App\Entity\Judgehost;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JudgehostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('hostname', TextType::class, [
            'label' => 'Hostname',
            //'attr' => ['readonly' => true],
        ]);
        $builder->add('enabled', ChoiceType::class, [
            'label' => '활성화 여부',
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
        ]);
        $builder->add('hidden', ChoiceType::class, [
            'label' => '숨김 여부',
            'choices' => [
                '예' => true,
                '아니오' => false,
            ],
        ]);
        $builder->add('contest', EntityType::class, [
            'class' => Contest::class,
            'choice_label' => fn(Contest $contest) => sprintf(
                'c%d: %s - %s', $contest->getCid(), $contest->getShortname(), $contest->getName()
            ),
            'label' => '연결할 대회',
            'placeholder' => '선택 안 함',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Judgehost::class]);
    }
}
