<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Division;
use App\Models\Upazila;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function divisions(): JsonResponse
    {
        $rows = Division::query()
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_bn']);

        return $this->success($rows, 'Divisions retrieved successfully.');
    }

    public function districts(Request $request): JsonResponse
    {
        $request->validate([
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
        ]);

        $rows = District::query()
            ->where('division_id', (int) $request->input('division_id'))
            ->orderBy('name_en')
            ->get(['id', 'division_id', 'name_en', 'name_bn']);

        return $this->success($rows, 'Districts retrieved successfully.');
    }

    public function upazilas(Request $request): JsonResponse
    {
        $request->validate([
            'district_id' => ['required', 'integer', 'exists:districts,id'],
        ]);

        $rows = Upazila::query()
            ->where('district_id', (int) $request->input('district_id'))
            ->orderBy('name_en')
            ->get(['id', 'district_id', 'name_en', 'name_bn']);

        return $this->success($rows, 'Upazilas retrieved successfully.');
    }
}
