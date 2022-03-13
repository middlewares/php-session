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
     * @var array|null
     */
    private $options;

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
     * Set the session options.
     *
     * @throws RuntimeException
     */
    public function options(array $options): self
    {
        self::checkSessionSettings($options);

        $this->options = $options;

        return $this;
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
        self::checkSessionSettings($this->options ?? []);
        self::checkSessionCanStart();

        // Session name
        $name = $this->name ?? $this->options['name'] ?? session_name();
        session_name($name);

        // Session ID
        $id = $this->id ?: self::readSessionCookie($request, $name);
        if (!empty($id)) {
            session_id($id);
        }

        if ($this->options === null) {
            session_start();
        } else {
            session_start($this->options);
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
                session_name(),
                session_id(),
                time(),
                session_get_cookie_params()
            );
        }

        return $response;
    }

    /**
     * Check PHP session settings for compatibility with PSR-7.
     *
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
    private static function runIdRegeneration(int $interval = null, string $key = null): void
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
