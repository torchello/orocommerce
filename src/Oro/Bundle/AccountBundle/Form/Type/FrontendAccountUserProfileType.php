<?php

namespace Oro\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\UserBundle\Form\Type\ChangePasswordType;

class FrontendAccountUserProfileType extends AbstractType
{
    const NAME = 'oro_account_frontend_account_user_profile';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'namePrefix',
                'text',
                [
                    'required' => false,
                    'label' => 'oro.account.accountuser.name_prefix.label'
                ]
            )
            ->add(
                'firstName',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.account.accountuser.first_name.label'
                ]
            )
            ->add(
                'middleName',
                'text',
                [
                    'required' => false,
                    'label' => 'oro.account.accountuser.middle_name.label'
                ]
            )
            ->add(
                'lastName',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.account.accountuser.last_name.label'
                ]
            )
            ->add(
                'nameSuffix',
                'text',
                [
                    'required' => false,
                    'label' => 'oro.account.accountuser.name_suffix.label'
                ]
            )
            ->add(
                'birthday',
                'oro_date',
                [
                    'required' => false,
                    'label' => 'oro.account.accountuser.birthday.label'
                ]
            )
            ->add(
                'email',
                'email',
                [
                    'required' => true,
                    'label' => 'oro.account.accountuser.email.label'
                ]
            )
            ->add(
                'changePassword',
                ChangePasswordType::NAME,
                [
                    'current_password_label' => 'oro.account.accountuser.current_password.label',
                    'plain_password_invalid_message' => 'oro.account.message.password_mismatch',
                    'first_options_label' => 'oro.account.accountuser.new_password.label',
                    'second_options_label' => 'oro.account.accountuser.password_confirmation.label'
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'intention' => 'frontend_account_user',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
