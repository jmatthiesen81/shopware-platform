<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Storefront\Controller;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerException;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractImitateCustomerRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractLogoutRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractResetPasswordRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractSendPasswordRecoveryMailRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Checkout\Customer\SalesChannel\ImitateCustomerRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\LoginRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\LogoutRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\ResetPasswordRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\SendPasswordRecoveryMailRoute;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\SalesChannel\OrderRoute;
use Shopware\Core\Content\ContactForm\SalesChannel\AbstractContactFormRoute;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterUnsubscribeRoute;
use Shopware\Core\Framework\Adapter\Translation\AbstractTranslator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\RateLimiter\Exception\RateLimitExceededException;
use Shopware\Core\Framework\RateLimiter\RateLimiter;
use Shopware\Core\Framework\Test\RateLimiter\DisableRateLimiterCompilerPass;
use Shopware\Core\Framework\Test\RateLimiter\RateLimiterTestTrait;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\SalesChannelRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Integration\Traits\CustomerTestTrait;
use Shopware\Core\Test\Integration\Traits\OrderFixture;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use Shopware\Storefront\Checkout\Cart\SalesChannel\StorefrontCartFacade;
use Shopware\Storefront\Controller\AuthController;
use Shopware\Storefront\Controller\FormController;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoader;
use Shopware\Storefront\Page\Account\Order\AccountOrderPageLoader;
use Shopware\Storefront\Page\Account\RecoverPassword\AccountRecoverPasswordPageLoader;
use Shopware\Storefront\Page\GenericPageLoader;
use Shopware\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[Group('slow')]
class ControllerRateLimiterTest extends TestCase
{
    use CustomerTestTrait;
    use OrderFixture;
    use RateLimiterTestTrait;
    use StorefrontControllerTestBehaviour;

    private Context $context;

    private IdsCollection $ids;

    private KernelBrowser $browser;

    private SalesChannelContext $salesChannelContext;

    private TranslatorInterface $translator;

    public static function setUpBeforeClass(): void
    {
        DisableRateLimiterCompilerPass::disableNoLimit();
        KernelLifecycleManager::bootKernel(true, Uuid::randomHex());
    }

    public static function tearDownAfterClass(): void
    {
        DisableRateLimiterCompilerPass::enableNoLimit();
        KernelLifecycleManager::bootKernel(true, Uuid::randomHex());
    }

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);

        $salesChannelContextFactory = static::getContainer()->get(SalesChannelContextFactory::class)->getDecorated();
        $this->salesChannelContext = $salesChannelContextFactory->create(Uuid::randomHex(), $this->ids->get('sales-channel'));

        $this->clearCache();

        $session = $this->getSession();
        static::assertInstanceOf(Session::class, $session);
        $session->getFlashBag()->clear();

        $this->translator = static::getContainer()->get('translator');
    }

    public function testGenerateAccountRecoveryRateLimit(): void
    {
        $passwordRecoveryMailRoute = $this->createMock(SendPasswordRecoveryMailRoute::class);
        $passwordRecoveryMailRoute->method('sendRecoveryMail')->willThrowException(new RateLimitExceededException(time() + 10));

        $controller = new AuthController(
            static::getContainer()->get(AccountLoginPageLoader::class),
            $passwordRecoveryMailRoute,
            static::getContainer()->get(ResetPasswordRoute::class),
            static::getContainer()->get(LoginRoute::class),
            static::getContainer()->get(LogoutRoute::class),
            static::getContainer()->get(ImitateCustomerRoute::class),
            static::getContainer()->get(StorefrontCartFacade::class),
            static::getContainer()->get(AccountRecoverPasswordPageLoader::class)
        );
        $controller->setContainer(static::getContainer());

        $request = $this->createRequest('frontend.account.recover.request');

        static::getContainer()->get('request_stack')->push($request);

        $controller->generateAccountRecovery($request, new RequestDataBag([
            'email' => [
                'email' => 'test@example.com',
            ],
        ]), $this->salesChannelContext);

        $session = $this->getSession();
        static::assertInstanceOf(Session::class, $session);
        $flashBag = $session->getFlashBag();

        static::assertNotEmpty($flash = $flashBag->get('info'));
        static::assertSame($this->translator->trans('error.rateLimitExceeded', ['%seconds%' => 10]), $flash[0]);
    }

    public function testAuthControllerGuestLoginShowsRateLimit(): void
    {
        $controller = new AuthController(
            static::getContainer()->get(AccountLoginPageLoader::class),
            $this->createMock(AbstractSendPasswordRecoveryMailRoute::class),
            $this->createMock(AbstractResetPasswordRoute::class),
            $this->createMock(LoginRoute::class),
            $this->createMock(AbstractLogoutRoute::class),
            $this->createMock(AbstractImitateCustomerRoute::class),
            static::getContainer()->get(StorefrontCartFacade::class),
            static::getContainer()->get(AccountRecoverPasswordPageLoader::class)
        );
        $controller->setContainer(static::getContainer());

        $request = $this->createRequest('frontend.account.guest.login.page', [
            'redirectTo' => 'frontend.account.order.single.page',
            'redirectParameters' => ['deepLinkCode' => 'example'],
            'loginError' => false,
            'waitTime' => 5,
        ]);

        static::getContainer()->get('request_stack')->push($request);

        $response = $controller->guestLoginPage($request, $this->salesChannelContext);

        $contentReturn = $response->getContent();
        $crawler = new Crawler();
        $crawler->addHtmlContent((string) $contentReturn);

        $errorContent = $crawler->filterXPath('//div[@class="flashbags container"]//div[@class="alert-content-container"]')->text();

        static::assertStringContainsString($this->translator->trans('account.loginThrottled', ['%seconds%' => 5]), $errorContent);
    }

    public function testAuthControllerLoginShowsRateLimit(): void
    {
        $loginRoute = $this->createMock(LoginRoute::class);
        $loginRoute->method('login')->willThrowException(CustomerException::customerAuthThrottledException(5));

        $controller = new AuthController(
            static::getContainer()->get(AccountLoginPageLoader::class),
            $this->createMock(AbstractSendPasswordRecoveryMailRoute::class),
            $this->createMock(AbstractResetPasswordRoute::class),
            $loginRoute,
            $this->createMock(AbstractLogoutRoute::class),
            $this->createMock(AbstractImitateCustomerRoute::class),
            static::getContainer()->get(StorefrontCartFacade::class),
            static::getContainer()->get(AccountRecoverPasswordPageLoader::class)
        );
        $controller->setContainer(static::getContainer());

        $request = $this->createRequest('frontend.account.login');

        static::getContainer()->get('request_stack')->push($request);

        $response = $controller->login($request, new RequestDataBag([
            'email' => 'test@example.com',
            'password' => 'wrong',
        ]), $this->salesChannelContext);

        $contentReturn = $response->getContent();
        $crawler = new Crawler();
        $crawler->addHtmlContent((string) $contentReturn);

        $errorContent = $crawler->filterXPath('//form[@class="login-form"]//div[@class="alert-content-container"]')->text();

        static::assertStringContainsString($this->translator->trans('account.loginThrottled', ['%seconds%' => 5], 'messages', 'en-GB'), $errorContent);
    }

    public function testFormControllerRateLimit(): void
    {
        $contactFormRoute = $this->createMock(AbstractContactFormRoute::class);
        $contactFormRoute->method('load')->willThrowException(new RateLimitExceededException(time() + 5));

        $controller = new FormController(
            $contactFormRoute,
            static::getContainer()->get(NewsletterSubscribeRoute::class),
            static::getContainer()->get(NewsletterUnsubscribeRoute::class),
        );
        $controller->setContainer(static::getContainer());

        $response = $controller->sendContactForm(new RequestDataBag([
        ]), $this->salesChannelContext);

        $content = \json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertCount(1, $content);
        static::assertArrayHasKey('type', $content[0]);
        static::assertSame('info', $content[0]['type']);

        $contentReturn = $content[0]['alert'];
        $crawler = new Crawler();
        $crawler->addHtmlContent($contentReturn);

        $errorContent = $crawler->filterXPath('//div[@class="alert-content-container"]')->text();

        static::assertStringContainsString($this->translator->trans('error.rateLimitExceeded', ['%seconds%' => 5]), $errorContent);
    }

    public function testResetAccountOrderRateLimit(): void
    {
        $orderRoute = new OrderRoute(
            static::getContainer()->get('order.repository'),
            static::getContainer()->get('promotion.repository'),
            $this->mockResetLimiter([
                RateLimiter::GUEST_LOGIN => 1,
            ]),
            static::getContainer()->get('event_dispatcher'),
        );

        $order = $this->createCustomerWithOrder();

        $controller = new AccountOrderPageLoader(
            $this->createMock(GenericPageLoader::class),
            $this->createMock(EventDispatcher::class),
            $orderRoute,
            $this->createMock(AccountService::class),
            $this->createMock(AbstractTranslator::class)
        );

        $controller->load(new Request([
            'deepLinkCode' => $order->getDeepLinkCode(),
            'email' => 'orderTest@example.com',
            'zipcode' => '12345',
        ]), $this->salesChannelContext);
    }

    public function testAccountOrderRateLimit(): void
    {
        $order = $this->createCustomerWithOrder();

        for ($i = 0; $i <= 10; ++$i) {
            $this->browser->request(
                'POST',
                '/account/order/' . $order->getDeepLinkCode(),
                $this->tokenize('frontend.account.order.single.page', [
                    'email' => 'orderTest@example.com',
                    'zipcode' => 'wrong',
                ])
            );

            $response = $this->browser->getResponse();
            static::assertInstanceOf(RedirectResponse::class, $response);

            $waitTime = $i >= 10 ? $this->queryFromString($response->getTargetUrl(), 'waitTime') : 0;

            $this->browser->request(
                'GET',
                $response->getTargetUrl()
            );

            $contentReturn = $this->browser->getResponse()->getContent();
            $crawler = new Crawler();
            $crawler->addHtmlContent((string) $contentReturn);

            $errorContent = $crawler->filterXPath('//div[@class="flashbags container"]//div[@class="alert-content-container"]')->text();

            if ($i >= 10) {
                static::assertStringContainsString($this->translator->trans('account.loginThrottled', ['%seconds%' => $waitTime]), $errorContent);
            } else {
                static::assertStringContainsString($this->translator->trans('account.orderGuestLoginWrongCredentials'), $errorContent);
            }
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    private function createRequest(string $route, array $params = []): Request
    {
        $request = new Request();
        $request->query->add($params);
        $request->setSession($this->getSession());
        $request->headers->set('HOST', 'localhost');
        $request->attributes->add([
            '_route' => $route,
            SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST => true,
            PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID => $this->ids->get('sales-channel'),
            PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT => $this->salesChannelContext,
            RequestTransformer::STOREFRONT_URL => 'http://localhost',
        ]);

        return $request;
    }

    private function createCustomerWithOrder(): OrderEntity
    {
        $orderId = Uuid::randomHex();
        $customerId = $this->createCustomer('orderTest@example.com', true);

        static::getContainer()->get('customer.repository')->update([
            [
                'id' => $customerId,
                'salesChannelId' => $this->ids->get('sales-channel'),
            ],
        ], $this->context);

        $orderData = $this->getOrderData($orderId, $this->context);
        $orderData[0]['orderCustomer']['customer'] = ['id' => $customerId];
        $orderData[0]['orderCustomer']['email'] = 'orderTest@example.com';
        $orderData[0]['orderCustomer']['addresses'][0]['zipcode'] = '12345';
        $orderData[0]['addresses'][0]['zipcode'] = '12345';
        $orderData[0]['salesChannelId'] = $this->ids->get('sales-channel');

        $orderRepository = static::getContainer()->get('order.repository');
        $orderRepository->create($orderData, $this->context);

        $order = $orderRepository->search(new Criteria([$orderId]), $this->context)->first();

        static::assertNotNull($order);
        static::assertInstanceOf(OrderEntity::class, $order);

        return $order;
    }

    private function queryFromString(string $url, string $param): string
    {
        $rawParams = \parse_url($url, \PHP_URL_QUERY);
        static::assertIsString($rawParams);

        \parse_str($rawParams, $params);

        static::assertArrayHasKey($param, $params);
        static::assertIsString($params[$param]);

        return $params[$param];
    }
}
