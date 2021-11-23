<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitae1d034e8cb1ba9ed268540fef3cec32
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitae1d034e8cb1ba9ed268540fef3cec32::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitae1d034e8cb1ba9ed268540fef3cec32::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitae1d034e8cb1ba9ed268540fef3cec32::$classMap;

        }, null, ClassLoader::class);
    }
}
