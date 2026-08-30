<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();
        $query = Donation::where('church_id', $user->church_id)->with('donor:id,name,email');

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($from = $request->input('from')) {
            $query->where('donated_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->where('donated_at', '<=', $to);
        }

        $donations = $query->orderBy('donated_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($donations);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'nullable|in:tithe,offering,building_fund,missions,benevolence,youth,other',
            'method' => 'nullable|in:cash,check,card,bank_transfer,online,other',
            'donor_name' => 'nullable|string|max:255',
            'donor_email' => 'nullable|email',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'recurring_frequency' => 'nullable|in:weekly,biweekly,monthly',
            'donated_at' => 'nullable|date',
        ]);

        $donation = Donation::create([
            'church_id' => $user->church_id,
            'donor_id' => $user->id,
            'amount' => $validated['amount'],
            'type' => $validated['type'] ?? 'tithe',
            'method' => $validated['method'] ?? 'cash',
            'donor_name' => $validated['donor_name'] ?? $user->name,
            'donor_email' => $validated['donor_email'] ?? $user->email,
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_recurring' => $validated['is_recurring'] ?? false,
            'recurring_frequency' => $validated['recurring_frequency'] ?? null,
            'donated_at' => $validated['donated_at'] ?? now(),
        ]);

        return response()->json(['data' => $donation->load('donor:id,name,email')], 201);
    }

    public function show($id)
    {
        $user = Auth::guard('api')->user();
        $donation = Donation::where('church_id', $user->church_id)
            ->with('donor:id,name,email')
            ->findOrFail($id);
        return response()->json(['data' => $donation]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::guard('api')->user();
        $donation = Donation::where('church_id', $user->church_id)->findOrFail($id);
        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'type' => 'sometimes|in:tithe,offering,building_fund,missions,benevolence,youth,other',
            'method' => 'sometimes|in:cash,check,card,bank_transfer,online,other',
            'notes' => 'nullable|string',
        ]);
        $donation->update($validated);
        return response()->json(['data' => $donation->load('donor:id,name,email')]);
    }

    public function destroy($id)
    {
        $user = Auth::guard('api')->user();
        $donation = Donation::where('church_id', $user->church_id)->findOrFail($id);
        $donation->delete();
        return response()->json(['message' => 'Donation deleted']);
    }

    public function summary(Request $request)
    {
        $user = Auth::guard('api')->user();
        $churchId = $user->church_id;

        $totalThisMonth = Donation::where('church_id', $churchId)
            ->whereMonth('donated_at', now()->month)
            ->whereYear('donated_at', now()->year)
            ->sum('amount');

        $totalThisYear = Donation::where('church_id', $churchId)
            ->whereYear('donated_at', now()->year)
            ->sum('amount');

        $byType = Donation::where('church_id', $churchId)
            ->whereMonth('donated_at', now()->month)
            ->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->get();

        $recentCount = Donation::where('church_id', $churchId)
            ->whereMonth('donated_at', now()->month)
            ->count();

        return response()->json([
            'total_this_month' => $totalThisMonth,
            'total_this_year' => $totalThisYear,
            'by_type' => $byType,
            'donation_count' => $recentCount,
        ]);
    }
}
