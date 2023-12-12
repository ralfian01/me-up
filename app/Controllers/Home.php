<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        return "Welcome to website";
        // return view('welcome_message');
    }
}
