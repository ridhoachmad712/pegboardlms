<?php

namespace App\Http\Controllers;

use App\Services\StudentActivity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentActivityController extends Controller
{
    public function index(Request $request, StudentActivity $activity): View
    {
        $data = $activity->forStudent($request->user());
        $filter = in_array($request->query('filter'), ['all', 'tasks', 'attendance', 'updates'], true)
            ? $request->query('filter')
            : 'all';

        $actions = $data['actions']->when(
            $filter === 'tasks',
            fn ($items) => $items->whereIn('kind', ['assignment', 'quiz', 'revision'])
        )->when(
            $filter === 'attendance',
            fn ($items) => $items->where('kind', 'attendance')
        );
        $updates = $filter === 'all' || $filter === 'updates' ? $data['updates'] : collect();

        return view('activities.index', compact('actions', 'updates', 'filter', 'data'));
    }
}
