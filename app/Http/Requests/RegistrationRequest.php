<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\TshirtSize;
use App\Support\IndonesiaRegions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates the participant form used to create and edit a registration.
 */
class RegistrationRequest extends FormRequest
{
    private const PHONE_PATTERN = '/^(\+62|62|0)8[0-9]{7,12}$/';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalize phone numbers before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/[\s\-().]/', '', (string) $this->input('phone')),
            'emergency_contact_phone' => preg_replace('/[\s\-().]/', '', (string) $this->input('emergency_contact_phone')),
            'identity_number' => preg_replace('/\s/', '', (string) $this->input('identity_number')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:'.self::PHONE_PATTERN],
            'birth_date' => ['required', 'date', 'before:today', 'after:1920-01-01'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'identity_number' => ['required', 'string', 'regex:/^[A-Za-z0-9]{6,20}$/'],
            'tshirt_size' => ['required', Rule::enum(TshirtSize::class)],
            'emergency_contact_name' => ['required', 'string', 'max:100'],
            'emergency_contact_phone' => ['required', 'string', 'regex:'.self::PHONE_PATTERN, 'different:phone'],
            'province' => ['required', 'string', Rule::in(IndonesiaRegions::provinces())],
            'city' => ['required', 'string', Rule::in(IndonesiaRegions::citiesOf($this->input('province')))],
            'address' => ['required', 'string', 'max:255'],
            'agreement' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            Enum::class => ':attribute yang dipilih tidak valid.',
            'in' => ':attribute yang dipilih tidak valid.',
            'phone.regex' => 'Format :attribute tidak valid. Gunakan format 08xxxxxxxxxx.',
            'emergency_contact_phone.regex' => 'Format :attribute tidak valid. Gunakan format 08xxxxxxxxxx.',
            'emergency_contact_phone.different' => 'Nomor kontak darurat harus berbeda dengan nomor telepon peserta.',
            'identity_number.regex' => ':attribute harus terdiri dari 6–20 huruf atau angka.',
            'birth_date.date' => ':attribute tidak valid.',
            'birth_date.before' => ':attribute harus sebelum hari ini.',
            'birth_date.after' => ':attribute tidak valid.',
            'gender.required' => 'Pilih gender.',
            'tshirt_size.required' => 'Pilih ukuran T-shirt.',
            'province.required' => 'Pilih provinsi.',
            'city.required' => 'Pilih kota atau kabupaten.',
            'agreement.accepted' => 'Kamu harus menyetujui Race Regulations dan Privacy Policy.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'Full Name',
            'phone' => 'Phone Number',
            'birth_date' => 'Date of Birth',
            'gender' => 'Gender',
            'identity_number' => 'KTP/Passport',
            'tshirt_size' => 'T-shirt Size',
            'emergency_contact_name' => 'Emergency Contact Name',
            'emergency_contact_phone' => 'Emergency Contact Phone',
            'province' => 'Province',
            'city' => 'City',
            'address' => 'Full Address',
        ];
    }
}
