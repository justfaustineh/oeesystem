<?php

namespace App\Imports;

use App\Models\Distributor;
use App\Models\Country;
use App\Models\ProductCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Collection;

class DistributorImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    private $countryCache = [];
    private $categoryCache = [];
    private $errors = [];
    private $rowNumber = 2; // Start from 2 (header is row 1)

    /**
     * Map row data to Distributor model
     */
    public function model(array $row)
    {
        $this->rowNumber++;

        try {
            // Get country
            $countryId = $this->resolveCountry($row);
            if (!$countryId) {
                $this->errors[] = [
                    'row' => $this->rowNumber,
                    'column' => 'country',
                    'value' => $row['country'] ?? 'N/A',
                    'message' => 'Country not found'
                ];
                return null;
            }

            // Prepare distributor data
            $data = [
                'country_id' => $countryId,
                'region_id' => null,
                'company_name' => trim($row['company_name'] ?? ''),
                'trade_name' => trim($row['trade_name'] ?? null),
                'registration_number' => trim($row['registration_number'] ?? null),
                'email' => trim($row['email'] ?? null),
                'phone' => trim($row['phone'] ?? null),
                'website' => trim($row['website'] ?? null),
                'address' => trim($row['address'] ?? null),
                'city' => trim($row['city'] ?? null),
                'latitude' => $this->parseCoordinate($row['latitude'] ?? null),
                'longitude' => $this->parseCoordinate($row['longitude'] ?? null),
                'type' => strtolower(trim($row['type'] ?? 'distributor')),
                'status' => strtolower(trim($row['status'] ?? 'active')),
                'is_featured' => $this->parseBoolean($row['is_featured'] ?? false),
                'contract_start' => $this->parseDate($row['contract_start_yyyy_mm_dd'] ?? null),
                'contract_end' => $this->parseDate($row['contract_end_yyyy_mm_dd'] ?? null),
                'notes' => trim($row['notes'] ?? null),
            ];

            // Validate required fields
            if (empty($data['company_name'])) {
                $this->errors[] = [
                    'row' => $this->rowNumber,
                    'column' => 'company_name',
                    'value' => 'N/A',
                    'message' => 'Company name is required'
                ];
                return null;
            }

            // Create distributor
            $distributor = Distributor::create($data);

            // Attach product categories if provided
            if (!empty($row['product_categories_comma_separated'])) {
                $this->attachCategories($distributor, $row['product_categories_comma_separated']);
            }

            return $distributor;
        } catch (\Exception $e) {
            $this->errors[] = [
                'row' => $this->rowNumber,
                'column' => 'general',
                'value' => 'N/A',
                'message' => $e->getMessage()
            ];
            return null;
        }
    }

    /**
     * Validation rules for each row
     */
    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'country' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'city' => 'nullable|string|max:100',
            'type' => 'nullable|in:distributor,dealer,stockist,agent',
            'status' => 'nullable|in:active,inactive,suspended',
            'is_featured' => 'nullable|in:yes,no,true,false,1,0',
        ];
    }

    /**
     * Skip failures (rows with validation errors)
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $attributes = $failure->attribute();
            $attribute_str = is_array($attributes) ? implode(', ', $attributes) : (string)$attributes;
            
            $errors_list = $failure->errors();
            $errors_str = is_array($errors_list) ? implode(', ', $errors_list) : (string)$errors_list;
            
            $this->errors[] = [
                'row' => $failure->row(),
                'column' => $attribute_str,
                'value' => 'See validation errors',
                'message' => $errors_str
            ];
        }
    }

    /**
     * Get all errors collected during import
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Resolve country by name or code
     */
    private function resolveCountry(array $row): ?int
    {
        $countryInput = trim($row['country'] ?? '');

        if (empty($countryInput)) {
            return null;
        }

        // Check cache first
        if (isset($this->countryCache[$countryInput])) {
            return $this->countryCache[$countryInput];
        }

        // Try to find by name or country code
        $country = Country::where('name', 'LIKE', "%{$countryInput}%")
            ->orWhere('country_code', strtoupper($countryInput))
            ->orWhere('flag_emoji', $countryInput)
            ->first();

        if ($country) {
            $this->countryCache[$countryInput] = $country->id;
            return $country->id;
        }

        $this->countryCache[$countryInput] = null;
        return null;
    }

    /**
     * Attach product categories to distributor
     */
    private function attachCategories(Distributor $distributor, string $categories): void
    {
        $categoryNames = array_map('trim', explode(',', $categories));
        $categoryIds = [];

        foreach ($categoryNames as $categoryName) {
            if (empty($categoryName)) {
                continue;
            }

            // Check cache
            if (isset($this->categoryCache[$categoryName])) {
                $categoryIds[] = $this->categoryCache[$categoryName];
                continue;
            }

            // Find category
            $category = ProductCategory::where('name', 'LIKE', "%{$categoryName}%")
                ->orWhere('slug', str_slug($categoryName))
                ->first();

            if ($category) {
                $this->categoryCache[$categoryName] = $category->id;
                $categoryIds[] = $category->id;
            }
        }

        if (!empty($categoryIds)) {
            $distributor->productCategories()->sync($categoryIds);
        }
    }

    /**
     * Parse boolean values
     */
    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string)$value));
        return in_array($value, ['yes', 'true', '1', 'y', 'on']);
    }

    /**
     * Parse date values (handles various formats)
     */
    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim((string)$value);

        try {
            // Try to parse as timestamp
            $date = \DateTime::createFromFormat('Y-m-d', $value);
            if ($date && $date->format('Y-m-d') === $value) {
                return $value;
            }

            // Try common date formats
            foreach (['d/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'] as $format) {
                $date = \DateTime::createFromFormat($format, $value);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            }

            // Try to parse as string (let PHP handle it)
            $timestamp = strtotime($value);
            if ($timestamp) {
                return date('Y-m-d', $timestamp);
            }
        } catch (\Exception $e) {
            // Return null if parsing fails
        }

        return null;
    }

    /**
     * Parse decimal coordinates
     */
    private function parseCoordinate($value): ?float
    {
        if (empty($value)) {
            return null;
        }

        $value = trim((string)$value);
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($float !== false) {
            return round($float, 7);
        }

        return null;
    }
}
