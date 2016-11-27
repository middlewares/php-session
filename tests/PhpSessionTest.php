<?php

namespace Middlewares\Tests;

use Middlewares\PhpSession;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\CallableMiddleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;

class PhpSessionTest extends \PHPUnit_Framework_TestCase
{
    public function sessionDataProvider()
    {
        return [
            [
                'session_1',
                'Iván',
            ], [
                'session_2',
                'Pablo',
            ],
        ];
    }

    /**
     * @dataProvider sessionDataProvider
     */
    public function testPhpSession($sessionName, $value)
    {
        $response = (new Dispatcher([
            (new PhpSession())->name($sessionName),
            new CallableMiddleware(function ($request) use ($value) {
                $response = new Response();

                $response->getBody()->write(session_name());
                $_SESSION['name'] = $value;

                return $response;
            }),
        ]))->dispatch(new ServerRequest());

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);

        $this->assertEquals($sessionName, (string) $response->getBody());
        $this->assertEquals($value, $_SESSION['name']);
    }
}
