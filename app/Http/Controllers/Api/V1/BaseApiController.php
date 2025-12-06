<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;

abstract class BaseApiController extends Controller
{
    use ApiResponse;
}

