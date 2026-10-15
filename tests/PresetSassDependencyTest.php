<?php

namespace Laravel\Ui\Tests;

use Laravel\Ui\Presets\Bootstrap;
use Laravel\Ui\Presets\React;
use Laravel\Ui\Presets\Vue;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PresetSassDependencyTest extends TestCase
{
    #[Test]
    public function bootstrap_preset_installs_sass_and_minifiers()
    {
        $packages = $this->updatedPackages(Bootstrap::class, []);

        $this->assertSame('^1.56.1', $packages['sass']);
        $this->assertSame('^1.80.0', $packages['sass-embedded']);
        $this->assertSame('^0.25.0', $packages['esbuild']);
    }

    #[Test]
    public function vue_preset_installs_sass_for_its_scss_entrypoint()
    {
        $packages = $this->updatedPackages(Vue::class, [
            'vite' => '^8.0.0',
        ]);

        $this->assertSame('^1.56.1', $packages['sass']);
        $this->assertSame('^1.80.0', $packages['sass-embedded']);
        $this->assertSame('^0.25.0', $packages['esbuild']);
        $this->assertSame('^8.0.0', $packages['vite']);
    }

    #[Test]
    public function react_preset_installs_sass_for_its_scss_entrypoint()
    {
        $packages = $this->updatedPackages(React::class, [
            'vite' => '^8.0.0',
        ]);

        $this->assertSame('^1.56.1', $packages['sass']);
        $this->assertSame('^1.80.0', $packages['sass-embedded']);
        $this->assertSame('^0.25.0', $packages['esbuild']);
        $this->assertSame('^8.0.0', $packages['vite']);
    }

    #[Test]
    public function every_preset_configures_sass_and_css_minification()
    {
        foreach (['bootstrap', 'vue', 'react'] as $preset) {
            $config = file_get_contents(
                dirname(__DIR__).'/src/Presets/'.$preset.'-stubs/vite.config.js'
            );

            $this->assertStringContainsString("api: 'modern-compiler'", $config);
            $this->assertStringContainsString("'color-functions'", $config);
            $this->assertStringContainsString("'global-builtin'", $config);
            $this->assertStringContainsString("'import'", $config);
            $this->assertStringContainsString("'if-function'", $config);
            $this->assertStringContainsString("cssMinify: 'esbuild'", $config);
        }
    }

    #[Test]
    public function presets_remove_tailwind_dependencies_but_keep_other_packages()
    {
        foreach ([Bootstrap::class, Vue::class, React::class] as $preset) {
            $packages = [
                '@tailwindcss/vite' => '^4.0.0',
                '@tailwindcss/postcss' => '^4.0.0',
                '@tailwindcss/cli' => '^4.0.0',
                '@tailwindcss/typography' => '^0.5.0',
                'tailwindcss' => '^4.0.0',
                'concurrently' => '^10.0.3',
                'vite' => '^8.0.0',
            ];

            $method = new ReflectionMethod($preset, 'removeTailwindPackages');
            $method->setAccessible(true);
            $updated = $method->invoke(null, $packages);

            $this->assertArrayNotHasKey('tailwindcss', $updated);
            $this->assertArrayNotHasKey('@tailwindcss/vite', $updated);
            $this->assertArrayNotHasKey('@tailwindcss/postcss', $updated);
            $this->assertArrayNotHasKey('@tailwindcss/cli', $updated);
            $this->assertArrayNotHasKey('@tailwindcss/typography', $updated);
            $this->assertSame('^10.0.3', $updated['concurrently']);
            $this->assertSame('^8.0.0', $updated['vite']);
        }
    }

    #[Test]
    public function bootstrap_welcome_stub_uses_bootstrap_assets_without_tailwind()
    {
        $welcome = file_get_contents(dirname(__DIR__).'/src/Presets/bootstrap-stubs/welcome.blade.php');

        $this->assertStringContainsString("@vite(['resources/sass/app.scss', 'resources/js/app.js'])", $welcome);
        $this->assertStringContainsString('class="container py-5"', $welcome);
        $this->assertStringContainsString('class="card h-100', $welcome);
        $this->assertStringNotContainsString('tailwind', strtolower($welcome));
    }

    /**
     * @param  class-string  $preset
     * @param  array<string, string>  $packages
     * @return array<string, string>
     */
    protected function updatedPackages(string $preset, array $packages): array
    {
        $method = new ReflectionMethod($preset, 'updatePackageArray');
        $method->setAccessible(true);

        return $method->invoke(null, $packages);
    }
}
