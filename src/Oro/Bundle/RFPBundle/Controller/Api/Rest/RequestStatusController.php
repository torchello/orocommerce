<?php

namespace Oro\Bundle\RFPBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\RFPBundle\Entity\RequestStatus;

/**
 * @NamePrefix("oro_api_rfp_")
 */
class RequestStatusController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Rest delete
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete RequestStatus",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_rfp_request_status_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroRFPBundle:RequestStatus"
     * )
     * @return Response
     */
    public function deleteAction($id)
    {
        $requestStatusClass = $this->container->getParameter('oro_rfp.entity.request.status.class');
        $em = $this->get('doctrine')->getManagerForClass($requestStatusClass);

        /** @var RequestStatus $requestStatus */
        $requestStatus = $em->getRepository($requestStatusClass)->find($id);

        if (null === $requestStatus) {
            return $this->handleView(
                $this->view(['successful' => false], Response::HTTP_NOT_FOUND)
            );
        }

        $defaultRequestStatusName = $this->get('oro_config.manager')->get('oro_rfp.default_request_status');

        if ($defaultRequestStatusName === $requestStatus->getName()) {
            return $this->handleView(
                $this->view(['successful' => false], Response::HTTP_FORBIDDEN)
            );
        }

        $requestStatus->setDeleted(true);
        $em->flush();

        return $this->handleView(
            $this->view(
                [
                    'successful' => true,
                    'message' => $this->get('translator')->trans('oro.rfp.message.request_status_deleted')
                ],
                Response::HTTP_OK
            )
        );
    }

    /**
     * @param $id
     *
     * @ApiDoc(
     *      description="Restore RequestStatus",
     *      resource=true
     * )
     * @AclAncestor("oro_rfp_request_status_delete")
     *
     * @return Response
     */
    public function restoreAction($id)
    {
        $requestStatusClass = $this->container->getParameter('oro_rfp.entity.request.status.class');
        $em = $this->get('doctrine')->getManagerForClass($requestStatusClass);
        $requestStatus = $em->getRepository($requestStatusClass)->find($id);

        if (null === $requestStatus) {
            return new JsonResponse(
                $this->get('translator')->trans('oro.rfp.message.request_status_not_found'),
                Response::HTTP_NOT_FOUND
            );
        }

        $requestStatus->setDeleted(false);
        $em->flush();

        return $this->handleView(
            $this->view(
                [
                    'successful' => true,
                    'message' => $this->get('translator')->trans('oro.rfp.message.request_status_restored')
                ],
                Response::HTTP_OK
            )
        );
    }
}
