<x-layouts.admin title="User Management" subtitle="Detail akun peserta.">
    <div class="mx-auto max-w-2xl overflow-hidden rounded-3xl border border-border bg-surface shadow-card">
        @fragment('details')
            <x-admin.detail-panel title="User Account Details" subtitle="Account overview" :close-href="route('admin.users.index')">
                <div class="flex items-center gap-4">
                    <x-avatar :user="$user" class="size-14" />
                    <div class="min-w-0">
                        <p class="text-body-lg font-semibold wrap-anywhere">{{ $user->name }}</p>
                        <p class="text-body-sm text-muted wrap-anywhere">{{ $user->email }}</p>
                    </div>
                </div>

                <dl class="mt-6 grid gap-x-6 gap-y-5 min-[26rem]:grid-cols-2">
                    <x-admin.detail-item label="Last Login">{{ $user->last_login_at?->translatedFormat('j M Y, H:i') ?? '-' }}</x-admin.detail-item>
                    <x-admin.detail-item label="Registration Status">{{ $user->registration?->payment_status->accountStatusLabel() ?? 'Not Registered' }}</x-admin.detail-item>
                    <x-admin.detail-item label="BIB Number">{{ $user->registration?->bib_number ?? '-' }}</x-admin.detail-item>
                    <x-admin.detail-item label="Authentication">{{ $user->authenticationMethod() }}</x-admin.detail-item>
                </dl>

                @if ($user->registration)
                    <x-button :href="route('admin.registrations.show', $user->registration)" variant="secondary" pill block class="mt-7" data-admin-modal-link>
                        View Registration Details
                    </x-button>
                @endif
            </x-admin.detail-panel>
        @endfragment
    </div>
</x-layouts.admin>
