<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxLocalizationController extends Controller
{
    /**
     * @Route("/set-current-localization", name="oro_frontend_localization_frontend_set_current_localization")
     * @Method({"POST"})
     *
     * {@inheritdoc}
     */
    public function setCurrentLocalizationAction(Request $request)
    {
        $localization = $this->get('oro_locale.manager.localization')->getLocalization($request->get('localization'));
        $userLocalizationManager = $this->get('oro_frontend_localization.manager.user_localization');

        $result = false;
        if (in_array($localization, $userLocalizationManager->getEnabledLocalizations(), true)) {
            $userLocalizationManager->setCurrentLocalization($localization);
            $result = true;
        }

        return new JsonResponse(['success' => $result]);
    }
}
