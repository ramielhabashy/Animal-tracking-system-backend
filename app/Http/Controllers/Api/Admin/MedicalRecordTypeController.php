<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecordType;
use App\Models\VaccinationType;
use App\Models\VaccinationSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MedicalRecordTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = MedicalRecordType::orderBy('name')->get();
        return response()->json(['data' => $types]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:medical_record_types,slug',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $type = MedicalRecordType::create($validated);
        return response()->json(['data' => $type, 'message' => 'Medical record type created'], 201);
    }

    public function update(Request $request, MedicalRecordType $medicalRecordType): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:50|unique:medical_record_types,slug,' . $medicalRecordType->id,
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $medicalRecordType->update($validated);
        return response()->json(['data' => $medicalRecordType, 'message' => 'Medical record type updated']);
    }

    public function destroy(MedicalRecordType $medicalRecordType): JsonResponse
    {
        $medicalRecordType->delete();
        return response()->json(['message' => 'Medical record type deleted']);
    }

    // Vaccination Types CRUD

    public function vaccinationTypes(): JsonResponse
    {
        $types = VaccinationType::active()->orderBy('name')->get();
        return response()->json(['data' => $types]);
    }

    public function allVaccinationTypes(): JsonResponse
    {
        $types = VaccinationType::orderBy('name')->get();
        return response()->json(['data' => $types]);
    }

    public function storeVaccinationType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:vaccination_types,slug',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $type = VaccinationType::create($validated);
        return response()->json(['data' => $type, 'message' => 'Vaccination type created'], 201);
    }

    public function updateVaccinationType(Request $request, VaccinationType $vaccinationType): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:50|unique:vaccination_types,slug,' . $vaccinationType->id,
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $vaccinationType->update($validated);
        return response()->json(['data' => $vaccinationType, 'message' => 'Vaccination type updated']);
    }

    public function destroyVaccinationType(VaccinationType $vaccinationType): JsonResponse
    {
        $inUse = VaccinationSchedule::where('vaccination_type', $vaccinationType->slug)->exists();
        if ($inUse) {
            return response()->json(['message' => 'Cannot delete: vaccination type is in use.'], 409);
        }
        $vaccinationType->delete();
        return response()->json(['message' => 'Vaccination type deleted']);
    }
}
