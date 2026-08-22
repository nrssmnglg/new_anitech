<?php

namespace App\Http\Requests\Farmer;

use App\Services\Farmer\FarmerInquiryService;
use Illuminate\Foundation\Http\FormRequest;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        app(FarmerInquiryService::class)->ensureDefaultCategories();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:query_categories,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
