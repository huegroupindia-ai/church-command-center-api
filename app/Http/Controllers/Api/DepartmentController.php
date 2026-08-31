<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::withCount(['users', 'equipment', 'incidents'])
            ->orderBy('name', 'asc');

        if ($request->filled('church_id')) {
            $query->where('church_id', $request->query('church_id'));
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'head' => 'nullable|integer|exists:users,id',
            'is_active' => 'boolean',
            'church_id' => 'nullable|integer|exists:churches,id',
        ]);

        $data['created_by'] = Auth::guard('api')->id() ?? 1;
        $data['church_id'] = $data['church_id'] ?? 1;

        $department = Department::create($data);

        return response()->json($department, 201);
    }

    public function show($id)
    {
        $department = Department::withCount(['users', 'equipment', 'incidents'])
            ->with(['leader', 'checklistTemplates'])
            ->findOrFail($id);

        return response()->json($department);
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'head' => 'nullable|integer|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $department->update($data);

        return response()->json($department);
    }

    public function destroy($id)
    {
        Department::findOrFail($id)->delete();

        return response()->json(['message' => 'Department deleted']);
    }
}
