<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Swop\GitHubWebHookMiddleware;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swop\GitHubWebHook\Exception\InvalidGitHubRequestSignatureException;
use Swop\GitHubWebHook\Security\SignatureValidatorInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * This class offers a PSR-7 style & PSR-15 compatible middleware which could be used to verify that a request
 * coming from GitHub in a web hook context contains proper signature based on the provided secret.
 *
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GithubWebHook implements ServerMiddlewareInterface
{
    /** @var SignatureValidatorInterface */
    private $signatureValidator;
    /** @var string */
    private $secret;

    /**
     * @param SignatureValidatorInterface $signatureValidator GitHub web hook signature validator
     * @param string                      $secret             GitHub web hook secret
     */
    public function __construct(SignatureValidatorInterface $signatureValidator, $secret)
    {
        $this->signatureValidator = $signatureValidator;
        $this->secret             = $secret;
    }

    /**
     * Execute the middleware.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param callable          $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (null !== $securityResponse = $this->checkSecurity($request)) {
            return $securityResponse;
        }

        return $next($request, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if (null !== $securityResponse = $this->checkSecurity($request)) {
            return $securityResponse;
        }

        return $delegate->process($request);
    }

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface|null
     */
    private function checkSecurity(RequestInterface $request)
    {
        try {
            $this->signatureValidator->validate($request, $this->secret);
        } catch (InvalidGitHubRequestSignatureException $e) {
            return new JsonResponse(
                [
                    'error'   => 401,
                    'message' => 'Unauthorized'
                ],
                401
            );
        }

        return null;
    }
}
