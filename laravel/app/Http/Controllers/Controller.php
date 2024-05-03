<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected $max_records_in_query = 100;
}
