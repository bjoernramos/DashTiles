<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class LocaleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // If user chose a locale, apply it early in the request
        $session = session();
        $chosen = (string) ($session->get('locale') ?? '');
        if ($chosen !== '') {
            $config = config('App');
            $supported = (array) ($config->supportedLocales ?? []);
            if (in_array($chosen, $supported, true)) {
                $request->setLocale($chosen);
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
