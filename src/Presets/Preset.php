<?php

namespace Laravel\Ui\Presets;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Preset
{
    /**
     * Remove Tailwind packages from the application's package manifest.
     *
     * @param  array<string, string>  $packages
     * @return array<string, string>
     */
    protected static function removeTailwindPackages(array $packages)
    {
        foreach (array_keys($packages) as $package) {
            if ($package === 'tailwindcss' || Str::startsWith($package, '@tailwindcss/')) {
                unset($packages[$package]);
            }
        }

        return $packages;
    }

    /**
     * Ensure the component directories we need exist.
     *
     * @return void
     */
    protected static function ensureComponentDirectoryExists()
    {
        $filesystem = new Filesystem;

        if (! $filesystem->isDirectory($directory = resource_path('js/components'))) {
            $filesystem->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Update the "package.json" file.
     *
     * @param  bool  $dev
     * @return void
     */
    protected static function updatePackages($dev = true)
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = static::updatePackageArray(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * Remove the installed Node modules.
     *
     * @return void
     */
    protected static function removeNodeModules()
    {
        tap(new Filesystem, function ($files) {
            $files->deleteDirectory(base_path('node_modules'));

            $files->delete(base_path('yarn.lock'));
        });
    }
}
