<?php

namespace Tests\Support;

use App\Models\Admin;
use App\Models\Permission;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Row builders for the shared schema.
 *
 * This repo has no model factories (only the unused Laravel UserFactory), and
 * the test database is a structure-only snapshot of production, so every test
 * starts from an empty schema and builds exactly the rows it needs. Each helper
 * fills only the NOT NULL columns plus whatever the caller overrides.
 */
class Fixtures
{
    private static int $seq = 0;

    /** Monotonic suffix so unique columns (email, reference, number) never collide. */
    public static function uniq(string $prefix = ''): string
    {
        return $prefix.(++self::$seq).'-'.substr(bin2hex(random_bytes(4)), 0, 6);
    }

    /** Seed admin_permissions + admin_role_permissions (needed for non-super_admin roles). */
    public static function seedPermissions(): void
    {
        if (Permission::count() === 0) {
            (new PermissionSeeder)->run();
        }
    }

    public static function admin(array $attrs = []): Admin
    {
        return Admin::create(array_merge([
            'name' => 'Test Admin',
            'email' => self::uniq('admin').'@example.test',
            'password' => 'secret-password',
            'role' => 'super_admin',
            'status' => 'active',
        ], $attrs));
    }

    public static function country(array $attrs = []): int
    {
        return DB::table('countries')->insertGetId(array_merge([
            'name' => 'Malaysia',
            'code' => strtoupper(substr(self::uniq(), 0, 3)),
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    public static function identityType(array $attrs = []): int
    {
        return DB::table('identity_types')->insertGetId(array_merge([
            'name' => 'MyKad',
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    public static function customer(array $attrs = []): int
    {
        return DB::table('customers')->insertGetId(array_merge([
            'name' => 'Acme Sdn Bhd '.self::uniq(),
            'contact_name' => 'Contact Person',
            'contact_email' => self::uniq('contact').'@example.test',
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    public static function customerUser(int $customerId, array $attrs = []): int
    {
        return DB::table('customer_users')->insertGetId(array_merge([
            'customer_id' => $customerId,
            'name' => 'Client User',
            'email' => self::uniq('user').'@example.test',
            'password' => bcrypt('secret-password'),
            'role' => 'admin',
            'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    /** $billing: 'monthly' (post-pay) or 'per_request' (cash / upfront). */
    public static function agreement(int $customerId, string $billing = 'monthly', array $attrs = []): int
    {
        return DB::table('agreements')->insertGetId(array_merge([
            'customer_id' => $customerId,
            'type' => 'standard',
            'start_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'sla_tat' => '5 days',
            'billing' => $billing,
            'payment' => 'bank_transfer',
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    public static function scopeType(int $countryId, array $attrs = []): int
    {
        return DB::table('scope_types')->insertGetId(array_merge([
            'country_id' => $countryId,
            'name' => 'Crime Risk Integrity '.self::uniq(),
            'turnaround' => '3 days',
            'price' => 100.00,
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    public static function screeningRequest(int $customerId, int $customerUserId, array $attrs = []): int
    {
        return DB::table('screening_requests')->insertGetId(array_merge([
            'customer_id' => $customerId,
            'customer_user_id' => $customerUserId,
            'reference' => 'REQ-'.now()->format('Y').'-'.self::uniq(),
            'status' => 'new',
            'type' => 'employment_global',
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    public static function candidate(int $requestId, int $identityTypeId, array $attrs = []): int
    {
        return DB::table('request_candidates')->insertGetId(array_merge([
            'screening_request_id' => $requestId,
            'identity_type_id' => $identityTypeId,
            'name' => 'Candidate Name',
            'identity_number' => '900101-01-'.random_int(1000, 9999),
            'status' => 'new',
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    public static function attachScope(int $candidateId, int $scopeTypeId, array $attrs = []): void
    {
        DB::table('candidate_scope_type')->insert(array_merge([
            'request_candidate_id' => $candidateId,
            'scope_type_id' => $scopeTypeId,
            'status' => 'new',
            'assigned_at' => now(),
        ], $attrs));
    }

    public static function invoice(int $customerId, array $attrs = []): int
    {
        return DB::table('invoices')->insertGetId(array_merge([
            'customer_id' => $customerId,
            'number' => 'INV-'.now()->format('Y').'-'.self::uniq(),
            'period' => now()->format('F Y'),
            'status' => 'unpaid',
            'issued_at' => now()->toDateString(),
            'due_at' => now()->addDays(30)->toDateString(),
            'subtotal' => 100.00,
            'tax' => 6.00,
            'total' => 106.00,
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    public static function receipt(int $invoiceId, array $attrs = []): int
    {
        return DB::table('invoice_payment_receipts')->insertGetId(array_merge([
            'invoice_id' => $invoiceId,
            'file_path' => 'receipts/test-'.self::uniq().'.pdf',
            'file_name' => 'receipt.pdf',
            'status' => 'pending',
            'amount_claimed' => 106.00,
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    /** Log in as an admin by populating the custom session keys AdminAuth checks. */
    public static function actingAs($test, Admin $admin)
    {
        // current_admin() memoises in the container; clear it between logins.
        app()->forgetInstance('current_admin');

        return $test->withSession([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'admin_role' => $admin->role,
            'admin_email' => $admin->email,
        ]);
    }
}
