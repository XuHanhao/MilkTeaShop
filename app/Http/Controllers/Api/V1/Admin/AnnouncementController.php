<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends BaseApiController
{
    public function publicIndex(Request $request): JsonResponse
    {
        $audience = $request->user()?->type ?? 'customer';

        $announcements = Announcement::query()
            ->where('status', 'published')
            ->where(function ($query) use ($audience) {
                $query->where('target_audience', 'all')
                    ->orWhere('target_audience', $audience);
            })
            ->where(function ($query) {
                $now = now();
                $query->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($query) {
                $now = now();
                $query->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->latest()
            ->paginate(10);

        return $this->success($announcements);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Announcement::query();
        
        if ($request->has('status') && $request->string('status')) {
            $query->where('status', $request->string('status'));
        }
        
        $announcements = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success($announcements);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'target_audience' => ['required', Rule::in(['all', 'customer', 'staff', 'manager'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ]);

        $announcement = Announcement::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return $this->success($announcement, 'Announcement created successfully');
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'target_audience' => ['sometimes', Rule::in(['all', 'customer', 'staff', 'manager'])],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ]);

        $announcement->fill($validated)->save();

        return $this->success($announcement, 'Announcement updated successfully');
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return $this->success(null, 'Announcement deleted successfully');
    }
}

