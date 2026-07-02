<?php

namespace App\Exports;

use App\Models\Country;
use App\Models\ProductCategory;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DistributorTemplateExport implements FromArray, WithHeadings, WithStyles
{
    /**
     * Get template data with sample rows
     */
    public function array(): array
    {
        return [
            [
                'ABC Distributors Ltd',
                'ABC Trade',
                'Kenya',
                'abc@example.com',
                '+254701234567',
                'https://www.abcdist.com',
                'Nairobi',
                '123 Business Street',
                '-1.286389',
                '36.817223',
                'distributor',
                'active',
                'yes',
                '2024-01-01',
                '2026-01-01',
                'Electronics, Solar Equipment',
                'ABC123456',
                'Quality distributor with wide network'
            ],
            [
                'XYZ Traders',
                'XYZ Retail',
                'Uganda',
                'xyz@example.com',
                '+256701234567',
                'https://www.xyztraders.com',
                'Kampala',
                '456 Trade Avenue',
                '0.347596',
                '32.585192',
                'dealer',
                'active',
                'no',
                '2023-06-01',
                '2025-12-31',
                'Electronics',
                'XYZ654321',
                'Established dealer in central region'
            ],
        ];
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return [
            'Company Name *',
            'Trade Name',
            'Country *',
            'Email',
            'Phone',
            'Website',
            'City',
            'Address',
            'Latitude',
            'Longitude',
            'Type',
            'Status',
            'Featured',
            'Contract Start (YYYY-MM-DD)',
            'Contract End (YYYY-MM-DD)',
            'Product Categories (comma-separated)',
            'Registration Number',
            'Notes',
        ];
    }

    /**
     * Style the spreadsheet
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:R1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0D6E63'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(12);
        $sheet->getColumnDimension('L')->setWidth(12);
        $sheet->getColumnDimension('M')->setWidth(10);
        $sheet->getColumnDimension('N')->setWidth(18);
        $sheet->getColumnDimension('O')->setWidth(18);
        $sheet->getColumnDimension('P')->setWidth(25);
        $sheet->getColumnDimension('Q')->setWidth(15);
        $sheet->getColumnDimension('R')->setWidth(20);

        // Format data rows
        $sheet->getStyle('A2:R3')->getAlignment()->setWrapText(true);

        // Add instructions sheet
        $instructionSheet = $sheet->getParent()->createSheet();
        $instructionSheet->setTitle('Instructions');
        $instructionSheet->getColumnDimension('A')->setWidth(80);

        $instructions = [
            ['BULK DISTRIBUTOR IMPORT - TEMPLATE INSTRUCTIONS'],
            [''],
            ['REQUIRED FIELDS:'],
            ['• Company Name - Name of the distributor company'],
            ['• Country - Country name (e.g., Kenya, Uganda, Tanzania, Rwanda, Burundi, DRC, South Sudan)'],
            [''],
            ['OPTIONAL FIELDS:'],
            ['• Trade Name - Alternate business name'],
            ['• Email - Contact email address'],
            ['• Phone - Contact phone number'],
            ['• Website - Company website URL'],
            ['• City - City location'],
            ['• Address - Physical address'],
            ['• Latitude - Decimal latitude coordinate'],
            ['• Longitude - Decimal longitude coordinate'],
            ['• Type - One of: distributor, dealer, stockist, agent (default: distributor)'],
            ['• Status - One of: active, inactive, suspended (default: active)'],
            ['• Featured - yes/no or true/false (default: no)'],
            ['• Contract Start - Date in YYYY-MM-DD format'],
            ['• Contract End - Date in YYYY-MM-DD format'],
            ['• Product Categories - Comma-separated category names (e.g., Electronics, Solar Equipment, Batteries)'],
            ['• Registration Number - Official registration number'],
            ['• Notes - Additional notes or remarks'],
            [''],
            ['NOTES:'],
            ['1. Ensure all country names match the system database exactly'],
            ['2. Date format must be YYYY-MM-DD (e.g., 2024-01-15)'],
            ['3. Latitude/Longitude should be decimal numbers (-90 to 90 for lat, -180 to 180 for long)'],
            ['4. Product categories must exist in the system'],
            ['5. Duplicate company names will be created as separate records'],
            ['6. Invalid rows will be skipped and reported after import'],
            ['7. Use commas to separate multiple product categories'],
            ['8. Email addresses must be valid email format'],
            [''],
            ['SAMPLE DATA:'],
            ['See "Sheet1" for example entries'],
        ];

        foreach ($instructions as $row => $data) {
            $instructionSheet->setCellValue('A' . ($row + 1), $data[0]);
        }

        $instructionSheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0D6E63']],
        ]);

        return [];
    }
}
