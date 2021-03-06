<?php

namespace Oro\Bundle\AccountBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\AddressBundle\Form\Handler\AddressHandler;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\AccountUserAddress;

class AccountUserAddressController extends Controller
{
    /**
     * @Route("/", name="oro_account_frontend_account_user_address_index")
     * @Layout(vars={"entity_class", "account_address_count", "account_user_address_count"})
     * @AclAncestor("oro_account_frontend_account_user_address_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_account.entity.account_user_address.class'),
            'account_user_address_count' => $this->getUser()->getAddresses()->count(),
            'account_address_count' => $this->getUser()->getAccount()->getAddresses()->count(),
            'data' => [
                'entity' => $this->getUser()
            ]
        ];
    }

    /**
     * @Route(
     *     "/{entityId}/address-create",
     *     name="oro_account_frontend_account_user_address_create",
     *     requirements={"entityId":"\d+"}
     * )
     * @Acl(
     *      id="oro_account_frontend_account_user_address_create",
     *      type="entity",
     *      class="OroAccountBundle:AccountUserAddress",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     * @Layout
     *
     * @ParamConverter("accountUser", options={"id" = "entityId"})
     *
     * @param AccountUser $accountUser
     * @param Request $request
     * @return array
     */
    public function createAction(AccountUser $accountUser, Request $request)
    {
        return $this->update($accountUser, new AccountUserAddress(), $request);
    }

    /**
     * @Route(
     *     "/{entityId}/address/{id}/update",
     *     name="oro_account_frontend_account_user_address_update",
     *     requirements={"entityId":"\d+", "id":"\d+"}
     * )
     * @Acl(
     *      id="oro_account_frontend_account_user_address_update",
     *      type="entity",
     *      class="OroAccountBundle:AccountUserAddress",
     *      permission="EDIT",
     *      group_name="commerce"
     * )
     * @Layout
     *
     * @ParamConverter("accountUser", options={"id" = "entityId"})
     *
     * @param AccountUser $accountUser
     * @param AccountUserAddress $accountAddress
     * @param Request $request
     * @return array
     */
    public function updateAction(AccountUser $accountUser, AccountUserAddress $accountAddress, Request $request)
    {
        return $this->update($accountUser, $accountAddress, $request);
    }

    /**
     * @param AccountUser $accountUser
     * @param AccountUserAddress $accountAddress
     * @param Request $request
     * @return array
     */
    private function update(AccountUser $accountUser, AccountUserAddress $accountAddress, Request $request)
    {
        $this->prepareEntities($accountUser, $accountAddress, $request);

        $form = $this->get('oro_account.provider.fronted_account_user_address_form')
            ->getAddressForm($accountAddress, $accountUser)
            ->getForm();

        $currentUser = $this->getUser();

        $manager = $this->getDoctrine()->getManagerForClass(
            $this->container->getParameter('oro_account.entity.account_user_address.class')
        );

        $handler = new AddressHandler($form, $request, $manager);

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $form->getData(),
            $form,
            function (AccountUserAddress $accountAddress) use ($accountUser) {
                return [
                    'route' => 'oro_account_frontend_account_user_address_update',
                    'parameters' => ['id' => $accountAddress->getId(), 'entityId' => $accountUser->getId()],
                ];
            },
            function (AccountUserAddress $accountAddress) use ($accountUser, $currentUser) {
                if ($currentUser instanceof AccountUser && $currentUser->getId() === $accountUser->getId()) {
                    return ['route' => 'oro_account_frontend_account_user_address_index'];
                } else {
                    return [
                        'route' => 'oro_account_frontend_account_user_view',
                        'parameters' => ['id' => $accountUser->getId()],
                    ];
                }
            },
            $this->get('translator')->trans('oro.account.controller.accountuseraddress.saved.message'),
            $handler,
            function (AccountUserAddress $accountAddress, FormInterface $form, Request $request) {
                $url = $request->getUri();
                if ($request->headers->get('referer')) {
                    $url = $request->headers->get('referer');
                }

                return [
                    'backToUrl' => $url
                ];
            }
        );

        if ($result instanceof Response) {
            return $result;
        }

        return [
            'data' => array_merge($result, ['accountUser' => $accountUser])
        ];
    }

    /**
     * @param AccountUser $accountUser
     * @param AccountUserAddress $accountAddress
     * @param Request $request
     */
    private function prepareEntities(AccountUser $accountUser, AccountUserAddress $accountAddress, Request $request)
    {
        if ($request->getMethod() === 'GET' && !$accountAddress->getId()) {
            $accountAddress->setFirstName($accountUser->getFirstName());
            $accountAddress->setLastName($accountUser->getLastName());
            if (!$accountUser->getAddresses()->count()) {
                $accountAddress->setPrimary(true);
            }
        }

        if (!$accountAddress->getFrontendOwner()) {
            $accountUser->addAddress($accountAddress);
        } elseif ($accountAddress->getFrontendOwner()->getId() !== $accountUser->getId()) {
            throw new BadRequestHttpException('Address must belong to AccountUser');
        }
    }
}
