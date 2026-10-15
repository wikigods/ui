<?php

namespace Laravel\Ui\Tests;

use Illuminate\Support\Facades\Route;
use Laravel\Ui\AuthRouteMethods;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProfileScaffoldingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::mixin(new AuthRouteMethods);
    }

    #[Test]
    public function profile_routes_are_enabled_by_default()
    {
        Route::auth();

        $this->assertTrue(Route::has('profile.edit'));
        $this->assertTrue(Route::has('profile.update'));
        $this->assertTrue(Route::has('profile.password.update'));
        $this->assertTrue(Route::has('profile.destroy'));
    }

    #[Test]
    public function profile_routes_can_be_disabled_explicitly()
    {
        Route::auth(['profile' => false]);

        $this->assertFalse(Route::has('profile.edit'));
    }

    #[Test]
    public function enabled_profile_routes_require_authentication()
    {
        Route::auth();

        foreach (['profile.edit', 'profile.update', 'profile.password.update', 'profile.destroy'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertContains('auth', $route->gatherMiddleware());
        }
    }

    #[Test]
    public function profile_password_route_does_not_replace_password_reset_route()
    {
        Route::auth();

        $this->assertSame(
            ['POST'],
            Route::getRoutes()->getByName('password.update')->methods()
        );
        $this->assertSame(
            ['PUT'],
            Route::getRoutes()->getByName('profile.password.update')->methods()
        );
    }

    #[Test]
    public function auth_scaffolding_exports_profile_view_controller_and_validation_requests()
    {
        $this->artisan('ui:auth', [
            'type' => 'bootstrap',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertFileExists(resource_path('views/profile/edit.blade.php'));
        $this->assertFileExists(app_path('Http/Controllers/Auth/ProfileController.php'));
        $this->assertFileExists(app_path('Http/Requests/ProfileUpdateRequest.php'));
        $this->assertFileExists(app_path('Http/Requests/PasswordUpdateRequest.php'));
        $this->assertFileExists(app_path('Http/Requests/DeleteAccountRequest.php'));

        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString('Auth::routes();', $routes);
    }
}
