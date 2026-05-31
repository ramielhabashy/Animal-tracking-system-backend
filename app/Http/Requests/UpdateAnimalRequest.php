<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'species' => 'sometimes|in:Camel,Goat,Sheep,Cow,Dog',
            'breed' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'sometimes|in:Male,Female',
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
            'delete_document_ids' => 'nullable|array',
            'delete_document_ids.*' => 'exists:animal_documents,id',
        ];
    }
}
