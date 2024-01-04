<?php

namespace App\Web;

class Home extends BaseWeb
{
    public function index()
    {
        return $this->view("welcome_message");
    }
}
