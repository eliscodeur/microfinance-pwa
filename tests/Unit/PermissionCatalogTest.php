<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PermissionCatalogTest extends TestCase
{
    public function test_legacy_role_permissions_expand_to_their_previous_access(): void
    {
        $permissions = PermissionCatalog::expand(['Gérer Crédits']);

        $this->assertContains('credits.view', $permissions);
        $this->assertContains('credits.approve', $permissions);
        $this->assertContains('credits.disburse', $permissions);
        $this->assertContains('credits.payments', $permissions);
    }

    public function test_roles_can_store_granular_permissions_without_legacy_labels(): void
    {
        $permissions = PermissionCatalog::expand(['credits.view', 'credits.approve']);

        $this->assertContains('credits.view', $permissions);
        $this->assertContains('credits.approve', $permissions);
        $this->assertNotContains('Gérer Crédits', $permissions);
    }

    public function test_granular_role_permissions_do_not_grant_other_actions(): void
    {
        $user = new User();
        $user->email = 'agent@example.test';
        $user->setRelation('role', new Role(['permissions' => ['credits.view']]));

        $this->assertTrue(Gate::forUser($user)->allows('credits.view'));
        $this->assertFalse(Gate::forUser($user)->allows('credits.approve'));
    }

    public function test_legacy_payroll_management_permission_preserves_existing_access(): void
    {
        $permissions = PermissionCatalog::expand(['payroll.manage']);

        $this->assertContains('payroll.prepare', $permissions);
        $this->assertContains('payroll.approve', $permissions);
        $this->assertContains('payroll.reject', $permissions);
        $this->assertContains('payroll.pay', $permissions);
        $this->assertContains('advances.disburse', $permissions);
    }

    public function test_payroll_preparation_does_not_grant_approval_or_payment(): void
    {
        $user = new User();
        $user->email = 'payroll@example.test';
        $user->setRelation('role', new Role(['permissions' => ['payroll.prepare']]));

        $this->assertTrue(Gate::forUser($user)->allows('payroll.prepare'));
        $this->assertFalse(Gate::forUser($user)->allows('payroll.approve'));
        $this->assertFalse(Gate::forUser($user)->allows('payroll.pay'));
    }

    public function test_performance_view_and_export_permissions_are_separate(): void
    {
        $user = new User();
        $user->email = 'performance@example.test';
        $user->setRelation('role', new Role(['permissions' => ['performance.view']]));

        $this->assertTrue(Gate::forUser($user)->allows('performance.view'));
        $this->assertFalse(Gate::forUser($user)->allows('performance.export'));
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $user = new User();
        $user->email = config('role_permissions.super_admin_email');
        $user->setRelation('role', new Role(['permissions' => []]));

        $this->assertTrue(Gate::forUser($user)->allows('an.unlisted.permission'));
    }
}
