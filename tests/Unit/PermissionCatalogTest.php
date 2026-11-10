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

    public function test_super_admin_has_all_permissions(): void
    {
        $user = new User();
        $user->email = config('role_permissions.super_admin_email');
        $user->setRelation('role', new Role(['permissions' => []]));

        $this->assertTrue(Gate::forUser($user)->allows('an.unlisted.permission'));
    }
}
