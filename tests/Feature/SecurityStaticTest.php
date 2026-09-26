<?php

namespace ImmuniCare\Tests\Feature;

use PHPUnit\Framework\TestCase;

final class SecurityStaticTest extends TestCase
{
    public function testProjectDoesNotUsePdo(): void
    {
        $root = dirname(__DIR__, 2);
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root)
        );

        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $forbidden = 'P' . 'DO';
            self::assertStringNotContainsString(
                'new ' . $forbidden,
                file_get_contents($file->getPathname())
            );
        }
    }

    public function testBodyHasNoFlexDisplayRule(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/style.css');
        self::assertDoesNotMatchRegularExpression(
            '/body[^{}]*\{[^}]*display\s*:\s*flex/is',
            $css
        );
    }
}
