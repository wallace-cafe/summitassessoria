<?php

namespace App\Controllers;

class Home extends BaseController
{
  public function index()
  {
    // fazendo um teste de Deploy automático
    return view('home');
  }
}
