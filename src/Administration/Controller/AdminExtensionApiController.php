<?php declare(strict_types=1);

namespace Shopware\Administration\Controller;

use Shopware\Core\Framework\App\ActionButton\AppAction;
use Shopware\Core\Framework\App\ActionButton\Executor;
use Shopware\Core\Framework\App\AppCollection;
use Shopware\Core\Framework\App\AppException;
use Shopware\Core\Framework\App\Hmac\QuerySigner;
use Shopware\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal Only to be used by the admin-extension-sdk.
 */
#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('framework')]
class AdminExtensionApiController extends AbstractController
{
    /**
     * @param EntityRepository<AppCollection> $appRepository
     */
    public function __construct(
        private readonly Executor $executor,
        private readonly AppPayloadServiceHelper $appPayloadServiceHelper,
        private readonly EntityRepository $appRepository,
        private readonly QuerySigner $querySigner
    ) {
    }

    #[Route(path: '/api/_action/extension-sdk/run-action', name: 'api.action.extension-sdk.run-action', methods: ['POST'])]
    public function runAction(RequestDataBag $requestDataBag, Context $context): Response
    {
        $appName = $requestDataBag->get('appName');
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('name', $appName)
        );

        $app = $this->appRepository->search($criteria, $context)->getEntities()->first();
        if (!$app) {
            throw AppException::appNotFoundByName($appName);
        }

        if (!$app->getAppSecret()) {
            throw AppException::appSecretMissing($app->getName());
        }

        $targetUrl = $requestDataBag->getString('url');
        $targetHost = \parse_url($targetUrl, \PHP_URL_HOST);
        $allowedHosts = $app->getAllowedHosts() ?? [];
        if (!$targetHost || !\in_array($targetHost, $allowedHosts, true)) {
            throw AppException::hostNotAllowed($targetUrl, $app->getName());
        }

        $ids = $requestDataBag->get('ids', []);
        if (!$ids instanceof RequestDataBag) {
            throw AppException::invalidArgument('Ids must be an array');
        }

        $action = new AppAction(
            $app,
            $this->appPayloadServiceHelper->buildSource($app->getVersion(), $app->getName()),
            $targetUrl,
            $requestDataBag->getString('entity'),
            $requestDataBag->getString('action'),
            $ids->all(),
            Uuid::randomHex()
        );

        return $this->executor->execute($action, $context);
    }

    #[Route(path: '/api/_action/extension-sdk/sign-uri', name: 'api.action.extension-sdk.sign-uri', methods: ['POST'])]
    public function signUri(RequestDataBag $requestDataBag, Context $context): Response
    {
        $appName = $requestDataBag->get('appName');
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('name', $appName)
        );

        $app = $this->appRepository->search($criteria, $context)->getEntities()->first();
        if (!$app) {
            throw AppException::appNotFoundByName($appName);
        }

        $uri = $this->querySigner->signUri($requestDataBag->get('uri'), $app, $context)->__toString();

        return new JsonResponse([
            'uri' => $uri,
        ]);
    }
}
