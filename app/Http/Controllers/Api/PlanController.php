<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success('Plans fetched.', [
            'plans' => Plan::query()
                ->where('is_active', true)
                ->orderBy('price')
                ->get(),
        ]);
    }
}
