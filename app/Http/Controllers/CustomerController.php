<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerUser;
use App\Models\CustomerUserInvitation;
use App\Mail\CustomerUserInvitationMail;
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
        $query = Customer::withCount(['screeningRequests', 'invoices'])->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('registration_no', 'ilike', "%{$search}%")
                  ->orWhere('contact_email', 'ilike', "%{$search}%");
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
            'invoices' => fn ($q) => $q->latest()->take(10),
            'transactions' => fn ($q) => $q->latest()->take(10),
        ]);
        $recentRequests = $customer->screeningRequests()->with('candidates')->latest()->take(10)->get();

        $stats = [
            'requests_total'   => $customer->screeningRequests()->count(),
            'requests_active'  => $customer->screeningRequests()->whereIn('status', ['new', 'in_progress'])->count(),
            'requests_flagged' => $customer->screeningRequests()->where('status', 'flagged')->count(),
            'invoices_unpaid'  => $customer->invoices()->whereIn('status', ['unpaid', 'overdue'])->count(),
            'team_members'     => $customer->customerUsers->count(),
        ];

        $activeAgreement = $customer->agreements
            ->filter(fn ($a) => $a->expiry_date->isFuture())
            ->sortByDesc('expiry_date')
            ->first();

        $countryFlag = Country::where('name', $customer->country)->value('flag');

        return view('customers.show', compact('customer', 'recentRequests', 'stats', 'activeAgreement', 'countryFlag'));
    }

    public function edit(Customer $customer)
    {
        $countries = Country::orderBy('name')->get();
        return view('customers.edit', compact('customer', 'countries'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'registration_no'=> 'nullable|string|max:100',
            'address'        => 'nullable|string',
            'country'        => 'nullable|string|max:100',
            'industry'       => 'nullable|string|max:100',
            'contact_name'   => 'nullable|string|max:255',
            'contact_email'  => 'nullable|email|max:255',
            'contact_phone'  => 'nullable|string|max:50',
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
            'name'             => 'required|string|max:255',
            'registration_no'  => 'nullable|string|max:100',
            'address'          => 'nullable|string',
            'country'          => 'nullable|string|max:100',
            'industry'         => 'nullable|string|max:100',
            'contact_name'     => 'nullable|string|max:255',
            'contact_email'    => 'nullable|email|max:255',
            'contact_phone'    => 'nullable|string|max:50',
            'send_invitation'  => 'sometimes|boolean',
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
                    'name'        => $data['contact_name'],
                    'email'       => $data['contact_email'],
                    // Random 32-byte placeholder; the user sets a real password through the invitation link.
                    'password'    => Str::random(32),
                    'role'        => 'admin',
                    'status'      => 'inactive',
                ]);

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
            'success'        => "Fresh invitation sent to {$user->email}.",
            'invitation_url' => $invitation->url(),
        ]);
    }

    private function createInvitation(CustomerUser $user): CustomerUserInvitation
    {
        $invitation = CustomerUserInvitation::create([
            'customer_user_id' => $user->id,
            'token'            => Str::random(64),
            'expires_at'       => now()->addDays(14),
            'sent_count'       => 1,
            'last_sent_at'     => now(),
        ]);

        try {
            Mail::to($user->email)->send(new CustomerUserInvitationMail($invitation));
        } catch (\Throwable $e) {
            // Log but don't fail; admin can copy link manually from the success message.
            Log::warning('Failed to send invitation email', [
                'customer_user_id' => $user->id,
                'email'            => $user->email,
                'error'            => $e->getMessage(),
            ]);
        }

        return $invitation;
    }
}
