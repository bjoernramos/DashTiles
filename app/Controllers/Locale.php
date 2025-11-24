<?php

namespace App\Controllers;

class Locale extends BaseController
{
    /**
     * Set the UI locale and persist it in the session.
     */
    public function set(string $locale)
    {
        $config = config('App');
        $supported = (array) ($config->supportedLocales ?? []);
        if (! in_array($locale, $supported, true)) {
            // Fallback to default if invalid
            $locale = $config->defaultLocale ?? 'de';
        }

        $session = session();
        $session->set('locale', $locale);

        // Also apply to current request for immediate effect
        $this->request->setLocale($locale);

        $referer = (string) ($this->request->getServer('HTTP_REFERER') ?? '');
        if ($referer !== '') {
            return redirect()->to($referer);
        }
        return redirect()->to('/');
    }
}
