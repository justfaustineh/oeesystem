<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportDistributorsRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('distributors.create');
    }

    public function rules()
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // 5MB max
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'Please select a file to import.',
            'file.file' => 'The uploaded item must be a file.',
            'file.mimes' => 'Only Excel (.xlsx, .xls) and CSV files are allowed.',
            'file.max' => 'File size must not exceed 5MB.',
        ];
    }
}
