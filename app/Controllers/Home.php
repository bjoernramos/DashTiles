<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $basePath = rtrim((string) (getenv('toolpages.basePath') ?: '/'), '/');
        return view('home', [
            'basePath' => $basePath,
        ]);
    }

    public function health()
    {
        return $this->response->setJSON([
            'status' => 'ok',
            'time' => date(DATE_ATOM),
        ]);
    }
}
