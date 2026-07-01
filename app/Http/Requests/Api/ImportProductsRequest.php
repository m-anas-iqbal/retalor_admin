<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimetypes:text/plain,text/csv,text/comma-separated-values,application/csv,application/vnd.ms-excel', 'max:5120'],
        ];
    }
}
