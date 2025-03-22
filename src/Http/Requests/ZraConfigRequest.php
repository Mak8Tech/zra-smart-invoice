<?php

namespace Mak8Tech\ZraSmartInvoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ZraConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tpin' => ['required', 'string', 'size:10'],
            'branch_id' => ['required', 'string', 'size:3'],
            'device_serial' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tpin.size' => 'TPIN must be exactly 10 characters.',
            'branch_id.size' => 'Branch ID must be exactly 3 characters.',
            'device_serial.max' => 'Device Serial Number cannot exceed 100 characters.',
        ];
    }
}
