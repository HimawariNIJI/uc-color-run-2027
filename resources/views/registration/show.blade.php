<x-layouts.app title="My Registration">
    <div class="page-container page-section">
        <div class="mx-auto max-w-card">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 rounded-lg text-body-sm font-medium text-muted transition-colors hover:text-primary">
                <x-icon name="arrow-left" class="size-4" />
                Back to home
            </a>

            <section class="mt-3 rounded-3xl border border-border bg-surface p-5 shadow-card sm:p-6">
                <header class="flex items-center justify-between gap-3 border-b border-border pb-4">
                    <h1 class="text-h3">My Registration</h1>
                    @if (! $registration)
                        <x-badge>Empty</x-badge>
                    @elseif ($registration->isPaid())
                        <x-badge tone="success">Paid</x-badge>
                    @else
                        <x-badge tone="warning">Pending</x-badge>
                    @endif
                </header>

                @if (! $registration)
                    <div class="mt-5 rounded-2xl border border-border bg-background px-5 py-8">
                        <x-state-message icon="inbox" title="No Registration Yet">
                            You haven't registered for UC Color Run 2027 yet. Complete your registration to secure your spot!
                        </x-state-message>
                    </div>

                    <x-button :href="route('registration.type')" size="lg" pill block class="mt-4">
                        Register Now
                        <x-icon name="arrow-right" class="size-4.5" />
                    </x-button>
                @else
                    @php($isPaid = $registration->isPaid())

                    <div class="mt-5 rounded-2xl border border-border bg-background p-4 sm:p-5">
                        <div class="flex items-start gap-3 border-b border-border pb-4">
                            <span @class([
                                'grid size-10 shrink-0 place-items-center rounded-xl',
                                'bg-success/12 text-success-strong' => $isPaid,
                                'bg-primary/10 text-primary' => ! $isPaid,
                            ])>
                                <x-icon :name="$isPaid ? 'badge-check' : 'clipboard-list'" class="size-5" />
                            </span>
                            <div>
                                <h2 class="text-body font-bold">{{ $isPaid ? 'Registration Confirmed' : 'Registration Submitted' }}</h2>
                                <p class="mt-0.5 text-caption text-muted">
                                    @if ($isPaid)
                                        Your payment has been verified and your registration is confirmed. See you at the starting line!
                                    @else
                                        Your registration has been submitted successfully. Please complete your payment to confirm your participation.
                                    @endif
                                </p>
                            </div>
                        </div>

                        <h3 class="mt-4 text-body-sm font-bold">Registration Details</h3>
                        <dl class="mt-3 flex flex-col gap-2.5">
                            <x-detail-row label="Registration ID">{{ $registration->code }}</x-detail-row>
                            <x-detail-row label="Participant Name">{{ $registration->full_name }}</x-detail-row>
                            <x-detail-row label="Email"><span class="break-all">{{ $registration->email }}</span></x-detail-row>
                            <x-detail-row label="Race Category">{{ $registration->category->runLabel() }} &middot; {{ $registration->type->label() }}</x-detail-row>
                            <x-detail-row label="T-shirt Size">{{ $registration->tshirt_size->label() }}</x-detail-row>
                        </dl>

                        <h3 class="mt-4 border-t border-border pt-4 text-body-sm font-bold">Payment Information</h3>
                        <dl class="mt-3 flex flex-col gap-2.5">
                            <x-detail-row label="Payment Status">
                                <x-badge :tone="$registration->payment_status->tone()">{{ $registration->payment_status->label() }}</x-badge>
                            </x-detail-row>
                            <x-detail-row label="Amount" emphasis>{{ \App\Support\Rupiah::format($registration->amount) }}</x-detail-row>
                        </dl>
                    </div>

                    <div class="mt-4 flex flex-col gap-3">
                        @if ($isPaid)
                            <x-button :href="route('my-bib.show')" size="lg" pill block>
                                View My BIB
                                <x-icon name="arrow-right" class="size-4.5" />
                            </x-button>
                        @else
                            @if ($registration->hasFailedPayment())
                                <form method="POST" action="{{ route('payment.retry') }}" data-submit-guard>
                                    @csrf
                                    <x-button type="submit" size="lg" pill block>
                                        <x-icon name="wallet" class="size-4.5" />
                                        Pay Now
                                    </x-button>
                                </form>
                            @else
                                <x-button :href="route('payment.show')" size="lg" pill block>
                                    <x-icon name="wallet" class="size-4.5" />
                                    Pay Now
                                </x-button>
                            @endif
                            <x-button :href="route('my-registration.edit')" variant="secondary" size="lg" pill block>
                                <x-icon name="pencil" class="size-4.5" />
                                Edit Registration
                            </x-button>
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts.app>
