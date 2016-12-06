<?php

namespace Middlewares\Tests;

use Middlewares\PhpSession;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

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
        $request = Factory::createServerRequest();

        $response = (new Dispatcher([
            (new PhpSession())->name($sessionName),
            function ($request) use ($value) {
                echo session_name();

                $_SESSION['name'] = $value;
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);

        $this->assertEquals($sessionName, (string) $response->getBody());
        $this->assertEquals($value, $_SESSION['name']);
    }
}
