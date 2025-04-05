<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\PhpSession;
use Middlewares\Utils\Dispatcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PhpSessionTest extends TestCase
{
    /** @var array<string,bool|string|int|float> */
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

    /**
     * @return array<int, array<int, bool|string>>
     */
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

    public function testCheckUseTransSidSettingException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('session.use_trans_sid must be false');

        (new PhpSession())->options([
            'use_trans_sid' => true,
        ]);
    }

    public function testCheckUseCookiesSettingException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('session.use_cookies must be false');

        (new PhpSession())->options([
            'use_trans_sid' => false,
            'use_cookies' => true,
        ]);
    }

    public function testCheckUseOnlyCookiesSettingException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('session.use_only_cookies must be true');

        (new PhpSession())->options([
            'use_trans_sid' => false,
            'use_cookies' => false,
            'use_only_cookies' => false,
        ]);
    }

    public function testCheckCacheLimiterSettingException(): void
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
    public function testDefaultSettingCheck(): void
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
    public function testWriteSessionCookie(): void
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

        $parsed = static::parseCookie($response->getHeaderLine('Set-Cookie'));

        $this->assertArrayHasKey('PHPSESSID', $parsed);
        $this->assertArrayHasKey('Expires', $parsed);
        $this->assertArrayHasKey('Max-Age', $parsed);
        $this->assertArrayNotHasKey('SameSite', $parsed);
        $this->assertEquals(true, $parsed['HttpOnly']);
        $this->assertEquals(true, $parsed['Secure']);
        $this->assertEquals('domain.tld', $parsed['Domain']);
        $this->assertEquals('/path', $parsed['Path']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCookieParamsAreConsidered(): void
    {
        $response = Dispatcher::run(
            [
                (new PhpSession())->options([
                    // session params
                    'use_cookies' => false,
                    'cache_limiter' => '',

                    // cookie params
                    'lifetime' => 10,
                    'path' => '/middlewares',
                    'domain' => 'middlewares.dev',
                    'secure' => '',
                    'httponly' => '',
                    'samesite' => 'Strict',
                ]),
            ]
        );

        $this->assertTrue($response->hasHeader('Set-Cookie'));

        $cookie = $response->getHeaderLine('Set-Cookie');
        $parsed = static::parseCookie($cookie);

        $this->assertEquals('Strict', $parsed['SameSite']);
        $this->assertEquals('middlewares.dev', $parsed['Domain']);
        $this->assertEquals('/middlewares', $parsed['Path']);
        $this->assertArrayNotHasKey('HttpOnly', $parsed);
        $this->assertArrayNotHasKey('Secure', $parsed);
        $this->assertArrayHasKey('Expires', $parsed);
        $this->assertArrayHasKey('PHPSESSID', $parsed);
        $this->assertEquals('10', $parsed['Max-Age']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCookieParamsOptionOverwritePhpCookieParamsAreConsidered(): void
    {
        // these are ignored
        static::setPhpCookieParams();

        $response = Dispatcher::run(
            [
                (new PhpSession())->options([
                    // session params
                    'use_cookies' => false,
                    'cache_limiter' => '',

                    // cookie params
                    'lifetime' => 10,
                    'path' => '/middlewares',
                    'domain' => 'middlewares.dev',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]),
            ]
        );

        $this->assertTrue($response->hasHeader('Set-Cookie'));

        $cookie = $response->getHeaderLine('Set-Cookie');
        $parsed = static::parseCookie($cookie);

        if (PHP_VERSION_ID >= 70400) {
            $this->assertEquals('Strict', $parsed['SameSite']);
        }

        $this->assertEquals('middlewares.dev', $parsed['Domain']);
        $this->assertEquals('/middlewares', $parsed['Path']);
        $this->assertEquals(true, $parsed['HttpOnly']);
        $this->assertEquals(true, $parsed['Secure']);
        $this->assertArrayHasKey('Expires', $parsed);
        $this->assertArrayHasKey('PHPSESSID', $parsed);
        $this->assertEquals('10', $parsed['Max-Age']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testPhpCookieParamsAreConsideredIfCookieParamsAreNotSpecified(): void
    {
        // these are taken into account now
        static::setPhpCookieParams();

        $response = Dispatcher::run(
            [
                (new PhpSession())->options([
                    // session params
                    'use_cookies' => false,
                    'cache_limiter' => '',
                ]),
            ]
        );

        $this->assertTrue($response->hasHeader('Set-Cookie'));

        $cookie = $response->getHeaderLine('Set-Cookie');
        $parsed = static::parseCookie($cookie);

        if (PHP_VERSION_ID >= 70400) {
            $this->assertEquals('Strict', $parsed['SameSite']);
        }

        $this->assertEquals('wild.dev', $parsed['Domain']);
        $this->assertEquals('/wild', $parsed['Path']);
        $this->assertArrayNotHasKey('HttpOnly', $parsed);
        $this->assertArrayNotHasKey('Secure', $parsed);
        $this->assertArrayHasKey('Expires', $parsed);
        $this->assertArrayHasKey('PHPSESSID', $parsed);
        $this->assertEquals('99', $parsed['Max-Age']);
    }

    /**
     * @runInSeparateProcess
     * @dataProvider sessionDataProvider
     */
    public function testPhpSession(string $sessionName, string $sessionId, string $value): void
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
    public function testRegenerateId(): void
    {
        $sessionId = session_create_id();

        $response = Dispatcher::run(
            [
                (new PhpSession())
                    ->id((string) $sessionId)
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
    public function testStrictMode(): void
    {
        $sessionId = session_create_id();

        $response = Dispatcher::run(
            [
                (new PhpSession())
                    ->id((string) $sessionId)
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

    /**
     * @return array<string,string|bool>
     */
    private static function parseCookie(string $cookieHeader): array
    {
        $parts = explode(';', $cookieHeader);
        $cookie = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '=') !== false) {
                list($key, $value) = explode('=', $part, 2);
                $cookie[$key] = $value;
            } else {
                // It's a flag (like Secure, HttpOnly)
                $cookie[$part] = true;
            }
        }

        return $cookie;
    }

    private static function setPhpCookieParams(): void
    {
        if (PHP_VERSION_ID >= 70400) {
            session_set_cookie_params([
                'lifetime' => 99,
                'path' => '/wild',
                'domain' => 'wild.dev',
                'secure' => '',
                'httponly' => '',
                'samesite' => 'Strict',
            ]);
        } elseif (PHP_VERSION_ID >= 70200) {
            session_set_cookie_params(
                99,
                '/wild',
                'wild.dev',
                false,
                false
            );
        }
    }
}
