<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb5ac570b55d0450cea4ad7e8c7df26d6
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'MathPHP\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'MathPHP\\' => 
        array (
            0 => __DIR__ . '/..' . '/markrogoyski/math-php/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb5ac570b55d0450cea4ad7e8c7df26d6::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb5ac570b55d0450cea4ad7e8c7df26d6::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb5ac570b55d0450cea4ad7e8c7df26d6::$classMap;

        }, null, ClassLoader::class);
    }
}
