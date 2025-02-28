<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitebf3363f3d127af88645db55c647e611
{
    public static $prefixLengthsPsr4 = array (
        's' => 
        array (
            'setasign\\Fpdi\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'setasign\\Fpdi\\' => 
        array (
            0 => __DIR__ . '/..' . '/setasign/fpdi/src',
        ),
    );

    public static $classMap = array (
        'Clegginabox\\PDFMerger\\PDFMerger' => __DIR__ . '/..' . '/clegginabox/pdf-merger/src/PDFMerger/PDFMerger.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'FPDF' => __DIR__ . '/..' . '/setasign/fpdf/fpdf.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitebf3363f3d127af88645db55c647e611::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitebf3363f3d127af88645db55c647e611::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitebf3363f3d127af88645db55c647e611::$classMap;

        }, null, ClassLoader::class);
    }
}
