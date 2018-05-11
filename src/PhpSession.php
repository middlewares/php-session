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
    private $lifetime;

    /**
     * @var string
     */
    private $lifetimeSessionKey = 'session-lifetime';


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
     * Set the session lifetime.
     */
    public function lifetime(int $lifetime): self
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    /**
     * Set the session lifetime key used in $_SESSION.
     */
    public function lifetimeSessionKey(string $lifetimeSessionKey): self
    {
        $this->lifetimeSessionKey = $lifetimeSessionKey;

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

        // Session lifetime
        $lifetime = $this->lifetime;

        if (!empty($lifetime)) {
            $key = $this->lifetimeSessionKey;

            if (!isset($_SESSION[$key])) {
                $_SESSION[$key] = time() + $lifetime;
            }

            if ($_SESSION[$key] < time()) {
                session_regenerate_id(true);

                $_SESSION[$key] = time() + $lifetime;
            }
        }

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
}
