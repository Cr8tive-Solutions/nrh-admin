<?php

namespace App\Http\Controllers;

use App\Mail\CustomerUserInvitationMail;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerUser;
use App\Models\CustomerUserInvitation;
use App\Models\Package;
use App\Models\PortalRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount(['screeningRequests', 'invoices'])
            ->with('primaryUser.latestInvitation')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('registration_no', 'ilike', "%{$search}%")
                    ->orWhere('contact_email', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('invitation')) {
            $filter = $request->invitation;
            $query->whereHas('primaryUser.latestInvitation', function ($q) use ($filter) {
                if ($filter === 'pending') {
                    $q->whereNull('accepted_at')->where('expires_at', '>', now());
                } elseif ($filter === 'expired') {
                    $q->whereNull('accepted_at')->where('expires_at', '<=', now());
                } elseif ($filter === 'accepted') {
                    $q->whereNotNull('accepted_at');
                }
            });
        }

        $customers = $query->paginate(25)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'agreements',
            'customerUsers.latestInvitation',
            'customerUsers.portalRoles',
            'invoices' => fn ($q) => $q->latest()->take(10),
            'transactions' => fn ($q) => $q->latest()->take(10),
            'packages' => fn ($q) => $q->orderBy('country_id')->orderBy('name'),
            'packages.country',
            'packages.scopeTypes',
        ]);
        $recentRequests = $customer->screeningRequests()->with('candidates')->latest()->take(10)->get();

        $stats = [
            'requests_total' => $customer->screeningRequests()->count(),
            'requests_active' => $customer->screeningRequests()->whereIn('status', ['new', 'in_progress'])->count(),
            'requests_flagged' => $customer->screeningRequests()->whereHas('candidates', fn ($q) => $q->where('status', 'flagged'))->count(),
            'invoices_unpaid' => $customer->invoices()->whereIn('status', ['unpaid', 'overdue'])->count(),
            'team_members' => $customer->customerUsers->count(),
        ];

        $activeAgreement = $customer->activeAgreement();

        $countryFlag = Country::where('name', $customer->country)->value('flag');

        // Fetched once and threaded through the package helpers, so the Packages
        // tab costs one query rather than one per package and per scope.
        $scopePrices = $customer->scopePrices()->pluck('price', 'scope_type_id');

        $packageTotals = $customer->packages->mapWithKeys(
            fn (Package $package) => [$package->id => $package->effectiveTotal($scopePrices)]
        );

        $packageUnpriced = $customer->packages->mapWithKeys(
            fn (Package $package) => [$package->id => $package->unpricedScopeTypes($scopePrices)->pluck('id')->all()]
        );

        return view('customers.show', compact(
            'customer', 'recentRequests', 'stats', 'activeAgreement', 'countryFlag',
            'scopePrices', 'packageTotals', 'packageUnpriced',
        ));
    }

    public function edit(Customer $customer)
    {
        $countries = Country::orderBy('name')->get();

        return view('customers.edit', compact('customer', 'countries'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'registration_no' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'industry' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
        ]);

        $customer->update($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function create()
    {
        $countries = Country::orderBy('name')->get();

        return view('customers.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'registration_no' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'industry' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'send_invitation' => 'sometimes|boolean',
        ]);

        $sendInvitation = ! empty($data['send_invitation']);
        $invitationUrl = null;
        $invitationError = null;

        $customer = DB::transaction(function () use ($data, $sendInvitation, &$invitationUrl, &$invitationError) {
            $customer = Customer::create(collect($data)->except('send_invitation')->all());

            if ($sendInvitation && ! empty($data['contact_email']) && ! empty($data['contact_name'])) {
                // Don't fail customer creation if a duplicate user email exists in the shared DB.
                $existing = CustomerUser::where('email', $data['contact_email'])->first();
                if ($existing) {
                    $invitationError = "A client portal user with email {$data['contact_email']} already exists; no new invitation sent.";

                    return $customer;
                }

                $user = CustomerUser::create([
                    'customer_id' => $customer->id,
                    'name' => $data['contact_name'],
                    'email' => $data['contact_email'],
                    // Random 32-byte placeholder; the user sets a real password through the invitation link.
                    'password' => Str::random(32),
                    'role' => 'admin',
                    'status' => 'inactive',
                ]);

                // Same as provisionPrimaryUser: this is the account owner.
                $this->assignPortalRole($user, 'Owner');

                $invitation = $this->createInvitation($user);
                $invitationUrl = $invitation->url();
            }

            return $customer;
        });

        $flash = ['success' => 'Customer created.'];

        if ($invitationError) {
            $flash['warning'] = $invitationError;
        } elseif ($invitationUrl) {
            $flash['success'] = 'Customer created and invitation sent.';
            $flash['invitation_url'] = $invitationUrl;
        }

        return redirect()->route('customers.show', $customer)->with($flash);
    }

    /**
     * First-time provision flow: customer exists but has no user yet.
     * Uses the customer's contact_name + contact_email to create the primary user,
     * then issues the first invitation.
     */
    public function provisionPrimaryUser(Customer $customer)
    {
        if ($customer->customerUsers()->exists()) {
            return back()->with('warning', 'This customer already has a primary user. Use Resend instead.');
        }

        if (empty($customer->contact_email) || empty($customer->contact_name)) {
            return back()->with('error', 'Cannot provision a primary user: customer is missing contact name or email. Edit the customer first.');
        }

        if (CustomerUser::where('email', $customer->contact_email)->exists()) {
            return back()->with('error', "A client portal user with email {$customer->contact_email} already exists under another customer; no new account created.");
        }

        $user = CustomerUser::create([
            'customer_id' => $customer->id,
            'name' => $customer->contact_name,
            'email' => $customer->contact_email,
            'password' => Str::random(32),
            'role' => 'admin',
            'status' => 'inactive',
        ]);

        // The primary user is the account owner — without a portal role they can
        // log in and see almost nothing, since every client route is permission-gated.
        $this->assignPortalRole($user, 'Owner');

        $invitation = $this->createInvitation($user);

        return back()->with([
            'success' => "Primary user created and invitation sent to {$user->email}.",
            'invitation_url' => $invitation->url(),
        ]);
    }

    /**
     * Invite an additional portal user for a customer.
     *
     * Mirrors the client portal's own Settings → Users invite (inactive account
     * + 14-day token; the invitee sets their own password), but available to NRH
     * staff — useful when the customer has nobody who can reach that screen, or
     * asks us to add someone for them. Unlike provisionPrimaryUser this works
     * whether or not the customer already has users, and the role is chosen
     * rather than assumed to be Owner.
     */
    public function inviteUser(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // customer_users.email is unique across every customer, so a clash
            // may well be an account under a different company.
            'email' => ['required', 'email', 'max:255', Rule::unique('customer_users', 'email')],
            'portal_role' => ['required', 'string', Rule::in(CustomerUser::PORTAL_ROLES)],
        ], [
            'email.unique' => 'A client portal account already exists with that email — possibly under another company. Portal logins are unique by email.',
        ]);

        $user = DB::transaction(function () use ($data, $customer) {
            $user = CustomerUser::create([
                'customer_id' => $customer->id,
                'name' => $data['name'],
                'email' => $data['email'],
                // Placeholder only; the invitation link is where a real password is set.
                'password' => Str::random(32),
                'role' => in_array($data['portal_role'], CustomerUser::ADMIN_PORTAL_ROLES, true) ? 'admin' : 'user',
                'status' => 'inactive',
            ]);

            $this->assignPortalRole($user, $data['portal_role']);

            return $user;
        });

        $invitation = $this->createInvitation($user);

        return back()->with([
            'success' => "{$user->name} invited as {$data['portal_role']}; invitation sent to {$user->email}.",
            'invitation_url' => $invitation->url(),
        ]);
    }

    /**
     * Change a portal user's role. The client portal gates every route on
     * permissions attached to these roles, so this is the lever for fixing a
     * customer who has locked themselves out of their own team management.
     */
    public function updateUserRole(Request $request, Customer $customer, CustomerUser $user)
    {
        abort_unless($user->customer_id === $customer->id, 404);

        $data = $request->validate([
            'portal_role' => ['required', 'string', Rule::in(CustomerUser::PORTAL_ROLES)],
        ]);

        $this->assignPortalRole($user, $data['portal_role']);

        return back()->with('success', "{$user->name} is now {$data['portal_role']}.");
    }

    /**
     * Activate or suspend a portal login. Suspending is the fastest way to cut
     * off access for someone who has left the customer's company.
     */
    public function updateUserStatus(Request $request, Customer $customer, CustomerUser $user)
    {
        abort_unless($user->customer_id === $customer->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        // Never leave a customer with no active owner — they would have no way
        // to manage their own team, and only an admin could undo it.
        if ($data['status'] === 'inactive' && $this->isLastActiveOwner($customer, $user)) {
            return back()->with('error', "{$user->name} is the only active Owner for {$customer->name}. Promote another user to Owner first.");
        }

        $user->update(['status' => $data['status']]);

        return back()->with(
            'success',
            $data['status'] === 'active'
                ? "{$user->name}'s access has been restored."
                : "{$user->name}'s access has been suspended."
        );
    }

    private function isLastActiveOwner(Customer $customer, CustomerUser $user): bool
    {
        if ($user->portalRoleName() !== 'Owner' || ! $user->isActive()) {
            return false;
        }

        return $customer->customerUsers()
            ->with('portalRoles')
            ->where('id', '!=', $user->id)
            ->where('status', 'active')
            ->get()
            ->every(fn (CustomerUser $other) => $other->portalRoleName() !== 'Owner');
    }

    /**
     * Point the user at exactly one portal role, writing Spatie's pivot directly
     * (this app does not ship spatie/laravel-permission) and keeping the legacy
     * `role` column in step so both portals agree on who is an admin.
     */
    private function assignPortalRole(CustomerUser $user, string $roleName): void
    {
        $role = PortalRole::forPortal()->where('name', $roleName)->first();

        if (! $role) {
            Log::warning('Portal role missing; cannot assign', [
                'customer_user_id' => $user->id,
                'role' => $roleName,
            ]);

            return;
        }

        DB::transaction(function () use ($user, $role, $roleName) {
            DB::table('model_has_roles')
                ->where('model_type', CustomerUser::ROLE_MODEL_TYPE)
                ->where('model_id', $user->id)
                ->delete();

            DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => CustomerUser::ROLE_MODEL_TYPE,
                'model_id' => $user->id,
            ]);

            $user->update([
                'role' => in_array($roleName, CustomerUser::ADMIN_PORTAL_ROLES, true) ? 'admin' : 'user',
            ]);
        });

        $user->unsetRelation('portalRoles');
    }

    /**
     * Resend a fresh invitation to a customer user.
     * Marks any previous invitations expired (they'll fail isPending()) and creates a new token.
     */
    public function resendInvitation(Customer $customer, CustomerUser $user)
    {
        if ($user->customer_id !== $customer->id) {
            abort(404);
        }

        if ($user->status === 'active' && $user->latestInvitation?->isAccepted()) {
            return back()->with('warning', "{$user->name} has already accepted their invitation.");
        }

        // Expire previous tokens by setting expires_at to now (a clean revoke).
        $user->invitations()->whereNull('accepted_at')->update(['expires_at' => now()->subSecond()]);

        $invitation = $this->createInvitation($user);

        return back()->with([
            'success' => "Fresh invitation sent to {$user->email}.",
            'invitation_url' => $invitation->url(),
        ]);
    }

    private function createInvitation(CustomerUser $user): CustomerUserInvitation
    {
        $invitation = CustomerUserInvitation::create([
            'customer_user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(14),
            'sent_count' => 1,
            'last_sent_at' => now(),
        ]);

        try {
            Mail::to($user->email)->send(new CustomerUserInvitationMail($invitation));
        } catch (\Throwable $e) {
            // Log but don't fail; admin can copy link manually from the success message.
            Log::warning('Failed to send invitation email', [
                'customer_user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        return $invitation;
    }
}
