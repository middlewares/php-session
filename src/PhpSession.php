<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Interop\Http\Middleware\DelegateInterface;

class PhpSession implements ServerMiddlewareInterface
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
     * Configure the session name.
     *
     * @param string $name
     *
     * @return self
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Configure the session id.
     *
     * @param string $id
     *
     * @return self
     */
    public function id($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if (session_status() === PHP_SESSION_DISABLED) {
            throw new RuntimeException('PHP sessions are disabled');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Failed to start the session: already started by PHP.');
        }

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

        session_start();

        $response = $delegate->process($request);

        if ((session_status() === PHP_SESSION_ACTIVE) && (session_name() === $name)) {
            session_write_close();
        }

        return $response;
    }
}
