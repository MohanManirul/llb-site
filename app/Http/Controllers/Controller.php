<?php

namespace App\Http\Controllers;

use App\Traits\HasFileUploadSupport;
use App\Traits\PaginationHelper;

abstract class Controller
{
    use HasFileUploadSupport, PaginationHelper;
}
