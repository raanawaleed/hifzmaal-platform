<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ActivityResource;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends ApiController
{
    /**
     * Who changed what in this family's shared data, and when — visible to
     * any member with family access, not just the owner. Transparency is
     * the point of an audit log on shared finances.
     */
    public function index(Request $request, Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $activities = Activity::where('log_name', 'financial')
            ->where('family_id', $family->id)
            ->with('causer')
            ->latest()
            ->paginate(min((int) $request->get('per_page', 25), 100));

        return ActivityResource::collection($activities);
    }
}
