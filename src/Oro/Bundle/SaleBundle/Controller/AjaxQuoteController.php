<?php

namespace Oro\Bundle\SaleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Model\QuoteRequestHandler;

class AjaxQuoteController extends Controller
{
    /**
     * Get order related data
     *
     * @Route("/related-data", name="oro_quote_related_data")
     * @Method({"GET"})
     * @AclAncestor("oro_quote_update")
     *
     * @return JsonResponse
     */
    public function getRelatedDataAction()
    {
        $quote = new Quote();
        $accountUser = $this->getQuoteRequestHandler()->getAccountUser();
        $account = $this->getAccount($accountUser);

        $quote->setAccount($account);
        $quote->setAccountUser($accountUser);

        $accountPaymentTerm = null;
        if ($account) {
            $accountPaymentTerm = $this->getPaymentTermProvider()->getAccountPaymentTerm($account);
        }
        $accountGroupPaymentTerm = null;
        if ($account->getGroup()) {
            $accountGroupPaymentTerm = $this->getPaymentTermProvider()
                ->getAccountGroupPaymentTerm($account->getGroup());
        }

        $orderForm = $this->createForm($this->getQuoteFormTypeName(), $quote);

        return new JsonResponse(
            [
                'shippingAddress' => $this->renderForm(
                    $orderForm->get(AddressType::TYPE_SHIPPING . 'Address')->createView()
                ),
                'accountPaymentTerm' => $accountPaymentTerm ? $accountPaymentTerm->getId() : null,
                'accountGroupPaymentTerm' => $accountGroupPaymentTerm ? $accountGroupPaymentTerm->getId() : null,
            ]
        );
    }

    /**
     * @param FormView $formView
     *
     * @return string
     */
    protected function renderForm(FormView $formView)
    {
        return $this->renderView('OroSaleBundle:Form:accountAddressSelector.html.twig', ['form' => $formView]);
    }

    /**
     * @param AccountUser $accountUser
     * @return null|Account
     */
    protected function getAccount(AccountUser $accountUser = null)
    {
        $account = $this->getQuoteRequestHandler()->getAccount();
        if (!$account && $accountUser) {
            $account = $accountUser->getAccount();
        }
        if ($account && $accountUser) {
            $this->validateRelation($accountUser, $account);
        }

        return $account;
    }

    /**
     * @param AccountUser $accountUser
     * @param Account $account
     *
     * @throws BadRequestHttpException
     */
    protected function validateRelation(AccountUser $accountUser, Account $account)
    {
        if ($accountUser && $accountUser->getAccount() && $accountUser->getAccount()->getId() !== $account->getId()) {
            throw new BadRequestHttpException('AccountUser must belong to Account');
        }
    }

    /**
     * @return PaymentTermProvider
     */
    protected function getPaymentTermProvider()
    {
        return $this->get('oro_payment.provider.payment_term');
    }

    /**
     * {@inheritdoc}
     */
    protected function getQuoteFormTypeName()
    {
        return QuoteType::NAME;
    }

    /**
     * @return QuoteRequestHandler
     */
    protected function getQuoteRequestHandler()
    {
        return $this->get('oro_sale.service.quote_request_handler');
    }
}
