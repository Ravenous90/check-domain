<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CheckLogResource;
use App\Models\DomainCheck;
use Illuminate\Http\Request;

class CheckLogController extends Controller
{
    public function index(Request $request, DomainCheck $check)
    {
        $logs = $check->logs()
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 30), 100));

        return CheckLogResource::collection($logs);
    }
}
