<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Swop\GitHubWebHookMiddleware\Tests;

use Prophecy\Argument;
use Swop\GitHubWebHook\Exception\InvalidGitHubRequestSignatureException;
use Swop\GitHubWebHookMiddleware\GithubWebHook;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GitHubWebHookTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider styleDataProvider
     *
     * @param string $style
     */
    public function testInvalidRequestShouldReturnA401Response($style)
    {
        $request         = new ServerRequest();
        $initialResponse = new Response();

        $signatureValidator = $this->prophesize('Swop\GitHubWebHook\Security\SignatureValidator');
        $signatureValidator
            ->validate(Argument::is($request), 'my_secret')
            ->shouldBeCalled()
            ->willThrow(new InvalidGitHubRequestSignatureException($request, 'signature'))
        ;

        $middleware = new GitHubWebHook($signatureValidator->reveal(), 'my_secret');

        switch ($style) {
            case 'PSR-7':
                $next = $this->prophesize('Swop\GitHubWebHookMiddleware\Tests\Fixtures\CallableMiddleware');
                $next->__invoke(Argument::any())->shouldNotBeCalled();
                $response = call_user_func_array($middleware, [$request, $initialResponse, $next->reveal()]);
                break;
            case 'PSR-15':
                $next = $this->prophesize('Interop\Http\Middleware\DelegateInterface');
                $next->process(Argument::any())->shouldNotBeCalled();

                $response = $middleware->process($request, $next->reveal());
                break;
        }

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(
            json_encode(array('error' => 401, 'message' => 'Unauthorized')),
            (string)$response->getBody()
        );
        $this->assertEquals(['content-type' => ['application/json']], $response->getHeaders());
    }

    /**
     * @dataProvider styleDataProvider
     *
     * @param string $style
     */
    public function testValidRequestShouldBeHandledByTheNextMiddleware($style)
    {
        $request          = new ServerRequest();
        $initialResponse  = new Response();
        $expectedResponse = new Response();

        $signatureValidator = $this->prophesize('Swop\GitHubWebHook\Security\SignatureValidator');
        $signatureValidator
            ->validate(Argument::is($request), 'my_secret')
            ->shouldBeCalled()
        ;

        $middleware = new GitHubWebHook($signatureValidator->reveal(), 'my_secret');

        switch ($style) {
            case 'PSR-7':
                $next = $this->prophesize('Swop\GitHubWebHookMiddleware\Tests\Fixtures\CallableMiddleware');
                $next->__invoke(
                    Argument::is($request),
                    Argument::is($initialResponse)
                )
                    ->shouldBeCalledTimes(1)
                    ->willReturn($expectedResponse)
                ;
                $response = $middleware($request, $initialResponse, $next->reveal());
                break;
            case 'PSR-15':
                $next = $this->prophesize('Interop\Http\Middleware\DelegateInterface');
                $next->process(Argument::is($request))
                    ->shouldBeCalledTimes(1)
                    ->willReturn($expectedResponse)
                ;

                $response = $middleware->process($request, $next->reveal());
                break;
        }

        $this->assertEquals($expectedResponse, $response);
    }

    public function styleDataProvider()
    {
        return [
            ['PSR-7'],
            ['PSR-15'],
        ];
    }
}
