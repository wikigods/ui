<?php

namespace Laravel\Ui\Presets;

use Illuminate\Filesystem\Filesystem;

class Bootstrap extends Preset
{
    /**
     * Install the preset.
     *
     * @return void
     */
    public static function install()
    {
        static::updatePackages();
        static::updateViteConfiguration();
        static::updateSass();
        static::updateBootstrapping();
        static::updateDefaultWelcomePage();
        static::removeNodeModules();
    }

    /**
     * Update the given package array.
     *
     * @param  array  $packages
     * @return array
     */
    protected static function updatePackageArray(array $packages)
    {
        return [
            'bootstrap' => '^5.2.3',
            '@popperjs/core' => '^2.11.6',
            'esbuild' => '^0.25.0',
            'sass' => '^1.56.1',
            'sass-embedded' => '^1.80.0',
        ] + static::removeTailwindPackages($packages);
    }

    /**
     * Update the Vite configuration.
     *
     * @return void
     */
    protected static function updateViteConfiguration()
    {
        copy(__DIR__.'/bootstrap-stubs/vite.config.js', base_path('vite.config.js'));
    }

    /**
     * Update the Sass files for the application.
     *
     * @return void
     */
    protected static function updateSass()
    {
        (new Filesystem)->ensureDirectoryExists(resource_path('sass'));

        copy(__DIR__.'/bootstrap-stubs/_variables.scss', resource_path('sass/_variables.scss'));
        copy(__DIR__.'/bootstrap-stubs/app.scss', resource_path('sass/app.scss'));
    }

    /**
     * Update the bootstrapping files.
     *
     * @return void
     */
    protected static function updateBootstrapping()
    {
        copy(__DIR__.'/bootstrap-stubs/bootstrap.js', resource_path('js/bootstrap.js'));
    }

    /**
     * Replace Laravel's stock Tailwind welcome page with the Bootstrap version.
     *
     * Custom welcome pages are left untouched.
     *
     * @return void
     */
    protected static function updateDefaultWelcomePage()
    {
        $welcome = resource_path('views/welcome.blade.php');

        if (! is_file($welcome)) {
            return;
        }

        $contents = file_get_contents($welcome);
        $tailwindStylesheet = resource_path('css/app.css');

        if (! str_contains($contents, 'resources/css/app.css') ||
            ! str_contains($contents, 'tailwindcss v4') ||
            ! is_file($tailwindStylesheet) ||
            ! str_contains(file_get_contents($tailwindStylesheet), "@import 'tailwindcss'")) {
            return;
        }

        copy(__DIR__.'/bootstrap-stubs/welcome.blade.php', $welcome);

        file_put_contents($tailwindStylesheet, "/* Bootstrap styles are loaded from resources/sass/app.scss. */".PHP_EOL);
    }
}
