<?php

namespace Laravel\Ui\Presets;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

class Vue extends Preset
{
    /**
     * Install the preset.
     *
     * @return void
     */
    public static function install()
    {
        static::ensureComponentDirectoryExists();
        static::updatePackages();
        static::updateViteConfiguration();
        static::updateBootstrapping();
        static::updateComponent();
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
            '@vitejs/plugin-vue' => '^5.2.1',
            'esbuild' => '^0.25.0',
            'sass' => '^1.56.1',
            'sass-embedded' => '^1.80.0',
            'vue' => '^3.5.13',
        ] + static::removeTailwindPackages(Arr::except($packages, [
            '@vitejs/plugin-react',
            'react',
            'react-dom',
        ]));
    }

    /**
     * Update the Vite configuration.
     *
     * @return void
     */
    protected static function updateViteConfiguration()
    {
        copy(__DIR__.'/vue-stubs/vite.config.js', base_path('vite.config.js'));
    }

    /**
     * Update the example component.
     *
     * @return void
     */
    protected static function updateComponent()
    {
        (new Filesystem)->delete(
            resource_path('js/components/Example.js')
        );

        copy(
            __DIR__.'/vue-stubs/ExampleComponent.vue',
            resource_path('js/components/ExampleComponent.vue')
        );
    }

    /**
     * Update the bootstrapping files.
     *
     * @return void
     */
    protected static function updateBootstrapping()
    {
        copy(__DIR__.'/vue-stubs/app.js', resource_path('js/app.js'));
    }
}
