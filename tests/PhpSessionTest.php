<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\PhpSession;
use Middlewares\Utils\Dispatcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PhpSessionTest extends TestCase
{
    private $sessionOptions = [
        'use_strict_mode' => false,
        'use_trans_sid' => false,
        'use_cookies' => false,
        'use_only_cookies' => true,
        'cache_limiter' => '',
        'cookie_lifetime' => 60 * 60 * 24 * 30, // 30 days
        'cookie_path' => '/path',
        'cookie_domain' => 'domain.tld',
        'cookie_secure' => true,
        'cookie_httponly' => true,
    ];

    private function getCookieHeader(string $sessionName, string $sessionId): string
    {
        return sprintf(
            '%s=%s; expires=%s; path=%s; domain=%s; secure; httponly',
            urlencode($sessionName),
            urlencode($sessionId),
            $this->sessionOptions['cookie_path'],
            $this->sessionOptions['cookie_domain'],
            gmdate('D, d M Y H:i:s T', $this->sessionOptions['cookie_lifetime'])
        );
    }

    public function sessionDataProvider(): array
    {
        return [
            [
                'session_1',
                session_create_id(),
                'Iván',
            ], [
                'session_2',
                session_create_id(),
                'Pablo',
            ],
        ];
    }

    public function testCheckUseTransSidSettingException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('session.use_trans_sid must be false');

        (new PhpSession())->options([
            'use_trans_sid' => true,
        ]);
    }

    public function testCheckUseCookiesSettingException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('session.use_cookies must be false');

        (new PhpSession())->options([
            'use_trans_sid' => false,
            'use_cookies' => true,
        ]);
    }

    public function testCheckUseOnlyCookiesSettingException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('session.use_only_cookies must be true');

        (new PhpSession())->options([
            'use_trans_sid' => false,
            'use_cookies' => false,
            'use_only_cookies' => false,
        ]);
    }

    public function testCheckCacheLimiterSettingException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('session.cache_limiter must be set to an empty string');

        (new PhpSession())->options([
            'use_trans_sid' => false,
            'use_cookies' => false,
            'use_only_cookies' => true,
            'cache_limiter' => 'nocache',
        ]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefaultSettingCheck()
    {
        ini_set('session.use_trans_sid', '0');
        ini_set('session.use_cookies', '0');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cache_limiter', '');
        $instance = new PhpSession();
        $this->assertEquals($instance->options([]), $instance);
    }

    /**
     * @runInSeparateProcess
     */
    public function testWriteSessionCookie()
    {
        $response = Dispatcher::run(
            [
                (new PhpSession())->options($this->sessionOptions),

                function () {
                    echo 'ok';
                },
            ]
        );

        $this->assertTrue($response->hasHeader('Set-Cookie'));
        $this->assertEquals('ok', (string) $response->getBody());
    }

    /**
     * @runInSeparateProcess
     * @dataProvider sessionDataProvider
     */
    public function testPhpSession(string $sessionName, string $sessionId, string $value)
    {
        $response = Dispatcher::run(
            [
                (new PhpSession())
                    ->name($sessionName)
                    ->id($sessionId)
                    ->options($this->sessionOptions),

                function ($request) use ($value) {
                    echo session_name() . ':' . session_id();

                    $_SESSION['name'] = $value;
                },
            ]
        );

        $this->assertFalse($response->hasHeader('Set-Cookie'));
        $this->assertEquals($sessionName . ':' . $sessionId, (string) $response->getBody());
        $this->assertEquals($value, $_SESSION['name']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerateId()
    {
        $sessionId = session_create_id();

        $response = Dispatcher::run(
            [
                (new PhpSession())
                    ->id($sessionId)
                    ->regenerateId(-10)
                    ->options($this->sessionOptions),

                function () use ($sessionId) {
                    $this->assertNotEquals($sessionId, session_id());
                    $this->assertTrue(isset($_SESSION['session-id-expires']));

                    echo 'ok';
                },
            ]
        );

        $this->assertTrue($response->hasHeader('Set-Cookie'));
        $this->assertEquals('ok', (string) $response->getBody());
    }

    /**
     * @runInSeparateProcess
     */
    public function testStrictMode()
    {
        $sessionId = session_create_id();

        $response = Dispatcher::run(
            [
                (new PhpSession())
                    ->id($sessionId)
                    ->options(array_merge($this->sessionOptions, [
                        'use_strict_mode' => true,
                    ])),

                function () {
                    echo session_id();
                },
            ]
        );

        $this->assertTrue($response->hasHeader('Set-Cookie'));
        $this->assertNotEquals($sessionId, (string) $response->getBody());
    }
}
