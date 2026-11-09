<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\TshirtSize;
use App\Models\TicketPrice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * The account holder's form plus the other people in a Family Bundle (up to 3 more, 4 in total)
 * or a Couple Bundle (exactly 1 more). The number of members comes from the package in the
 * database, never from the browser. Empty optional member rows are ignored.
 */
class BundleRegistrationRequest extends RegistrationRequest
{
    /**
     * Normalize member phone and identity numbers, and drop member rows left completely empty.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $members = collect((array) $this->input('members', []))
            ->filter(fn (mixed $member): bool => is_array($member) && collect($member)->contains(fn (mixed $value): bool => filled($value)))
            ->map(fn (array $member): array => [
                ...$member,
                'phone' => preg_replace('/[\s\-().]/', '', (string) ($member['phone'] ?? '')),
                'identity_number' => preg_replace('/\s/', '', (string) ($member['identity_number'] ?? '')),
            ])
            ->values()
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
        $otherMembers = max(1, ($this->package()?->max_participants ?? 2) - 1);

        return [
            ...parent::rules(),
            'members' => ['required', 'array', 'min:1', "max:{$otherMembers}"],
            'members.*.full_name' => ['required', 'string', 'max:100'],
            'members.*.phone' => ['nullable', 'string', 'regex:'.self::PHONE_PATTERN],
            'members.*.birth_date' => ['required', 'date', 'before:today', 'after:1920-01-01'],
            'members.*.gender' => ['required', Rule::enum(Gender::class)],
            'members.*.identity_number' => ['required', 'string', 'regex:'.self::IDENTITY_PATTERN],
            'members.*.tshirt_size' => ['required', Rule::enum(TshirtSize::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'members.required' => 'Isi data minimal 1 anggota bundle lainnya.',
            'members.min' => 'Isi data minimal 1 anggota bundle lainnya.',
            'members.max' => 'Paket ini hanya untuk maksimal :max anggota lain selain kamu.',
            'members.*.phone.regex' => 'Format :attribute tidak valid. Gunakan format 08xxxxxxxxxx.',
            'members.*.identity_number.regex' => ':attribute harus terdiri dari 6–20 huruf atau angka.',
            'members.*.birth_date.before' => ':attribute harus sebelum hari ini.',
            'members.*.gender.required' => 'Pilih gender anggota.',
            'members.*.tshirt_size.required' => 'Pilih ukuran T-shirt anggota.',
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
        ];
    }

    /**
     * The validated members with their position in the bundle (the account holder is position 1).
     *
     * @return list<array{position: int, full_name: string, phone: ?string, birth_date: string, gender: string, identity_number: string, tshirt_size: string}>
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
                'identity_number' => $member['identity_number'],
                'tshirt_size' => $member['tshirt_size'],
            ])
            ->all();
    }

    public function package(): ?TicketPrice
    {
        return TicketPrice::findBundle((string) $this->route('bundle'));
    }
}
