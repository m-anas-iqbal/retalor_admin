<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'usersCount' => User::count(),
            'adminsCount' => Admin::count(),
            'tokensCount' => ApiToken::count(),
            'recentUsers' => User::latest()->take(5)->get(),
        ]);
    }
}
