<?php

namespace Oro\Bundle\AccountBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Form\Type\AccountType;

class AccountController extends Controller
{
    /**
     * @Route("/", name="oro_account_index")
     * @Template
     * @AclAncestor("oro_account_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_account.entity.account.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_account_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_account_view",
     *      type="entity",
     *      class="OroAccountBundle:Account",
     *      permission="VIEW"
     * )
     *
     * @param Account $account
     * @return array
     */
    public function viewAction(Account $account)
    {
        return [
            'entity' => $account,
        ];
    }

    /**
     * @Route("/create", name="oro_account_create")
     * @Template("OroAccountBundle:Account:update.html.twig")
     * @Acl(
     *      id="oro_account_create",
     *      type="entity",
     *      class="OroAccountBundle:Account",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new Account());
    }

    /**
     * @Route("/update/{id}", name="oro_account_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_account_update",
     *      type="entity",
     *      class="OroAccountBundle:Account",
     *      permission="EDIT"
     * )
     *
     * @param Account $account
     * @return array
     */
    public function updateAction(Account $account)
    {
        return $this->update($account);
    }

    /**
     * @param Account $account
     * @return array|RedirectResponse
     */
    protected function update(Account $account)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $account,
            $this->createForm(AccountType::NAME, $account),
            function (Account $account) {
                return [
                    'route' => 'oro_account_update',
                    'parameters' => ['id' => $account->getId()],
                ];
            },
            function (Account $account) {
                return [
                    'route' => 'oro_account_view',
                    'parameters' => ['id' => $account->getId()],
                ];
            },
            $this->get('translator')->trans('oro.account.controller.account.saved.message')
        );
    }

    /**
     * @Route("/info/{id}", name="oro_account_info", requirements={"id"="\d+"})
     * @Template("OroAccountBundle:Account/widget:info.html.twig")
     * @AclAncestor("oro_account_view")
     *
     * @param Account $account
     * @return array
     */
    public function infoAction(Account $account)
    {
        return [
            'entity' => $account,
            'treeData' => $this->get('oro_account.account_tree_handler')->createTree($account),
        ];
    }
}
