<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckCashSession;
use App\Models\User;
use App\Services\CashSessionService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class CheckCashSessionTest extends TestCase
{
    public function test_inertia_requests_redirect_back_with_a_flash_error_when_cash_is_closed(): void
    {
        $user = new User();
        $user->id = 42;
        auth()->setUser($user);

        $sessionService = Mockery::mock(CashSessionService::class);
        $sessionService->shouldReceive('hasActiveSession')
            ->once()
            ->with(42)
            ->andReturnFalse();

        $session = app('session')->driver('array');
        $session->start();

        $request = Request::create('/admin/prets/42/valider', 'POST', [], [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_REFERER' => 'http://localhost/admin/prets/42',
        ]);
        $request->setLaravelSession($session);

        $response = (new CheckCashSession($sessionService))->handle(
            $request,
            fn () => response('The cash-session guard should stop this request.')
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            'Opération bloquée : Votre caisse n\'est pas ouverte. Veuillez ouvrir une session de caisse.',
            $session->get('error')
        );
    }
}
