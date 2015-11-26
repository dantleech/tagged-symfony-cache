<?php

namespace DTL\TaggedHttpCache\Tests\Kernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Response;

class DummyKernel implements HttpKernelInterface
{
    public $response;

    public function __construct()
    {
        $this->response = new Response('Dummy');
    }

    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        return $this->response;
    }
}
