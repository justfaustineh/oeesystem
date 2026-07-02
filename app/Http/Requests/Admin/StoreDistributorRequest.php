<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDistributorRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('distributors.create');
    }

    public function rules()
    {
        return [
            'company_name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'country_id' => 'required|exists:countries,id',
            'region_id' => 'nullable|exists:regions,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'type' => 'nullable|in:distributor,dealer,stockist,agent',
            'status' => 'nullable|in:active,inactive,suspended',
            'is_featured' => 'nullable|boolean',
            'contract_start' => 'nullable|date_format:Y-m-d',
            'contract_end' => 'nullable|date_format:Y-m-d',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:product_categories,id',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'company_name.required' => 'Company name is required.',
            'country_id.required' => 'Country is required.',
            'country_id.exists' => 'Selected country does not exist.',
            'email.email' => 'Please provide a valid email address.',
            'website.url' => 'Please provide a valid website URL.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'logo.image' => 'The logo must be an image file.',
            'logo.max' => 'Logo size must not exceed 2MB.',
            'contract_start.date_format' => 'Contract start date must be in YYYY-MM-DD format.',
            'contract_end.date_format' => 'Contract end date must be in YYYY-MM-DD format.',
        ];
    }
}
