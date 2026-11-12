<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\IdentityDocument;
use App\Enums\TshirtSize;
use App\Models\TicketPrice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * The account holder's form plus every other person in the bundle: exactly 3 more for a Family
 * Bundle (4 in total) and exactly 1 more for a Couple Bundle (2 in total). The number comes from
 * the package in the database, never from the browser, and every member row is required.
 *
 * Every member gives their own medical information. A Family Bundle proves identity with one photo
 * of the family's Kartu Keluarga, so its members type no KTP/Passport number; Couple Bundle members
 * still do, and the payer uploads a KTP photo.
 */
class BundleRegistrationRequest extends RegistrationRequest
{
    /**
     * Normalize member phone and identity numbers.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $members = collect((array) $this->input('members', []))
            ->map(fn (mixed $member): mixed => is_array($member) ? [
                ...$member,
                'phone' => preg_replace('/[\s\-().]/', '', (string) ($member['phone'] ?? '')),
                'identity_number' => preg_replace('/\s/', '', (string) ($member['identity_number'] ?? '')),
            ] : $member)
            ->all();

        $this->merge(['members' => $members]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'members' => ['required', 'array', 'list', 'size:'.$this->otherMemberCount()],
            'members.*' => ['array'],
            'members.*.full_name' => ['required', 'string', 'max:100'],
            'members.*.phone' => ['nullable', 'string', 'regex:'.self::PHONE_PATTERN],
            'members.*.birth_date' => ['required', 'date', 'before:today', 'after:1920-01-01'],
            'members.*.gender' => ['required', Rule::enum(Gender::class)],
            ...$this->membersTypeIdentityNumber()
                ? ['members.*.identity_number' => ['required', 'string', 'regex:'.self::IDENTITY_PATTERN]]
                : [],
            'members.*.tshirt_size' => ['required', Rule::enum(TshirtSize::class)],
            'members.*.medical_history' => ['required', 'string', 'max:'.self::MEDICAL_TEXT_MAX],
            'members.*.drug_allergies' => ['required', 'string', 'max:'.self::MEDICAL_TEXT_MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $participantCount = $this->otherMemberCount() + 1;
        $wrongCount = "{$this->packageName()} harus diisi tepat {$participantCount} peserta: kamu dan {$this->otherMemberCount()} anggota lainnya.";

        return [
            ...parent::messages(),
            'members.required' => $wrongCount,
            'members.array' => $wrongCount,
            'members.list' => $wrongCount,
            'members.size' => $wrongCount,
            'members.*.phone.regex' => 'Format :attribute tidak valid. Gunakan format 08xxxxxxxxxx.',
            'members.*.identity_number.regex' => ':attribute harus terdiri dari 6–20 huruf atau angka.',
            'members.*.birth_date.before' => ':attribute harus sebelum hari ini.',
            'members.*.gender.required' => 'Pilih gender anggota.',
            'members.*.tshirt_size.required' => 'Pilih ukuran T-shirt anggota.',
            'members.*.medical_history.required' => 'Riwayat penyakit anggota wajib diisi. Tulis “Tidak Ada” jika tidak memiliki riwayat penyakit.',
            'members.*.drug_allergies.required' => 'Alergi obat anggota wajib diisi. Tulis “Tidak Ada” jika tidak memiliki alergi obat.',
            Enum::class => ':attribute yang dipilih tidak valid.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'members.*.full_name' => 'Nama anggota',
            'members.*.phone' => 'Nomor telepon anggota',
            'members.*.birth_date' => 'Tanggal lahir anggota',
            'members.*.gender' => 'Gender anggota',
            'members.*.identity_number' => 'KTP/Passport anggota',
            'members.*.tshirt_size' => 'Ukuran T-shirt anggota',
            'members.*.medical_history' => 'Riwayat penyakit anggota',
            'members.*.drug_allergies' => 'Alergi obat anggota',
        ];
    }

    /**
     * The validated members with their position in the bundle (the account holder is position 1).
     * Family Bundle members have no KTP/Passport number, even if the browser sent one.
     *
     * @return list<array{position: int, full_name: string, phone: ?string, birth_date: string, gender: string, identity_number: ?string, tshirt_size: string, medical_history: string, drug_allergies: string}>
     */
    public function members(): array
    {
        return collect($this->validated('members'))
            ->values()
            ->map(fn (array $member, int $index): array => [
                'position' => $index + 2,
                'full_name' => $member['full_name'],
                'phone' => filled($member['phone'] ?? null) ? $member['phone'] : null,
                'birth_date' => $member['birth_date'],
                'gender' => $member['gender'],
                'identity_number' => $this->membersTypeIdentityNumber() ? $member['identity_number'] : null,
                'tshirt_size' => $member['tshirt_size'],
                'medical_history' => $member['medical_history'],
                'drug_allergies' => $member['drug_allergies'],
            ])
            ->all();
    }

    public function package(): ?TicketPrice
    {
        return once(fn (): ?TicketPrice => TicketPrice::findBundle((string) $this->route('bundle')));
    }

    /**
     * Everyone in the bundle except the account holder.
     */
    private function otherMemberCount(): int
    {
        return max(1, ($this->package()?->max_participants ?? 2) - 1);
    }

    private function membersTypeIdentityNumber(): bool
    {
        return $this->identityDocument() === IdentityDocument::Ktp;
    }

    private function packageName(): string
    {
        return $this->package()?->name() ?? 'Paket ini';
    }
}
