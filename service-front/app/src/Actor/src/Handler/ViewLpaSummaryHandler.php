<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class ViewLpaSummaryHandler extends AbstractHandler implements UserAware
{
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        private LpaService $lpaService,
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actorLpaToken = $request->getQueryParams()['lpa'];

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $user     = $this->getUser($request);
        $identity = !is_null($user) ? $user->getIdentity() : null;

        //UML-1394 TO BE REMOVED IN FUTURE TO SHOW PAGE NOT FOUND WITH APPROPRIATE CONTENT
        $lpaData = $this->lpaService->getLpaById($identity, $actorLpaToken);

        if (is_null($lpaData)) {
            return $this->redirectToRoute('lpa.dashboard');
        }

        return new HtmlResponse(
            $this->renderer->render(
                'actor::view-lpa-summary',
                [
                    'actorToken' => $actorLpaToken,
                    'user'       => $user,
                    'lpa'        => $lpaData->lpa,
                    'actor'      => $lpaData->actor,
                ]
            )
        );
    }
}
