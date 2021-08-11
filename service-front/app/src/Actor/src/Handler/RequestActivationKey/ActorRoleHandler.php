<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorRole;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ActorRoleHandler
 * @package Actor\RequestActivationKey\Handler
 * @codeCoverageIgnore
 */
class ActorRoleHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;

    private ActorRole $form;
    private ?UserInterface $user;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator
    ) {
        parent::__construct($renderer, $urlHelper);
        $this->setAuthenticator($authenticator);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new ActorRole($this->getCsrfGuard($request));
        $this->user = $this->getUser($request);

        if ($request->getMethod() == 'POST') {
            return $this->handlePost($request);
        }

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/actor-role',
            [
                'user'  => $this->user,
                'form'  => $this->form
            ]
        ));
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $selected = $this->form->getData()['actor_role_radio'];

            if ($selected === 'Donor') {
                return $this->redirectToRoute('lpa.add.contact-details');
            } else {
                return $this->redirectToRoute('lpa.add.donor-details');
            }
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/actor-role', [
            'user'  => $this->user,
            'form'  => $this->form
        ]));
    }
}
