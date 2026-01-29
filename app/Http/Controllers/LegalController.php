<?php

namespace App\Http\Controllers;

class LegalController extends Controller
{
    public function dmca()
    {
        return view('page.legal.dmca');
    }

    public function removeContent()
    {
        return view('page.legal.removeContent');
    }
}
