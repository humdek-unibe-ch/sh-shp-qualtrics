<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6e0fddd3de90bb45f8b8deee6fb58bd7
{
    public static $prefixLengthsPsr4 = array (
        'm' => 
        array (
            'mikehaertl\\tmp\\' => 15,
            'mikehaertl\\shellcommand\\' => 24,
            'mikehaertl\\pdftk\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'mikehaertl\\tmp\\' => 
        array (
            0 => __DIR__ . '/..' . '/mikehaertl/php-tmpfile/src',
        ),
        'mikehaertl\\shellcommand\\' => 
        array (
            0 => __DIR__ . '/..' . '/mikehaertl/php-shellcommand/src',
        ),
        'mikehaertl\\pdftk\\' => 
        array (
            0 => __DIR__ . '/..' . '/mikehaertl/php-pdftk/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6e0fddd3de90bb45f8b8deee6fb58bd7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6e0fddd3de90bb45f8b8deee6fb58bd7::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
