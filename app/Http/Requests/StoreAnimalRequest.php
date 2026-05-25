<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id' => 'nullable|string|unique:animals,animal_id|max:50',
            'name' => 'required|string|max:255',
            'species' => 'required|string|max:255',
            'breed' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:Male,Female,male,female',
            'color_markings' => 'nullable|string',
            'current_weight' => 'nullable|numeric|min:0',
            'identification_photo' => 'nullable|file|image|max:2048',
            'baseline_temperature' => 'nullable|numeric|min:0|max:50',
            'normal_heart_rate' => 'nullable|integer|min:0|max:300',
            'owner_id' => 'nullable|exists:users,id',
            'device_id' => 'nullable|string|max:255',
            'documents' => 'nullable|array',
            'documents.*.file' => 'required_with:documents|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
            'documents.*.type' => 'required_with:documents|string|in:registration_proof,health_certificate,other',
            'documents.*.notes' => 'nullable|string|max:500',
        ];
    }
}
