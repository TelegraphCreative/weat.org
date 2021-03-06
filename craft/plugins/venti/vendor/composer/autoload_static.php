<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita94ca211f1506685025c8b6ca6829bf4
{
    public static $prefixesPsr0 = array (
        'R' => 
        array (
            'Recurr' => 
            array (
                0 => __DIR__ . '/..' . '/simshaun/recurr/src',
            ),
        ),
        'D' => 
        array (
            'Doctrine\\Common\\Collections\\' => 
            array (
                0 => __DIR__ . '/..' . '/doctrine/collections/lib',
            ),
        ),
    );

    public static $classMap = array (
        'Mexitek\\PHPColors\\Color' => __DIR__ . '/..' . '/mexitek/phpcolors/src/Mexitek/PHPColors/Color.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInita94ca211f1506685025c8b6ca6829bf4::$prefixesPsr0;
            $loader->classMap = ComposerStaticInita94ca211f1506685025c8b6ca6829bf4::$classMap;

        }, null, ClassLoader::class);
    }
}
