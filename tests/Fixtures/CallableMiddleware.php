<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swop\GitHubWebHookMiddleware\Tests\Fixtures;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class CallableMiddleware
{
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next = null)
    {
    }
}
