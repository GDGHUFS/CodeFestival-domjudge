<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Contest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FinalizeContestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('b', IntegerType::class, [
            'label' => '추가 동메달 수',
        ]);
        $builder->add('finalizecomment', TextareaType::class, [
            'label' => '비고 (코멘트)',
            'required' => false,
        ]);
        $builder->add('finalize', SubmitType::class, ['label' => '마무리']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Contest::class]);
    }
}
