<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Service\User\UserService;
use Mezzio\Helper\ServerUrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ActivateAccountHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class ActivateAccountHandler extends AbstractHandler
{
    /** @var UserService */
    private $userService;

    /** @var EmailClient */
    private $emailClient;

    /** @var ServerUrlHelper */
    private $serverUrlHelper;

    /**
     * ActivateAccountHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     * @param EmailClient $emailClient
     * @param ServerUrlHelper $serverUrlHelper
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService,
        EmailClient $emailClient,
        ServerUrlHelper $serverUrlHelper
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
        $this->emailClient = $emailClient;
        $this->serverUrlHelper = $serverUrlHelper;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $activationToken = $request->getAttribute('token');

        // The implicitHeadMiddleware will attach an attribute to the request if it detects a HEAD request
        // We only want to continue with account activation if it is not there.
        if (
            $request->getAttribute(
                ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                false
            ) === false
        ) {
            $activated = $this->userService->activate($activationToken);

        if (!$activated) {
            //  If the user activate failed (probably because the token has been used) then redirect home
            return new HtmlResponse($this->renderer->render('actor::activate-account-not-found'));
        }

        $loginUrl = $this->urlHelper->generate('login');

        $signInLink = $this->serverUrlHelper->generate($loginUrl);

        $this->emailClient->sendAccountActivatedConfirmationEmail($activated, $signInLink);

        return new HtmlResponse($this->renderer->render('actor::activate-account'));
    }
}
