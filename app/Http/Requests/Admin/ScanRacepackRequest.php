<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * A racepack desk check: either a scanned QR payload or a typed ID (a registration code or one
 * participant's "UCCR-{BIB}"). Only the text is taken from the browser; everything else is looked
 * up in the database.
 */
class ScanRacepackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => Str::upper(trim((string) $this->input('code')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payload' => ['nullable', 'required_without:code', 'string', 'max:255'],
            'code' => ['nullable', 'required_without:payload', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payload.required_without' => 'Pindai QR code atau masukkan Registration ID.',
            'code.required_without' => 'Masukkan Registration ID peserta.',
            'max' => 'Invalid QR Code: data yang dipindai terlalu panjang.',
        ];
    }
}
