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
     */
    public function options(array $options): self
    {
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
        self::checkSessionCanStart();

        //Session name
        $name = $this->name ?: session_name();
        session_name($name);

        //Session id
        $id = $this->id;

        if (empty($id)) {
            $cookies = $request->getCookieParams();

            if (!empty($cookies[$name])) {
                $id = $cookies[$name];
            }
        }

        if (!empty($id)) {
            session_id($id);
        }

        if ($this->options === null) {
            session_start();
        } else {
            session_start($this->options);
        }

        // Session Id regeneration
        self::runIdRegeneration($this->regenerateIdInterval, $this->sessionIdExpiryKey);

        $response = $handler->handle($request);

        if ((session_status() === PHP_SESSION_ACTIVE) && (session_name() === $name)) {
            session_write_close();
        }

        return $response;
    }

    /**
     * Checks whether the session can be started.
     *
     * @throws RuntimeException
     */
    private static function checkSessionCanStart()
    {
        if (session_status() === PHP_SESSION_DISABLED) {
            throw new RuntimeException('PHP sessions are disabled');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Failed to start the session: already started by PHP.');
        }
    }

    /**
     * Regenerate the session id if it's needed
     */
    private static function runIdRegeneration(int $interval = null, string $key = null)
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
}
