<?php

namespace AlgoWeb\PODataLaravel\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use POData\Providers\Metadata\SimpleMetadataProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema as Schema;

class MetadataProvider extends ServiceProvider
{
    protected static $METANAMESPACE = "Data";

    /**
     * Bootstrap the application services.  Post-boot.
     *
     * @return void
     */
    public function boot()
    {
        self::$METANAMESPACE = env('ODataMetaNamespace', 'Data');
        // If we aren't migrated, there's no DB tables to pull metadata _from_, so bail out early
        try {
            if (!Schema::hasTable('migrations')) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        self::setupRoute();
        $isCaching = env('APP_METADATA_CACHING', false);

        if ($isCaching && Cache::has('metadata')) {
            $meta = Cache::get('metadata');
            $this->app->instance('metadata', $meta);
            return;
        }
        $meta = $this->app->make('metadata');

        $classes = get_declared_classes();
        $AutoClass = null;
        foreach ($classes as $class) {
            if (\Illuminate\Support\Str::startsWith($class, "Composer\\Autoload\\ComposerStaticInit")) {
                $AutoClass = $class;
            }
        }
        $ends = array();
        $Classes = $AutoClass::$classMap;
        foreach ($Classes as $name => $file) {
            if (\Illuminate\Support\Str::startsWith($name, "App")) {
                if (in_array("AlgoWeb\\PODataLaravel\\Models\\MetadataTrait", class_uses($name))) {
                    $ends[] = $name;
                }
            }
        }

        $EntityTypes = array();
        $ResourceSets = array();
        $begins = [];
        $numEnds = count($ends);

        for ($i = 0; $i < $numEnds; $i++) {
            $bitter = $ends[$i];
            $fqModelName = $bitter;

            $instance = new $fqModelName();
            $name = $instance->getEndpointName();
            $metaSchema = $instance->getXmlSchema();
            // if for whatever reason we don't get an XML schema, move on to next entry and drop current one from
            // further processing
            if (null == $metaSchema) {
                continue;
            }
            $EntityTypes[$fqModelName] = $metaSchema;
            $ResourceSets[$fqModelName] = $meta->addResourceSet(
                strtolower($name),
                $EntityTypes[$fqModelName]
            );
            $begins[] = $bitter;
        }

        $ends = $begins;
        unset($begins);

        // now that endpoints are hooked up, tackle the relationships
        // if we'd tried earlier, we'd be guaranteed to try to hook a relation up to null, which would be bad
        foreach ($ends as $bitter) {
            $fqModelName = $bitter;
            $instance = new $fqModelName();
            $instance->hookUpRelationships($EntityTypes, $ResourceSets);
        }
        if ($isCaching) {
            $cacheTime = env('APP_METADATA_CACHE_DURATION', 10);
            $cacheTime = !is_numeric($cacheTime) ? 10 : abs($cacheTime);
            Cache::put('metadata', $meta, $cacheTime);
        } else {
            Cache::forget('metadata');
        }
    }

    private static function setupRoute()
    {
        $valueArray = [];

        Route::any('odata.svc/{section}', 'AlgoWeb\PODataLaravel\Controllers\ODataController@index')
            ->where(['section' => '.*']);
        Route::any('odata.svc', 'AlgoWeb\PODataLaravel\Controllers\ODataController@index');
    }

    /**
     * Register the application services.  Boot-time only.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('metadata', function ($app) {
            return new SimpleMetadataProvider('Data', self::$METANAMESPACE);
        });
    }
}
