<?php
declare(strict_types = 1);

namespace Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PhpSession implements MiddlewareInterface
{
    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $id;

    /**
     * @var array<string,bool|float|int|string>|null
     */
    private $sessionOptions;

    /**
     * @var array<string,bool|float|int|string>|null
     */
    private $cookieOptions;

    /**
     * @var int|null
     */
    private $regenerateIdInterval;

    /**
     * @var string|null
     */
    private $sessionIdExpiryKey;

    /**
     * Configure the session name.
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Configure the session id.
     */
    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set the session and cookie options.
     *
     * @param  array<int|bool|string|float> $options
     * @throws RuntimeException
     */
    public function options(array $options): self
    {
        self::checkSessionSettings($options);

        $this->sessionOptions = $options;
        $this->cookieOptions = [];

        static::moveKeys(
            ['lifetime', 'path', 'domain', 'secure', 'httponly', 'samesite'],
            $this->sessionOptions,
            $this->cookieOptions
        );

        return $this;
    }

    /**
     * @param string[]                            $keysToMove
     * @param array<string,bool|float|int|string> $source
     * @param array<string,bool|float|int|string> $target
     */
    private static function moveKeys(array $keysToMove, array &$source, array &$target): void
    {
        foreach ($keysToMove as $key) {
            if (array_key_exists($key, $source)) {
                $target[$key] = $source[$key];
                unset($source[$key]);
            }
        }
    }

    /**
     * Set the session id regenerate interval and id expiry key name.
     */
    public function regenerateId(int $interval, string $key = 'session-id-expires'): self
    {
        $this->regenerateIdInterval = $interval;

        $this->sessionIdExpiryKey = $key;

        return $this;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        self::checkSessionSettings($this->sessionOptions ?? []);
        self::checkSessionCanStart();

        // Session name
        $name = $this->name ?? $this->sessionOptions['name'] ?? session_name();
        session_name((string) $name);

        // Session ID
        $id = $this->id ?: self::readSessionCookie($request, (string) $name);
        if (!empty($id)) {
            session_id($id);
        }

        if ($this->sessionOptions === null) {
            session_start();
        } else {
            session_start($this->sessionOptions);
        }

        // Session ID regeneration
        self::runIdRegeneration($this->regenerateIdInterval, $this->sessionIdExpiryKey);

        $response = $handler->handle($request);

        if (session_status() === PHP_SESSION_ACTIVE && session_name() === $name) {
            session_write_close();
        }

        // If the session ID changed, write the session cookie
        if (session_id() !== $id) {
            $response = self::writeSessionCookie(
                $response,
                (string) session_name(),
                (string) session_id(),
                time(),
                array_merge(session_get_cookie_params(), $this->cookieOptions)
            );
        }

        return $response;
    }

    /**
     * Check PHP session settings for compatibility with PSR-7.
     *
     * @param  array<int|bool|string|float> $options
     * @throws RuntimeException
     */
    private static function checkSessionSettings(array $options): void
    {
        // See https://paul-m-jones.com/post/2016/04/12/psr-7-and-session-cookies
        $use_trans_sid = $options['use_trans_sid'] ?? ini_get('session.use_trans_sid');
        $use_cookies = $options['use_cookies'] ?? ini_get('session.use_cookies');
        $use_only_cookies = $options['use_only_cookies'] ?? ini_get('session.use_only_cookies');
        $cache_limiter = $options['cache_limiter'] ?? ini_get('session.cache_limiter');

        if ($use_trans_sid != false) {
            throw new RuntimeException('session.use_trans_sid must be false');
        }

        if ($use_cookies != false) {
            throw new RuntimeException('session.use_cookies must be false');
        }

        if ($use_only_cookies != true) {
            throw new RuntimeException('session.use_only_cookies must be true');
        }

        if ($cache_limiter !== '') {
            throw new RuntimeException('session.cache_limiter must be set to an empty string');
        }
    }

    /**
     * Checks whether the session can be started.
     *
     * @throws RuntimeException
     */
    private static function checkSessionCanStart(): void
    {
        if (session_status() === PHP_SESSION_DISABLED) {
            throw new RuntimeException('PHP sessions are disabled');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Failed to start the session: already started by PHP.');
        }
    }

    /**
     * Regenerate the session ID if it's needed.
     */
    private static function runIdRegeneration(?int $interval = null, ?string $key = null): void
    {
        if (empty($interval)) {
            return;
        }

        $expiry = time() + $interval;

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = $expiry;
        }

        if ($_SESSION[$key] < time() || $_SESSION[$key] > $expiry) {
            session_regenerate_id(true);

            $_SESSION[$key] = $expiry;
        }
    }

    /**
     * Attempt to read the session ID from the session cookie in a PSR-7 request.
     */
    private static function readSessionCookie(ServerRequestInterface $request, string $name): string
    {
        $cookies = $request->getCookieParams();

        return $cookies[$name] ?? '';
    }

    /**
     * Write a session cookie to the PSR-7 response.
     *
     * @param array<bool|int|string> $params
     */
    private static function writeSessionCookie(
        ResponseInterface $response,
        string $name,
        string $id,
        int $now,
        array $params
    ): ResponseInterface {
        $cookie = urlencode($name) . '=' . urlencode($id);

        // if omitted, the cookie will expire at end of the session (ie when the browser closes)
        if (!empty($params['lifetime'])) {
            // @phpstan-ignore-next-line
            $expires = gmdate('D, d M Y H:i:s T', $now + $params['lifetime']);
            $cookie .= "; Expires={$expires}; Max-Age={$params['lifetime']}";
        }

        if (!empty($params['domain'])) {
            $cookie .= "; Domain={$params['domain']}";
        }

        if (!empty($params['path'])) {
            $cookie .= "; Path={$params['path']}";
        }

        if (!empty($params['samesite']) && in_array($params['samesite'], ['None', 'Lax', 'Strict'])) {
            $cookie .= '; SameSite=' . $params['samesite'];
        }

        if (!empty($params['secure'])) {
            $cookie .= '; Secure';
        }

        if (!empty($params['httponly'])) {
            $cookie .= '; HttpOnly';
        }

        return $response->withAddedHeader('Set-Cookie', $cookie);
    }
}
