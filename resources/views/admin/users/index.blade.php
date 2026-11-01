<x-layouts.admin title="User Management" subtitle="Akun peserta yang masuk ke website UC Color Run 2027.">
    <x-admin.card title="User Accounts" description="Google accounts that have accessed the event website" divided>
        <x-slot:actions>
            <x-button :href="route('admin.users.export', array_filter(['search' => $search]))" variant="secondary" size="sm" pill>
                <x-icon name="download" class="size-4" />
                Export CSV
            </x-button>
        </x-slot:actions>

        <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-3" role="search" data-auto-submit>
            <x-admin.search-input class="flex-1" :value="$search" placeholder="Search name or email..." label="Cari nama atau email" />
            <x-button :href="route('admin.users.index')" variant="secondary" pill class="min-h-11 shrink-0 sm:w-36">Reset</x-button>
        </form>

        <p class="mt-5 text-body-sm text-muted" aria-live="polite">
            {{ \App\Support\NumberFormat::integer($users->total()) }} {{ Str::plural('account', $users->total()) }} displayed
        </p>

        <x-admin.table class="mt-3" label="Daftar akun peserta" min-width="min-w-[40rem]">
            <x-slot:head>
                <th scope="col">User</th>
                <th scope="col">Last Login</th>
                <th scope="col">BIB Number</th>
                <th scope="col">Action</th>
            </x-slot:head>

            @forelse ($users as $user)
                <tr>
                    <td><x-admin.person :name="$user->name" :detail="$user->email" :avatar="$user" /></td>
                    <td class="whitespace-nowrap">{{ $user->last_login_at?->translatedFormat('j M Y, H:i') ?? '-' }}</td>
                    <td class="tabular-nums">{{ $user->registration?->bib_number ?? '-' }}</td>
                    <td><x-admin.view-link :href="route('admin.users.show', $user)" :subject="$user->name" /></td>
                </tr>
            @empty
                <x-admin.empty-row colspan="4" icon="users" :title="$search !== '' ? 'Tidak ada akun yang cocok.' : 'Belum ada akun peserta.'">
                    {{ $search !== '' ? 'Coba kata kunci lain atau tekan Reset.' : 'Akun muncul di sini setelah peserta masuk dengan Google.' }}
                </x-admin.empty-row>
            @endforelse
        </x-admin.table>

        <x-admin.pagination :paginator="$users" />
    </x-admin.card>
</x-layouts.admin>
