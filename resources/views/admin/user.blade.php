@extends('layouts.admin', [
    'heading' => 'User Management',
    'subheading' => 'View user details and account information.',
])

@section('title', 'User Management')

@section('content')
<div class="user-management">
    <div class="user-card">

        {{-- Header --}}
        <div class="user-header">
            <div>
                <h2>User Accounts</h2>
                <p>Google accounts that have accessed the event website.</p>
            </div>

            <a href="{{ route('admin.user.index') }}" class="btn-export">
                <svg class="icon" aria-hidden="true">
                    <use href="#icon-download" />
                </svg>
                Export CSV
            </a>
        </div>

        <div class="user-divider"></div>

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.user.index') }}"
              class="user-search-form">
            <div class="user-search">
                <svg class="icon" aria-hidden="true">
                    <use href="#icon-search" />
                </svg>

                <input
                    type="text"
                    name="search"
                    id="userSearch"
                    placeholder="Search name or email..."
                    aria-label="Search users by name"
                    autocomplete="off"
                >
            </div>

            <button type="submit" class="btn-search">Search</button>

            <a href="{{ route('admin.user.index') }}" class="btn-reset">
                Reset
            </a>
        </form>

        {{-- Table --}}
        <div class="user-table-wrapper">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>USER</th>
                        <th>LAST LOGIN</th>
                        <th>BIB NUMBER</th>
                        <th>ACTION</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="user-profile">
                                    <div class="user-avatar">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>

                                    <div class="user-identity">
                                        <span class="user-name">
                                            {{ $user->name }}
                                        </span>
                                        <span class="user-email">
                                            {{ $user->email }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="user-last-login">
                                    {{ $user->last_login
                                        ? \Carbon\Carbon::parse($user->last_login)->format('d M Y, H:i')
                                        : 'Never logged in' }}
                                </span>
                            </td>

                            <td>
                                <span class="bib-number">
                                    {{ $user->bib_number ?? '-' }}
                                </span>
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="btn-view"
                                    data-user-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-last-login="{{ $user->last_login ?? '' }}"
                                    data-bib-number="{{ $user->bib_number ?? '-' }}"
                                    data-registration-status="Registered"
                                    data-authentication="Google Sign-In"
                                    onclick="openAccountModal(this)"
                                >
                                    View <span aria-hidden="true">&rarr;</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="user-empty">
                                No user accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($users->hasPages())
            <div class="user-pagination">
                <div class="pagination-info">
                    Showing
                    <strong>{{ $users->firstItem() ?? 0 }}</strong>
                    to
                    <strong>{{ $users->lastItem() ?? 0 }}</strong>
                    of
                    <strong>{{ $users->total() }}</strong>
                    users
                </div>

                <div class="pagination-links">
                    {{ $users->withQueryString()->links() }}
                </div>
            </div>
        @endif

    </div>
</div>

<div class="account-modal" id="accountModal"
     aria-hidden="true">

    <div class="account-backdrop" onclick="closeAccountModal()"></div>

    <section class="account-dialog"
             role="dialog"
             aria-modal="true"
             aria-labelledby="accountModalTitle">

        <header class="account-header">
            <h2 id="accountModalTitle">User Account Details</h2>

            <button type="button"
                    class="account-close"
                    onclick="closeAccountModal()">
                <span aria-hidden="true">&times;</span>
                <span>Close</span>
            </button>
        </header>

        <p class="account-subtitle">Account overview</p>

        <hr class="account-divider">

        <div class="account-profile">
            <div class="account-avatar" id="modalAvatar">U</div>

            <div>
                <p class="account-name" id="modalName">Name</p>
                <p class="account-email" id="modalEmail">Email</p>
            </div>
        </div>

        <div class="account-details-grid">
            <div>
                <p class="account-label">Last Login</p>
                <p class="account-value" id="modalLastLogin">-</p>
            </div>

            <div>
                <p class="account-label">Registration Status</p>
                <p class="account-value" id="modalStatus">Registered</p>
            </div>

            <div>
                <p class="account-label">BIB Number</p>
                <p class="account-value" id="modalBib">-</p>
            </div>

            <div>
                <p class="account-label">Authentication</p>
                <p class="account-value" id="modalAuthentication">
                    Google Sign-In
                </p>
            </div>
        </div>

        <button type="button"
                class="account-registration-button"
                onclick="viewRegistrationDetails()">
            View Registration Details
        </button>
    </section>
</div>

<script>
    const accountModal = document.getElementById('accountModal');
    let previousFocusedElement = null;

    function openAccountModal(button) {
        previousFocusedElement = button;

        const data = button.dataset;

        document.getElementById('modalName').textContent =
            data.name || '-';

        document.getElementById('modalEmail').textContent =
            data.email || '-';

        document.getElementById('modalAvatar').textContent =
            (data.name || 'U').trim().charAt(0).toUpperCase();

        document.getElementById('modalLastLogin').textContent =
            formatLastLogin(data.lastLogin);

        document.getElementById('modalBib').textContent =
            data.bibNumber || '-';

        document.getElementById('modalStatus').textContent =
            data.registrationStatus || '-';

        document.getElementById('modalAuthentication').textContent =
            data.authentication || '-';

        accountModal.classList.add('is-open');
        accountModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');

        accountModal.querySelector('.account-close').focus();
    }

    function formatLastLogin(value) {
        if (!value) return 'Never logged in';

        const date = new Date(value.replace(' ', 'T'));

        if (Number.isNaN(date.getTime())) return value;

        return new Intl.DateTimeFormat('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23'
        }).format(date).replace(',', ',');
    }

    function closeAccountModal() {
        accountModal.classList.remove('is-open');
        accountModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');

        if (previousFocusedElement) {
            previousFocusedElement.focus();
        }
    }

    function viewRegistrationDetails() {
        alert('Registration Details');
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' &&
            accountModal.classList.contains('is-open')) {
            closeAccountModal();
        }
    });
</script>

@endsection