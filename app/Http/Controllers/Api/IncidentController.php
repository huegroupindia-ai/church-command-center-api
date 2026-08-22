<?php

namespace App\Http\Controllers\Api;

use App\Events\IncidentUpdated;
use App\Events\DashboardRefresh;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $query = Incident::query()->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->query('severity'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }

        $incidents = $query->get()->map(fn ($i) => $this->format($i));

        return response()->json(['data' => $incidents]);
    }

    public function store(Request $request)
    {
        $data = $this->validateIncident($request);
        $data['reported_by'] = Auth::guard('api')->id() ?? 1;
        $data['church_id'] = $data['church_id'] ?? 1;

        $incident = Incident::create($data);

        broadcast(new IncidentUpdated([
            'incident_id' => $incident->id,
            'title' => $incident->title,
            'severity' => $incident->severity ?? 'low',
            'status' => $incident->status ?? 'open',
            'reported_by' => Auth::guard('api')->user()?->name ?? '',
        ]));
        broadcast(new DashboardRefresh(reason: 'incident_created'));

        return response()->json($this->format($incident), 201);
    }

    public function show($id)
    {
        $incident = Incident::findOrFail($id);

        return response()->json($this->format($incident));
    }

    public function update(Request $request, $id)
    {
        $incident = Incident::findOrFail($id);
        $data = $this->validateIncident($request, false);
        $incident->update($data);

        broadcast(new IncidentUpdated([
            'incident_id' => $incident->id,
            'title' => $incident->title,
            'severity' => $incident->severity ?? 'low',
            'status' => $incident->status ?? 'open',
            'reported_by' => Auth::guard('api')->user()?->name ?? '',
        ]));
        broadcast(new DashboardRefresh(reason: 'incident_updated'));

        return response()->json($this->format($incident));
    }

    public function destroy($id)
    {
        Incident::findOrFail($id)->delete();

        return response()->json(['message' => 'Incident deleted']);
    }

    public function updateStatus(Request $request, $id)
    {
        $incident = Incident::findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'comments' => ['nullable', 'string'],
        ]);

        $data = ['status' => $validated['status']];
        if ($validated['status'] === 'resolved') {
            $data['resolved_at'] = now();
            $data['resolved_by'] = Auth::guard('api')->id() ?? 1;
            if (!empty($validated['comments'])) {
                $data['resolution_notes'] = $validated['comments'];
            }
        } elseif (!empty($validated['comments'])) {
            $data['resolution_notes'] = $validated['comments'];
        }

        $incident->update($data);

        return response()->json($this->format($incident));
    }

    public function assign(Request $request, $id)
    {
        $incident = Incident::findOrFail($id);
        $request->validate(['assigned_to' => ['required', 'integer']]);
        $incident->update(['assigned_to' => $request->input('assigned_to')]);

        return response()->json($this->format($incident));
    }

    public function addMedia(Request $request, $id)
    {
        $incident = Incident::findOrFail($id);
        $request->validate(['file' => ['required', 'file']]);

        $file = $request->file('file');
        $path = $file->store('incident-media', 'public');

        $media = IncidentMedia::create([
            'incident_id' => $incident->id,
            'type' => str_starts_with($file->getMimeType() ?? '', 'video') ? 'video' : 'image',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json($this->formatMedia($media), 201);
    }

    public function deleteMedia(Request $request, $id, $mediaId)
    {
        $incident = Incident::findOrFail($id);
        $media = IncidentMedia::where('incident_id', $incident->id)->findOrFail($mediaId);
        $media->delete();

        return response()->json(['message' => 'Media deleted']);
    }

    public function timeline($id)
    {
        Incident::findOrFail($id);

        return response()->json(['data' => []]);
    }

    protected function validateIncident(Request $request, bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return $request->validate([
            'church_id' => ['nullable', 'integer'],
            'service_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'title' => [$rule, 'string', 'max:255'],
            'description' => [$rule, 'string'],
            'type' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer'],
            'resolved_by' => ['nullable', 'integer'],
            'resolved_at' => ['nullable', 'date'],
            'resolution_notes' => ['nullable', 'string'],
        ]);
    }

    protected function format(Incident $i): array
    {
        return [
            'id' => $i->id,
            'church_id' => $i->church_id ?? 1,
            'service_id' => $i->service_id,
            'reported_by' => $i->reported_by ?? 1,
            'department_id' => $i->department_id,
            'title' => $i->title,
            'description' => $i->description,
            'type' => $i->type ?? 'general',
            'severity' => $i->severity ?? 'low',
            'status' => $i->status ?? 'open',
            'assigned_to' => $i->assigned_to,
            'resolved_by' => $i->resolved_by,
            'resolved_at' => $i->resolved_at?->toIso8601String(),
            'resolution_notes' => $i->resolution_notes,
            'created_at' => $i->created_at?->toIso8601String(),
            'updated_at' => $i->updated_at?->toIso8601String(),
            'reporter' => $i->reporter ? [
                'id' => $i->reporter->id,
                'name' => $i->reporter->name,
            ] : null,
            'department' => $i->department ? [
                'id' => $i->department->id,
                'name' => $i->department->name,
            ] : null,
            'media' => $i->media->map(fn ($m) => $this->formatMedia($m))->all(),
        ];
    }

    protected function formatMedia(IncidentMedia $m): array
    {
        return [
            'id' => $m->id,
            'incident_id' => $m->incident_id,
            'type' => $m->type,
            'file_path' => $m->file_path,
            'file_name' => $m->file_name,
            'file_size' => $m->file_size,
            'mime_type' => $m->mime_type,
            'created_at' => $m->created_at?->toIso8601String(),
        ];
    }
}
