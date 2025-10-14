<?php declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ICPCCmsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('contest_id', TextType::class, [
            'label' => '대회 ID',
            'help' => '"Web Services Token"을 생성하려면, ICPC 웹사이트의 <a
                            href="https://icpc.global/login" target="_blank">https://icpc.global/login</a> 에서
                        해당 대회의 "Export" 섹션으로 이동해 적절한 권한을 가진 토큰을 만들어주세요.
                        대회 ID(예: <code>Southwestern-Europe-2014</code>)는 대회 페이지의 URL에서 확인할 수 있습니다.',
            'help_html' => true,
        ]);
        $builder->add('access_token', TextType::class, ['label' => '접근 토큰']);
        $builder->add('fetch_teams', SubmitType::class, ['label' => '팀 불러오기', 'icon' => 'fa-upload']);
        // $builder->add('upload_standings', SubmitType::class);
    }
}
