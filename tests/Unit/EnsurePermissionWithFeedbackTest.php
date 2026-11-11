<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsurePermissionWithFeedback;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnsurePermissionWithFeedbackTest extends TestCase
{
    public function test_inertia_request_flashes_a_clear_message_when_permission_is_missing(): void
    {
        $user = new User();
        $user->email = 'cashier@example.test';
        $user->setRelation('role', new Role(['permissions' => ['credits.view']]));

        $session = app('session')->driver('array');
        $session->start();

        $request = Request::create('/admin/prets/42/decaisser', 'POST', [], [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_REFERER' => 'http://localhost/admin/prets/42',
        ]);
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $user);

        $response = (new EnsurePermissionWithFeedback())->handle(
            $request,
            fn () => response('The permission middleware should stop this request.'),
            'credits.disburse'
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            'Votre rôle ne dispose pas de la permission « Décaisser ».',
            $session->get('error')
        );
    }
}
