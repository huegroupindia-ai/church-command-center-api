<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index()
    {
        return response()->json(['data' => []]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => $request->all()], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => ['id' => $id]]);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['data' => array_merge(['id' => $id], $request->all())]);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'Deleted']);
    }
}
