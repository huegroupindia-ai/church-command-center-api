<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvidenceController extends Controller
{
    public function index(Request $request)
    {
        $query = Evidence::query();
        if ($request->filled('checklist_item_id')) {
            $query->where('checklist_item_id', $request->query('checklist_item_id'));
        }

        return response()->json(['data' => $query->get()->map(fn ($e) => $this->format($e))]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'checklist_item_id' => ['required', 'integer'],
            'type' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $path = null;
        $fileName = 'evidence';
        $fileSize = 0;
        $mime = 'application/octet-stream';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('evidence', 'public');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mime = $file->getMimeType();
        }

        $evidence = Evidence::create([
            'checklist_item_id' => $data['checklist_item_id'],
            'user_id' => Auth::guard('api')->id() ?? 1,
            'type' => $data['type'] ?? 'photo',
            'file_path' => $path ?? '',
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'mime_type' => $mime,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json($this->format($evidence), 201);
    }

    public function destroy($id)
    {
        Evidence::findOrFail($id)->delete();

        return response()->json(['message' => 'Evidence deleted']);
    }

    protected function format(Evidence $e): array
    {
        return [
            'id' => $e->id,
            'checklist_item_id' => $e->checklist_item_id,
            'user_id' => $e->user_id,
            'type' => $e->type,
            'file_path' => $e->file_path,
            'file_name' => $e->file_name,
            'file_size' => $e->file_size,
            'mime_type' => $e->mime_type,
            'notes' => $e->notes,
            'created_at' => $e->created_at?->toIso8601String(),
            'updated_at' => $e->updated_at?->toIso8601String(),
        ];
    }
}
