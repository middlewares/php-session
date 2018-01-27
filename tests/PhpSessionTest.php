<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\PhpSession;
use Middlewares\Utils\Dispatcher;
use PHPUnit\Framework\TestCase;

class PhpSessionTest extends TestCase
{
    public function sessionDataProvider(): array
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
     * @runInSeparateProcess
     * @dataProvider sessionDataProvider
     */
    public function testPhpSession(string $sessionName, string $value)
    {
        $response = Dispatcher::run(
            [
                (new PhpSession())
                    ->name($sessionName)
                    ->options([
                        'use_cookies' => false,
                        'use_only_cookies' => true,
                    ]),

                function ($request) use ($value) {
                    echo session_name();

                    $_SESSION['name'] = $value;
                },
            ]
        );

        $this->assertEquals($sessionName, (string) $response->getBody());
        $this->assertEquals($value, $_SESSION['name']);
    }
}
