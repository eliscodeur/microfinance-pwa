<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;

class CreditRouteResolutionTest extends TestCase
{
    public function test_create_url_matches_the_credit_creation_route(): void
    {
        $route = app('router')->getRoutes()->match(
            Request::create('/admin/credits/create', 'GET')
        );

        $this->assertSame('admin.credits.create', $route->getName());
    }

    public function test_credit_approval_routes_require_approval_permission_and_open_cash(): void
    {
        foreach (['admin.credits.approve', 'admin.prets.valider'] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);
            $middleware = $route->gatherMiddleware();

            $this->assertContains('can:credits.approve', $middleware);
            $this->assertContains('cash.open', $middleware);
        }
    }

    public function test_credit_disbursement_route_checks_permission_and_cash_session(): void
    {
        $route = app('router')->getRoutes()->getByName('admin.prets.decaisser');
        $middleware = $route->gatherMiddleware();

        $this->assertContains('permission.feedback:credits.disburse', $middleware);
        $this->assertContains('can:credits.disburse', $middleware);
        $this->assertContains('cash.open', $middleware);
    }
}
