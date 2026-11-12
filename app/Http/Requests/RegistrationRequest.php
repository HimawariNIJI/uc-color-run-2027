<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\IdentityDocument;
use App\Enums\TshirtSize;
use App\Models\TicketPrice;
use App\Support\IdentityPhoto;
use App\Support\IndonesiaRegions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates the participant form used to create and edit a registration. The account holder is
 * the payer (Full Nama (Nama Pembayar)) and proves identity with a photo of their KTP, or of the
 * family's Kartu Keluarga for a Family Bundle (see IdentityDocument).
 */
class RegistrationRequest extends FormRequest
{
    protected const PHONE_PATTERN = '/^(\+62|62|0)8[0-9]{7,12}$/';

    protected const IDENTITY_PATTERN = '/^[A-Za-z0-9]{6,20}$/';

    /**
     * Longest Riwayat Penyakit or Alergi Obat answer.
     */
    protected const MEDICAL_TEXT_MAX = 1000;

    private ?IdentityDocument $identityDocument = null;

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
            $this->identityDocument()->field() => IdentityPhoto::rules(required: ! $this->keepsSavedIdentityPhoto()),
            'tshirt_size' => ['required', Rule::enum(TshirtSize::class)],
            'emergency_contact_name' => ['required', 'string', 'max:100'],
            'emergency_contact_phone' => ['required', 'string', 'regex:'.self::PHONE_PATTERN, 'different:phone'],
            'medical_history' => ['required', 'string', 'max:'.self::MEDICAL_TEXT_MAX],
            'drug_allergies' => ['required', 'string', 'max:'.self::MEDICAL_TEXT_MAX],
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
            'medical_history.required' => 'Riwayat Penyakit wajib diisi. Tulis “Tidak Ada” jika tidak memiliki riwayat penyakit.',
            'drug_allergies.required' => 'Alergi Obat wajib diisi. Tulis “Tidak Ada” jika tidak memiliki alergi obat.',
            ...IdentityPhoto::messages($this->identityDocument()),
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
            'full_name' => 'Full Nama (Nama Pembayar)',
            'phone' => 'Phone Number',
            'birth_date' => 'Date of Birth',
            'gender' => 'Gender',
            $this->identityDocument()->field() => $this->identityDocument()->label(),
            'tshirt_size' => 'T-shirt Size',
            'emergency_contact_name' => 'Emergency Contact Name',
            'emergency_contact_phone' => 'Emergency Contact Phone',
            'medical_history' => 'Riwayat Penyakit',
            'drug_allergies' => 'Alergi Obat',
            'province' => 'Province',
            'city' => 'City',
            'address' => 'Full Address',
        ];
    }

    /**
     * The registration fields to save. The identity photo is stored separately, see IdentityPhoto.
     *
     * @return array<string, mixed>
     */
    public function participant(): array
    {
        return $this->safe()->except([
            'agreement',
            'members',
            ...array_map(fn (IdentityDocument $document): string => $document->field(), IdentityDocument::cases()),
        ]);
    }

    /**
     * The KTP, or the Kartu Keluarga when registering or editing a Family Bundle.
     */
    public function identityDocument(): IdentityDocument
    {
        return $this->identityDocument ??= $this->isEditing()
            ? $this->user()?->registration?->identityDocument() ?? IdentityDocument::Ktp
            : IdentityDocument::forPackage($this->package());
    }

    /**
     * The validated identity photo, or null when an edit keeps the saved one.
     */
    public function identityPhoto(): ?UploadedFile
    {
        return $this->validated($this->identityDocument()->field());
    }

    /**
     * The package being registered for. Individual packages never use a Kartu Keluarga, so only
     * the bundle form needs to say which one it is.
     */
    public function package(): ?TicketPrice
    {
        return null;
    }

    protected function isEditing(): bool
    {
        return $this->routeIs('my-registration.update');
    }

    /**
     * When editing, a registration that already has its identity photo keeps it unless a new one
     * is chosen. Registrations made before the photo field existed must add one.
     */
    protected function keepsSavedIdentityPhoto(): bool
    {
        return $this->isEditing() && $this->user()?->registration?->hasIdentityPhoto() === true;
    }
}
