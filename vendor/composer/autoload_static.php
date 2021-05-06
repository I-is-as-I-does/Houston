<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc412584ef39955aea15a4d663cbb3b4e
{
    public static $prefixLengthsPsr4 = array (
        'E' => 
        array (
            'ExoProject\\Houston\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ExoProject\\Houston\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc412584ef39955aea15a4d663cbb3b4e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc412584ef39955aea15a4d663cbb3b4e::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitc412584ef39955aea15a4d663cbb3b4e::$classMap;

        }, null, ClassLoader::class);
    }
}
