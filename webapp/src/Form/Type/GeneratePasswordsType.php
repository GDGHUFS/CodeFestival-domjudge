<?php declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class GeneratePasswordsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [
            '모든 팀' => 'team',
            '비밀번호가 없는 팀' => 'team_nopass',
            '심사위원' => 'judge',
            '관리자' => 'admin',
        ];
        $builder->add('group', ChoiceType::class, [
            'label' => '새 비밀번호를 생성할 대상:',
            'expanded' => true,
            'multiple' => true,
            'choices' => $choices]);
        $builder->add('generate', SubmitType::class, [
            'label' => '비밀번호 생성',
            'attr'  => ['class' => 'btn-warning'],
        ]);
    }
}
