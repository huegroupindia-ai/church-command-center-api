<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentMediaController extends Controller
{
    public function index(Request $request)
    {
        $query = IncidentMedia::query()->orderBy('created_at', 'desc');

        if ($request->filled('incident_id')) {
            $query->where('incident_id', $request->query('incident_id'));
        }

        $media = $query->get()->map(fn ($m) => $this->format($m));

        return response()->json(['data' => $media]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'incident_id' => ['required', 'integer'],
            'file' => ['required', 'file'],
            'type' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $path = $file->store('incident-media', 'public');

        $media = IncidentMedia::create([
            'incident_id' => $request->input('incident_id'),
            'type' => $request->input('type') ?? (str_starts_with($file->getMimeType() ?? '', 'video') ? 'video' : 'image'),
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json($this->format($media), 201);
    }

    public function show($id)
    {
        $media = IncidentMedia::findOrFail($id);

        return response()->json($this->format($media));
    }

    public function update(Request $request, $id)
    {
        $media = IncidentMedia::findOrFail($id);
        $request->validate([
            'type' => ['sometimes', 'string'],
            'file_name' => ['sometimes', 'string'],
        ]);
        $media->update($request->only(['type', 'file_name']));

        return response()->json($this->format($media));
    }

    public function destroy($id)
    {
        IncidentMedia::findOrFail($id)->delete();

        return response()->json(['message' => 'Media deleted']);
    }

    protected function format(IncidentMedia $m): array
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
