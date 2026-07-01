<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    public function ui(): View
    {
        return view('api.documentation');
    }

    public function spec(): JsonResponse
    {
        $spec = config('openapi');
        $spec['servers'] = [['url' => url('/api')]];

        return response()->json($spec);
    }
}
