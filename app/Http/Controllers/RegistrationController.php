<?php

namespace App\Http\Controllers;

use App\Mail\CustomerRegistrationMail;
use App\Models\AdminAuditLog;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerRegistration;
use App\Models\CustomerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Invite-to-register onboarding. Staff email a prospective customer a link;
 * the invitee registers their company on the client portal (/register/{token});
 * staff review the details here and approve — creating the customer and an
 * active Owner login with the password the invitee chose — or reject.
 */
class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'submitted');

        $query = CustomerRegistration::with('invitedBy')->latest();

        if ($status === 'expired') {
            $query->where('status', 'invited')->where('expires_at', '<', now());
        } elseif ($status === 'invited') {
            $query->where('status', 'invited')->where('expires_at', '>=', now());
        } elseif (in_array($status, CustomerRegistration::STATUSES, true)) {
            $query->where('status', $status);
        } else {
            $status = 'all';
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'ilike', "%{$search}%")
                    ->orWhere('company_name', 'ilike', "%{$search}%")
                    ->orWhere('contact_name', 'ilike', "%{$search}%")
                    ->orWhere('registration_no', 'ilike', "%{$search}%");
            });
        }

        $counts = CustomerRegistration::query()
            ->selectRaw("count(*) filter (where status = 'submitted') as submitted")
            ->selectRaw("count(*) filter (where status = 'invited' and expires_at >= now()) as invited")
            ->selectRaw("count(*) filter (where status = 'invited' and expires_at < now()) as expired")
            ->selectRaw("count(*) filter (where status = 'approved') as approved")
            ->selectRaw("count(*) filter (where status = 'rejected') as rejected")
            ->selectRaw("count(*) filter (where status = 'cancelled') as cancelled")
            ->selectRaw('count(*) as "all"')
            ->first()
            ->toArray();

        return view('registrations.index', [
            'registrations' => $query->paginate(25)->withQueryString(),
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('customer_users', 'email'),
            ],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'invite_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'email.unique' => 'A client portal account already exists with that email.',
        ]);

        $data['email'] = Str::lower($data['email']);

        $open = CustomerRegistration::where('email', $data['email'])
            ->whereIn('status', ['invited', 'submitted'])
            ->first();

        if ($open) {
            throw ValidationException::withMessages([
                'email' => $open->status === 'submitted'
                    ? 'This email has already registered and is awaiting review.'
                    : 'An invitation is already open for this email — use Resend on it instead.',
            ]);
        }

        $registration = new CustomerRegistration($data + [
            'status' => 'invited',
            'invited_by_admin_id' => current_admin()->id,
            'sent_count' => 1,
        ]);
        $token = $registration->issueToken();
        $registration->save();

        AdminAuditLog::record('customer_registration.invited', null, [
            'registration_id' => $registration->id,
            'email' => $registration->email,
        ]);

        $url = CustomerRegistration::urlFor($token);
        $sent = $this->send($registration, 'invite', $url);

        return redirect()->route('registrations.show', $registration)->with($this->linkFlash(
            $sent, "Invitation sent to {$registration->email}.", $url,
        ));
    }

    public function show(CustomerRegistration $registration)
    {
        $registration->load(['invitedBy', 'reviewedBy', 'customer']);

        // Hints for the reviewer — not blockers. A matching company may be a
        // second branch, or the same customer registering twice.
        $similar = collect();
        if ($registration->isAwaitingReview()) {
            $similar = Customer::query()
                ->where(function ($q) use ($registration) {
                    $q->where('name', 'ilike', $registration->company_name);
                    if ($registration->registration_no) {
                        $q->orWhere('registration_no', 'ilike', $registration->registration_no);
                    }
                })
                ->limit(5)
                ->get();
        }

        return view('registrations.show', [
            'registration' => $registration,
            'similar' => $similar,
            'emailTaken' => CustomerUser::where('email', $registration->email)->exists(),
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    public function resend(CustomerRegistration $registration)
    {
        if (! $registration->isOpenInvite()) {
            return back()->with('warning', 'Only an invitation that has not been completed can be resent.');
        }

        $token = $registration->issueToken();
        $registration->sent_count++;
        $registration->save();

        $url = CustomerRegistration::urlFor($token);
        $sent = $this->send($registration, 'invite', $url);

        return back()->with($this->linkFlash(
            $sent, "Fresh invitation sent to {$registration->email}. The previous link no longer works.", $url,
        ));
    }

    public function cancel(CustomerRegistration $registration)
    {
        if (! in_array($registration->status, ['invited', 'submitted'], true)) {
            return back()->with('warning', 'This registration is already closed.');
        }

        $registration->update([
            'status' => 'cancelled',
            'password_hash' => null,
            'reviewed_by_admin_id' => current_admin()->id,
            'reviewed_at' => now(),
        ]);

        AdminAuditLog::record('customer_registration.cancelled', null, [
            'registration_id' => $registration->id,
            'email' => $registration->email,
        ]);

        return back()->with('success', 'Registration cancelled. Its link no longer works.');
    }

    public function approve(Request $request, CustomerRegistration $registration)
    {
        if (! $registration->isAwaitingReview()) {
            return back()->with('warning', 'Only a submitted registration can be approved.');
        }

        // Staff may correct the submitted details before approving.
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'registration_no' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:2000'],
            'country' => ['required', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_designation' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', 'max:50'],
        ]);

        if (CustomerUser::where('email', $registration->email)->exists()) {
            return back()->with('error', "A client portal account already exists with {$registration->email}. Cancel this registration or add the person to that customer instead.");
        }

        $customer = DB::transaction(function () use ($registration, $data) {
            $customer = Customer::create([
                'name' => $data['company_name'],
                'registration_no' => $data['registration_no'],
                'address' => $data['address'],
                'country' => $data['country'],
                'industry' => $data['industry'] ?? null,
                'contact_name' => $data['contact_name'],
                'contact_email' => $registration->email,
                'contact_phone' => $data['contact_phone'],
            ]);

            $user = CustomerUser::create([
                'customer_id' => $customer->id,
                'name' => $data['contact_name'],
                'email' => $registration->email,
                'password' => Str::random(32), // replaced below
                'role' => 'admin',
                'status' => 'active',
            ]);

            // Carry over the password the invitee chose. Written past the
            // model's `hashed` cast, which refuses a hash made with a higher
            // bcrypt cost than this app's own setting.
            DB::table('customer_users')->where('id', $user->id)
                ->update(['password' => $registration->password_hash]);

            if (! $user->assignPortalRole('Owner')) {
                throw new \RuntimeException('The client portal Owner role is missing.');
            }

            $registration->update($data + [
                'status' => 'approved',
                'password_hash' => null,
                'reviewed_by_admin_id' => current_admin()->id,
                'reviewed_at' => now(),
                'customer_id' => $customer->id,
                'customer_user_id' => $user->id,
            ]);

            return $customer;
        });

        AdminAuditLog::record('customer_registration.approved', null, [
            'registration_id' => $registration->id,
            'customer_id' => $customer->id,
            'email' => $registration->email,
        ]);

        $loginUrl = rtrim(config('services.client_portal.url'), '/').'/login';
        $sent = $this->send($registration, 'approved', $loginUrl);

        $flash = ['success' => "{$customer->name} approved — the customer and their Owner login are active. Set up their agreement and scope pricing next."];
        if (! $sent) {
            $flash['warning'] = "The approval email to {$registration->email} could not be sent; let them know they can sign in.";
        }

        return redirect()->route('customers.show', $customer)->with($flash);
    }

    public function reject(Request $request, CustomerRegistration $registration)
    {
        if (! $registration->isAwaitingReview()) {
            return back()->with('warning', 'Only a submitted registration can be rejected.');
        }

        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:2000'],
        ], [
            'review_note.required' => 'Give a reason — it is included in the email to the registrant.',
        ]);

        $registration->update([
            'status' => 'rejected',
            'review_note' => $data['review_note'],
            'password_hash' => null,
            'reviewed_by_admin_id' => current_admin()->id,
            'reviewed_at' => now(),
        ]);

        AdminAuditLog::record('customer_registration.rejected', null, [
            'registration_id' => $registration->id,
            'email' => $registration->email,
        ]);

        $sent = $this->send($registration, 'rejected');

        return back()->with($sent
            ? ['success' => "Registration rejected; {$registration->email} has been emailed the reason."]
            : ['warning' => "Registration rejected, but the email to {$registration->email} could not be sent."]);
    }

    private function send(CustomerRegistration $registration, string $kind, ?string $url = null): bool
    {
        try {
            Mail::to($registration->email)->send(new CustomerRegistrationMail($registration, $kind, $url));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to send registration email', [
                'registration_id' => $registration->id,
                'kind' => $kind,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** Like the customer invites: always surface the link so staff can share it by hand. */
    private function linkFlash(bool $sent, string $message, string $url): array
    {
        return $sent
            ? ['success' => $message, 'invitation_url' => $url]
            : ['warning' => 'The email could not be sent — copy the link below and send it yourself.', 'invitation_url' => $url];
    }
}
