<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Usecase\Admin\SidebarMenuUsecase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected SidebarMenuUsecase $sidebarMenuUsecase
    ) {}

    public function index(): View|Response
    {
        $modules = $this->sidebarMenuUsecase->getDashboardModules(
            accessType: (int) Auth::user()->access_type
        );

        $allowedRoutes = [
            'admin.users.index',
            'admin.log_book.index',
        ];

        $modules = collect($modules['data'] ?? [])
            ->filter(fn ($menu) => in_array($menu->route_name, $allowedRoutes, true))
            ->values()
            ->all();

        return view('_admin.dashboard', [
            'modules' => $modules,
        ]);
    }
}
