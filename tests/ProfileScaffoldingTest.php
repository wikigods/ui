<?php

namespace Laravel\Ui\Tests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Filesystem\Filesystem;
use Laravel\Ui\UiServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProfileScaffoldingTest extends TestCase
{
    protected ?string $scaffoldPath = null;

    protected function getPackageProviders($app)
    {
        return [UiServiceProvider::class];
    }

    #[Test]
    public function profile_routes_are_enabled_by_default()
    {
        Auth::routes();
        $this->refreshRouteNameLookups();

        $this->assertTrue(Route::has('profile.edit'));
        $this->assertTrue(Route::has('profile.update'));
        $this->assertTrue(Route::has('profile.password.update'));
        $this->assertTrue(Route::has('profile.destroy'));
        $this->assertTrue(Route::has('profile.two-factor.enable'));
        $this->assertTrue(Route::has('profile.two-factor.confirm'));
        $this->assertTrue(Route::has('profile.two-factor.recovery-codes'));
        $this->assertTrue(Route::has('profile.two-factor.disable'));
        $this->assertTrue(Route::has('profile.sessions.destroy'));
        $this->assertTrue(Route::has('two-factor.login'));
        $this->assertTrue(Route::has('two-factor.authenticate'));
    }

    #[Test]
    public function profile_routes_can_be_disabled_explicitly()
    {
        Auth::routes(['profile' => false]);
        $this->refreshRouteNameLookups();

        $this->assertFalse(Route::has('profile.edit'));
    }

    #[Test]
    public function two_factor_routes_can_be_disabled_explicitly()
    {
        Auth::routes(['twoFactor' => false]);
        $this->refreshRouteNameLookups();

        $this->assertFalse(Route::has('two-factor.login'));
        $this->assertFalse(Route::has('two-factor.authenticate'));
        $this->assertFalse(Route::has('profile.two-factor.enable'));
        $this->assertFalse(Route::has('profile.two-factor.confirm'));
    }

    #[Test]
    public function enabled_profile_routes_require_authentication()
    {
        Auth::routes();
        $this->refreshRouteNameLookups();

        foreach ([
            'profile.edit',
            'profile.update',
            'profile.password.update',
            'profile.destroy',
            'profile.two-factor.enable',
            'profile.two-factor.confirm',
            'profile.two-factor.recovery-codes',
            'profile.two-factor.disable',
            'profile.sessions.destroy',
        ] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertContains('auth', $route->gatherMiddleware());
        }
    }

    #[Test]
    public function the_two_factor_challenge_is_throttled()
    {
        Auth::routes();
        $this->refreshRouteNameLookups();

        $route = Route::getRoutes()->getByName('two-factor.authenticate');

        $this->assertNotNull($route);
        $this->assertContains('throttle:5,1', $route->middleware());
    }

    #[Test]
    public function profile_password_route_does_not_replace_password_reset_route()
    {
        Auth::routes();
        $this->refreshRouteNameLookups();

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
        $this->scaffoldPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'laravel-ui-auth-'.bin2hex(random_bytes(6));
        mkdir($this->scaffoldPath, 0755, true);
        file_put_contents(
            $this->scaffoldPath.DIRECTORY_SEPARATOR.'composer.json',
            json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]])
        );

        $this->app->setBasePath($this->scaffoldPath);
        $this->app->useAppPath($this->scaffoldPath.DIRECTORY_SEPARATOR.'app');
        $this->app->useDatabasePath($this->scaffoldPath.DIRECTORY_SEPARATOR.'database');
        $this->app->instance('path.resources', $this->scaffoldPath.DIRECTORY_SEPARATOR.'resources');
        $this->app['config']->set('view.paths', [$this->scaffoldPath.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views']);
        mkdir($this->scaffoldPath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations', 0755, true);
        file_put_contents(
            $this->scaffoldPath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'0001_01_01_000000_create_users_table.php',
            '<?php // Existing application users migration.'
        );
        mkdir($this->scaffoldPath.DIRECTORY_SEPARATOR.'routes', 0755, true);
        file_put_contents($this->scaffoldPath.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php', "<?php\n");

        $this->artisan('ui:auth', [
            'type' => 'bootstrap',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertFileExists(resource_path('views/profile/edit.blade.php'));
        $this->assertFileExists(app_path('Http/Controllers/Auth/ProfileController.php'));
        $this->assertFileExists(app_path('Http/Requests/ProfileUpdateRequest.php'));
        $this->assertFileExists(app_path('Http/Requests/PasswordUpdateRequest.php'));
        $this->assertFileExists(app_path('Http/Requests/DeleteAccountRequest.php'));
        $this->assertFileExists(app_path('Http/Requests/ConfirmCurrentPasswordRequest.php'));
        $this->assertFileExists(app_path('Http/Requests/TwoFactorConfirmationRequest.php'));
        $this->assertFileExists(app_path('Http/Requests/TwoFactorLoginRequest.php'));
        $this->assertFileExists(app_path('Http/Controllers/Auth/TwoFactorChallengeController.php'));
        $this->assertFileExists(resource_path('views/auth/two-factor-challenge.blade.php'));
        $this->assertFileExists(database_path('migrations/2026_10_15_000000_add_two_factor_fields_to_users_table.php'));
        $this->assertFileExists(database_path('migrations/2026_10_15_000001_create_sessions_table.php'));

        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString('Auth::routes();', $routes);
    }

    protected function refreshRouteNameLookups(): void
    {
        Route::getRoutes()->refreshNameLookups();
    }

    protected function tearDown(): void
    {
        if ($this->scaffoldPath !== null) {
            (new Filesystem)->deleteDirectory($this->scaffoldPath);
        }

        parent::tearDown();
    }
}
