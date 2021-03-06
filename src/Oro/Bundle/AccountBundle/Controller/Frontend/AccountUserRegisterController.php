<?php

namespace Oro\Bundle\AccountBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Form\Handler\FrontendAccountUserHandler;

class AccountUserRegisterController extends Controller
{
    /**
     * Create account user form
     *
     * @Route("/register", name="oro_account_frontend_account_user_register")
     * @Layout()
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function registerAction(Request $request)
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('oro_account_frontend_account_user_profile'));
        }

        if (!$this->isRegistrationAllowed()) {
            return $this->redirect($this->generateUrl('oro_account_account_user_security_login'));
        }

        return $this->handleForm($request);
    }

    /**
     * @return bool
     */
    protected function isRegistrationAllowed()
    {
        return (bool) $this->get('oro_config.manager')->get('oro_account.registration_allowed');
    }

    /**
     * @param Request $request
     * @return LayoutContext|RedirectResponse
     */
    protected function handleForm(Request $request)
    {
        $form = $this->get('oro_account.provider.frontend_account_user_registration_form')
            ->getRegisterForm()
            ->getForm();
        $userManager = $this->get('oro_account_user.manager');
        $handler = new FrontendAccountUserHandler($form, $request, $userManager);

        if ($userManager->isConfirmationRequired()) {
            $registrationMessage = 'oro.account.controller.accountuser.registered_with_confirmation.message';
        } else {
            $registrationMessage = 'oro.account.controller.accountuser.registered.message';
        }
        $response = $this->get('oro_form.model.update_handler')->handleUpdate(
            $form->getData(),
            $form,
            ['route' => 'oro_account_account_user_security_login'],
            ['route' => 'oro_account_account_user_security_login'],
            $this->get('translator')->trans($registrationMessage),
            $handler
        );
        if ($response instanceof Response) {
            return $response;
        }

        return [];
    }

    /**
     * @Route("/confirm-email", name="oro_account_frontend_account_user_confirmation")
     * @param Request $request
     * @return RedirectResponse
     */
    public function confirmEmailAction(Request $request)
    {
        $userManager = $this->get('oro_account_user.manager');
        /** @var AccountUser $accountUser */
        $accountUser = $userManager->findUserByUsernameOrEmail($request->get('username'));
        $token = $request->get('token');
        if ($accountUser === null || empty($token) || $accountUser->getConfirmationToken() !== $token) {
            throw $this->createNotFoundException(
                $this->get('translator')
                    ->trans('oro.account.controller.accountuser.confirmation_error.message')
            );
        }

        $messageType = 'warn';
        $message = 'oro.account.controller.accountuser.already_confirmed.message';
        if (!$accountUser->isConfirmed()) {
            $userManager->confirmRegistration($accountUser);
            $userManager->updateUser($accountUser);
            $messageType = 'success';
            $message = 'oro.account.controller.accountuser.confirmed.message';
        }

        $this->get('session')->getFlashBag()->add($messageType, $message);

        return $this->redirect($this->generateUrl('oro_account_account_user_security_login'));
    }
}
