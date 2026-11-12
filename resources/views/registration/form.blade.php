{{--
    Participant form, shared by "create registration" and "edit registration". The account holder
    is the payer and uploads a photo of their KTP, or of the family's Kartu Keluarga for a Family
    Bundle. For a new bundle it also asks for every other person in the bundle (members[0..n], all
    required); they share the holder's emergency contact and address and give their own medical
    information. Couple Bundle members also type a KTP/Passport number; Family Bundle members don't,
    because the Kartu Keluarga covers them.
--}}
@php
    $isEditing = $registration->exists;
    $selectedProvince = old('province', $registration->province);
    $provinces = array_keys($regions);
    $amount = $isEditing ? $registration->amount : $package->price;
    $packageName = $isEditing ? $registration->packageName() : $package->name();
    $identityDocument = \App\Enums\IdentityDocument::forPackage($package);
    $usesFamilyCard = $identityDocument === \App\Enums\IdentityDocument::FamilyCard;
    $memberSlots = ! $isEditing && $package->isBundle() ? $package->max_participants - 1 : 0;
    $feeHint = 'Biaya registrasi '.\App\Support\Rupiah::format($amount).' ('.$packageName.($memberSlots > 0 ? ', satu paket untuk '.$package->max_participants.' peserta' : '').')';
@endphp

<x-layouts.app :title="$isEditing ? 'Edit Registration' : 'Registration Form'">
    <div class="page-container page-section">
        @unless ($isEditing)
            <x-registration-steps :current="4" class="mb-8" />
        @endunless

        <form
            method="POST"
            action="{{ $action }}"
            enctype="multipart/form-data"
            class="mx-auto max-w-form overflow-hidden rounded-3xl border border-border bg-surface shadow-card"
            novalidate
            data-submit-guard
        >
            @csrf
            @method($method)

            <header class="flex flex-col items-center gap-3 border-b border-border px-5 py-8 text-center sm:px-8">
                <x-logo size="lg" />
                <h1 class="text-body-sm text-muted">
                    {{ $isEditing ? 'You are editing your UC Color Run registration' : 'You are registering with UC Color Run' }}
                </h1>
            </header>

            <div class="flex flex-col gap-8 px-5 py-6 sm:px-8 md:py-8">
                @if ($errors->any())
                    <x-alert tone="error" title="Periksa kembali formulirmu">
                        Ada {{ $errors->count() }} isian yang perlu diperbaiki sebelum registrasi dapat dikirim.
                    </x-alert>
                @endif

                <fieldset>
                    <legend class="text-body-sm font-bold uppercase tracking-wide">1. Personal Information</legend>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-form.field label="Full Nama (Nama Pembayar)" for="full_name" required>
                            <x-form.input name="full_name" :value="$registration->full_name" placeholder="Enter Full name" autocomplete="name" required />
                        </x-form.field>

                        <x-form.field label="Email Address" for="email" required>
                            <x-form.input type="email" name="email" :value="$registration->email" placeholder="you@example.com (Autofill)" readonly />
                        </x-form.field>

                        <x-form.field label="Phone Number" for="phone" required>
                            <x-form.input type="tel" name="phone" :value="$registration->phone" placeholder="08xxxxxxxxxx" inputmode="tel" autocomplete="tel" required />
                        </x-form.field>

                        <x-form.field label="Date of Birth" for="birth_date" required>
                            <x-form.input
                                type="date"
                                name="birth_date"
                                :value="$registration->birth_date?->format('Y-m-d')"
                                icon="calendar"
                                min="1920-01-02"
                                :max="now()->subDay()->format('Y-m-d')"
                                autocomplete="bday"
                                required
                            />
                        </x-form.field>

                        <x-form.field label="Gender" for="gender" required>
                            <x-form.select name="gender" :options="\App\Enums\Gender::options()" :selected="$registration->gender?->value" placeholder="Select gender" required />
                        </x-form.field>

                        <x-form.field
                            :label="$identityDocument->label()"
                            :for="$identityDocument->field()"
                            :hint="$usesFamilyCard ? 'Satu foto KK untuk seluruh peserta Family Bundle. Tidak perlu KTP.' : null"
                            required
                        >
                            <x-form.image-upload
                                :name="$identityDocument->field()"
                                :label="$identityDocument->noun()"
                                :max-kilobytes="\App\Support\IdentityPhoto::MAX_KILOBYTES"
                                :mime-types="\App\Support\IdentityPhoto::MIME_TYPES"
                                :saved="$registration->hasIdentityPhoto()"
                            />
                        </x-form.field>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="text-body-sm font-bold uppercase tracking-wide">2. Race Information</legend>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-form.field
                            label="Race Category"
                            for="race_category"
                            required
                            :hint="$feeHint"
                        >
                            <x-form.input name="race_category" :value="$registration->raceLabel()" placeholder="3K/5K/10K Internal / External" readonly aria-describedby="race_category-hint" />
                        </x-form.field>

                        <x-form.field label="T-shirt Size" for="tshirt_size" required>
                            <x-form.select name="tshirt_size" :options="\App\Enums\TshirtSize::options()" :selected="$registration->tshirt_size?->value" placeholder="Select t-shirt size" required />
                        </x-form.field>

                        <x-form.field label="Emergency Contact Name" for="emergency_contact_name" required>
                            <x-form.input name="emergency_contact_name" :value="$registration->emergency_contact_name" placeholder="Enter Full name" required />
                        </x-form.field>

                        <x-form.field label="Emergency Contact Phone" for="emergency_contact_phone" required>
                            <x-form.input type="tel" name="emergency_contact_phone" :value="$registration->emergency_contact_phone" placeholder="08xxxxxxxxxx" inputmode="tel" required />
                        </x-form.field>

                        <x-form.field label="Riwayat Penyakit" for="medical_history" required hint="Tulis “Tidak Ada” jika tidak memiliki riwayat penyakit.">
                            <x-form.textarea name="medical_history" :value="$registration->medical_history" :pill="false" rows="3" maxlength="1000" placeholder="Contoh: asma, hipertensi, penyakit jantung" required />
                        </x-form.field>

                        <x-form.field label="Alergi Obat" for="drug_allergies" required hint="Tulis “Tidak Ada” jika tidak memiliki alergi obat.">
                            <x-form.textarea name="drug_allergies" :value="$registration->drug_allergies" :pill="false" rows="3" maxlength="1000" placeholder="Contoh: penisilin, ibuprofen" required />
                        </x-form.field>
                    </div>
                </fieldset>

                <fieldset data-region-select>
                    <legend class="text-body-sm font-bold uppercase tracking-wide">3. Address Information</legend>
                    <script type="application/json" data-region-data>@json($regions)</script>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-form.field label="Province" for="province" required>
                            <x-form.select
                                name="province"
                                :options="array_combine($provinces, $provinces)"
                                :selected="$registration->province"
                                placeholder="Select province"
                                autocomplete="address-level1"
                                data-region-province
                                required
                            />
                        </x-form.field>

                        <x-form.field label="City" for="city" required>
                            @php($cities = $regions[$selectedProvince] ?? [])
                            <x-form.select
                                name="city"
                                :options="array_combine($cities, $cities)"
                                :selected="$registration->city"
                                placeholder="Select city"
                                autocomplete="address-level2"
                                data-region-city
                                data-placeholder="Select city"
                                required
                            />
                        </x-form.field>

                        <x-form.field label="Full Address" for="address" required class="sm:col-span-2">
                            <x-form.input name="address" :value="$registration->address" placeholder="Enter Full address" autocomplete="street-address" required />
                        </x-form.field>
                    </div>

                    <p class="mt-4 text-caption text-muted">* Field wajib diisi. Email diisi otomatis setelah peserta login dengan Google.</p>
                </fieldset>

                @if ($memberSlots > 0)
                    <fieldset>
                        <legend class="text-body-sm font-bold uppercase tracking-wide">4. Bundle Members</legend>
                        <p class="mt-1 text-caption text-muted">
                            Kamu adalah peserta 1.
                            {{ $memberSlots === 1 ? 'Isi data pasanganmu.' : 'Isi data '.$memberSlots.' anggota lainnya: paket ini wajib diisi tepat '.$package->max_participants.' peserta.' }}
                            Kontak darurat dan alamat mengikuti datamu.
                            @if ($usesFamilyCard)
                                Identitas seluruh peserta cukup dengan Foto Kartu Keluarga di atas.
                            @endif
                        </p>
                        @error('members')
                            <p class="mt-2 text-caption font-medium text-error">{{ $message }}</p>
                        @enderror

                        @for ($index = 0; $index < $memberSlots; $index++)
                            <div class="mt-4 rounded-2xl border border-border p-4 sm:p-5">
                                <p class="text-body-sm font-semibold">Peserta {{ $index + 2 }}</p>
                                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                    <x-form.field label="Full Name" :for="'members_'.$index.'_full_name'" :error="'members.'.$index.'.full_name'" required :class="$usesFamilyCard ? 'sm:col-span-2' : ''">
                                        <x-form.input :name="'members['.$index.'][full_name]'" :id="'members_'.$index.'_full_name'" placeholder="Enter Full name" />
                                    </x-form.field>

                                    <x-form.field label="Phone Number (opsional)" :for="'members_'.$index.'_phone'" :error="'members.'.$index.'.phone'">
                                        <x-form.input type="tel" :name="'members['.$index.'][phone]'" :id="'members_'.$index.'_phone'" placeholder="08xxxxxxxxxx" inputmode="tel" />
                                    </x-form.field>

                                    <x-form.field label="Date of Birth" :for="'members_'.$index.'_birth_date'" :error="'members.'.$index.'.birth_date'" required>
                                        <x-form.input type="date" :name="'members['.$index.'][birth_date]'" :id="'members_'.$index.'_birth_date'" icon="calendar" min="1920-01-02" :max="now()->subDay()->format('Y-m-d')" />
                                    </x-form.field>

                                    <x-form.field label="Gender" :for="'members_'.$index.'_gender'" :error="'members.'.$index.'.gender'" required>
                                        <x-form.select :name="'members['.$index.'][gender]'" :id="'members_'.$index.'_gender'" :options="\App\Enums\Gender::options()" placeholder="Select gender" />
                                    </x-form.field>

                                    @unless ($usesFamilyCard)
                                        <x-form.field label="KTP/Passport" :for="'members_'.$index.'_identity_number'" :error="'members.'.$index.'.identity_number'" required>
                                            <x-form.input :name="'members['.$index.'][identity_number]'" :id="'members_'.$index.'_identity_number'" placeholder="Enter KTP/Passport ID" autocomplete="off" />
                                        </x-form.field>
                                    @endunless

                                    <x-form.field label="T-shirt Size" :for="'members_'.$index.'_tshirt_size'" :error="'members.'.$index.'.tshirt_size'" required>
                                        <x-form.select :name="'members['.$index.'][tshirt_size]'" :id="'members_'.$index.'_tshirt_size'" :options="\App\Enums\TshirtSize::options()" placeholder="Select t-shirt size" />
                                    </x-form.field>

                                    <x-form.field label="Riwayat Penyakit" :for="'members_'.$index.'_medical_history'" :error="'members.'.$index.'.medical_history'" required hint="Tulis “Tidak Ada” jika tidak ada.">
                                        <x-form.textarea :name="'members['.$index.'][medical_history]'" :id="'members_'.$index.'_medical_history'" :pill="false" rows="3" maxlength="1000" placeholder="Contoh: asma, hipertensi" />
                                    </x-form.field>

                                    <x-form.field label="Alergi Obat" :for="'members_'.$index.'_drug_allergies'" :error="'members.'.$index.'.drug_allergies'" required hint="Tulis “Tidak Ada” jika tidak ada.">
                                        <x-form.textarea :name="'members['.$index.'][drug_allergies]'" :id="'members_'.$index.'_drug_allergies'" :pill="false" rows="3" maxlength="1000" placeholder="Contoh: penisilin" />
                                    </x-form.field>
                                </div>
                            </div>
                        @endfor
                    </fieldset>
                @endif

                <div class="flex flex-col gap-5 border-t border-border pt-6">
                    <x-form.checkbox name="agreement" :checked="$isEditing">
                        I agree to the
                        <a href="{{ route('race.regulation') }}" target="_blank" class="font-semibold text-primary underline-offset-2 hover:underline">Race Regulations</a>
                        and
                        <a href="{{ route('privacy') }}" target="_blank" class="font-semibold text-primary underline-offset-2 hover:underline">Privacy Policy</a>.
                    </x-form.checkbox>

                    <div class="flex flex-col gap-3">
                        <x-button type="submit" size="lg" block data-busy-label="Menyimpan…">
                            {{ $isEditing ? 'Save Changes' : 'Submit Registration' }}
                        </x-button>
                        @if ($isEditing)
                            <x-button :href="route('my-registration.show')" variant="ghost" block>Cancel</x-button>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app>
