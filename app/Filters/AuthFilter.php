<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not modify the request or response in
     * any way. Instead, it should call $handler->handle() to continue
     * execution of the request.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Jika pengguna TIDAK login
        if (! session()->get('logged_in')) {
            // Simpan URL yang diminta agar bisa redirect kembali setelah login
            session()->setFlashdata('redirect_url', current_url());

            // Redirect ke halaman login
            return redirect()->to(base_url('login'));
        }
    }

    /**
     * We aren't doing anything here, so it's just a pass-through.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing for now
    }
}