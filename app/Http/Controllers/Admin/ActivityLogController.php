<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\View\View;

/** Admin görüntüleme logları — docs/ROUTES.md §6.11 (activity.read: yalnızca süper admin). */
final class ActivityLogController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.activity.index', [
            'logs' => AdminActivityLog::query()->with('user:id,username,ulid')->latest('id')->paginate(50),
        ]);
    }
}
