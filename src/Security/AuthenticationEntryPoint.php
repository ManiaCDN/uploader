<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface {

    private $router;

    public function __construct(RouterInterface $router) {
        $this->router = $router;
    }

    /**
     * @inheritDoc
     */
    public function start(Request $request, AuthenticationException $authException = null) {
        return new RedirectResponse($this->router->generate('connect_maniaplanet'));
    }
}
