<x-layouts.admin title="Registration Management" subtitle="Pantau data pendaftaran dan status pembayaran peserta.">
    <x-admin.card title="Participant Registrations" description="Review and manage individual entries" divided>
        <x-slot:actions>
            <x-button :href="route('admin.registrations.export', $filters->toQuery())" variant="secondary" size="sm" pill>
                <x-icon name="download" class="size-4" />
                Export CSV
            </x-button>
        </x-slot:actions>

        <form method="GET" action="{{ route('admin.registrations.index') }}" class="grid gap-3 min-[26rem]:grid-cols-2 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] xl:grid-cols-[minmax(0,1fr)_11rem_11rem_auto]" role="search" data-auto-submit>
            <x-admin.search-input class="min-[26rem]:col-span-2 md:col-span-3 xl:col-span-1" :value="$filters->search" placeholder="Search name or email..." label="Cari nama, email, atau Registration ID" />
            <x-admin.filter-select name="status" :options="$statusOptions" :selected="$filters->status?->value" all-label="All Status" label="Filter status pembayaran" />
            <x-admin.filter-select name="category" :options="$categoryOptions" :selected="$filters->category?->value" all-label="All Categories" label="Filter kategori lomba" />
            <x-button :href="route('admin.registrations.index')" variant="secondary" pill class="min-h-11 min-[26rem]:col-span-2 md:col-span-1 md:w-32">Reset</x-button>
        </form>

        <p class="mt-5 text-body-sm text-muted" aria-live="polite">
            {{ \App\Support\NumberFormat::integer($registrations->total()) }} {{ Str::plural('entry', $registrations->total()) }} shown
        </p>

        <x-admin.table class="mt-3" label="Daftar registrasi peserta" min-width="min-w-[50rem]">
            <x-slot:head>
                <th scope="col">Registration ID</th>
                <th scope="col">Participant</th>
                <th scope="col">Category</th>
                <th scope="col">Payment Status</th>
                <th scope="col">BIB Number</th>
                <th scope="col">Action</th>
            </x-slot:head>

            @forelse ($registrations as $registration)
                <tr>
                    <td class="whitespace-nowrap font-medium tabular-nums">{{ $registration->code }}</td>
                    <td><x-admin.person :name="$registration->full_name" :detail="$registration->email" :detail-href="'mailto:'.$registration->email" /></td>
                    <td><x-admin.category-badge :category="$registration->category" /></td>
                    <td><x-badge :tone="$registration->payment_status->tone()">{{ $registration->payment_status->label() }}</x-badge></td>
                    <td class="tabular-nums">{{ $registration->bib_number ?? '-' }}</td>
                    <td><x-admin.view-link :href="route('admin.registrations.show', $registration)" :subject="$registration->code" /></td>
                </tr>
            @empty
                <x-admin.empty-row colspan="6" icon="clipboard-list" :title="$filters->isFiltered() ? 'Tidak ada registrasi yang cocok.' : 'Belum ada registrasi.'">
                    {{ $filters->isFiltered() ? 'Ubah kata kunci atau filter, atau tekan Reset.' : 'Registrasi muncul di sini setelah peserta mengisi formulir pendaftaran.' }}
                </x-admin.empty-row>
            @endforelse
        </x-admin.table>

        <x-admin.pagination :paginator="$registrations" />
    </x-admin.card>
</x-layouts.admin>
