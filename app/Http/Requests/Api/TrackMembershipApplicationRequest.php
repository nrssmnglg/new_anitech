<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TrackMembershipApplicationRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_no' => ['required', 'string', 'max:50'],
            'birth_date' => ['required', 'date'],
        ];
    }
}
