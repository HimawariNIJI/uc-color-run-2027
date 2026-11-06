<x-layouts.admin title="Dashboard" subtitle="Ringkasan pendaftaran, pembayaran, dan pengambilan BIB UC Color Run 2027.">
    <div class="flex flex-col gap-4 sm:gap-6">
        <section class="grid gap-4 min-[30rem]:grid-cols-2 sm:gap-6 xl:grid-cols-4" aria-label="Ringkasan">
            @foreach ($stats as $stat)
                <x-admin.stat-card :label="$stat['label']" :value="$stat['value']" :caption="$stat['caption']" :icon="$stat['icon']" />
            @endforeach
        </section>

        <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
            <x-admin.card class="flex flex-col" title="Registrations by Category" description="Distribution of race entries">
                <x-admin.charts.bar
                    class="mt-6 flex flex-1 flex-col justify-center"
                    :data="$registrationsByCategory"
                    label="Registrations by Category"
                    category-heading="Category"
                    value-heading="registrasi"
                />
            </x-admin.card>

            <x-admin.card title="Payment Overview" description="Distribution by payment status">
                <x-admin.charts.donut
                    class="mt-6"
                    :data="$paymentOverview"
                    label="Payment Overview"
                    center-label="Registrations"
                    category-heading="Payment Status"
                    value-heading="registrasi"
                />
            </x-admin.card>
        </div>

        <x-admin.card id="registration-trend" class="scroll-mt-6" title="Registration Trend" description="New registrations over the last {{ $trendRange }} days">
            <x-slot:actions>
                <form method="GET" action="{{ route('admin.dashboard') }}#registration-trend" data-auto-submit>
                    @foreach ($filters->toQuery() as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <div class="relative">
                        <label for="range" class="sr-only">Rentang waktu Registration Trend</label>
                        <select name="range" id="range" class="form-control h-10 w-34 cursor-pointer appearance-none rounded-full pl-4 pr-10">
                            @foreach ($trendRanges as $days)
                                <option value="{{ $days }}" @selected($days === $trendRange)>{{ $days }} days</option>
                            @endforeach
                        </select>
                        <x-icon name="chevron-down" class="pointer-events-none absolute right-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
                    </div>
                    <noscript><x-button type="submit" variant="secondary" size="sm" pill class="mt-2">Terapkan</x-button></noscript>
                </form>
            </x-slot:actions>

            <x-admin.charts.line
                class="mt-6"
                :points="$trend"
                label="Registration Trend"
                value-heading="Registrasi baru"
                unit="registrasi baru"
            />
        </x-admin.card>

        <x-admin.card id="recent-registrations" class="scroll-mt-6" title="Recent Registrations" description="Latest participant activity">
            <x-slot:actions>
                <x-button :href="route('admin.registrations.index', $filters->toQuery())" variant="secondary" size="sm" pill>
                    View All
                    <x-icon name="arrow-right" class="size-4" />
                </x-button>
            </x-slot:actions>

            <form method="GET" action="{{ route('admin.dashboard') }}#recent-registrations" class="mt-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem]" role="search" data-auto-submit>
                @if ($trendRange !== $trendRanges[0])
                    <input type="hidden" name="range" value="{{ $trendRange }}">
                @endif
                <x-admin.search-input :value="$filters->search" placeholder="Search name, email, or registration ID..." label="Cari registrasi terbaru" />
                <x-admin.filter-select name="status" :options="$statusOptions" :selected="$filters->status?->value" all-label="All Statuses" label="Filter status pembayaran" />
            </form>

            <x-admin.table class="mt-5" label="Registrasi terbaru" min-width="min-w-[36rem]">
                <x-slot:head>
                    <th scope="col">Participant</th>
                    <th scope="col">Category</th>
                    <th scope="col">Status</th>
                    <th scope="col">Date</th>
                </x-slot:head>

                @forelse ($recentRegistrations as $registration)
                    <tr>
                        <td>
                            <a href="{{ route('admin.registrations.show', $registration) }}" class="group block rounded-md focus-visible:outline-2 focus-visible:outline-primary" data-admin-modal-link>
                                <x-admin.person :name="$registration->full_name" :detail="$registration->code" class="[&_p:first-child]:group-hover:text-primary" />
                            </a>
                        </td>
                        <td><x-admin.category-badge :category="$registration->category" /></td>
                        <td><x-badge :tone="$registration->payment_status->tone()">{{ $registration->payment_status->label() }}</x-badge></td>
                        <td class="whitespace-nowrap">{{ $registration->created_at->translatedFormat('j M Y') }}</td>
                    </tr>
                @empty
                    <x-admin.empty-row colspan="4" :title="$filters->isFiltered() ? 'Tidak ada registrasi yang cocok.' : 'Belum ada registrasi.'">
                        {{ $filters->isFiltered() ? 'Coba kata kunci atau status lain.' : 'Registrasi peserta akan muncul di sini.' }}
                    </x-admin.empty-row>
                @endforelse
            </x-admin.table>
        </x-admin.card>
    </div>
</x-layouts.admin>
