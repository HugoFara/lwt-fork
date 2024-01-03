<?php declare(strict_types=1);

require_once __DIR__ . '/../../inc/kernel_utility.php';

use PHPUnit\Framework\TestCase;

final class KernelUtilityTest extends TestCase
{
    
    /**
     * Test the display of version as a string
     */
    public function testGetVersion(): void
    {
        $version = get_version();
        $this->assertIsString($version);
    }

    /**
     * Test the correct format of version as v{3-digit MAJOR}{3-digit MINOR}{3-digit PATCH}
     */
    public function testGetVersionNumber(): void 
    {
        $version = get_version_number();
        $this->assertTrue(str_starts_with($version, 'v'));
        $this->assertSame(10, strlen($version));
    }

    /**
     * Test if the language from dictionary feature is properly working.
     */
    public function testLangFromDict(): void
    {
        $urls = [
            'http://translate.google.com/lwt_term?ie=UTF-8&sl=ar&tl=en&text=&lwt_popup=true',
            'http://localhost/lwt/ggl.php/?sl=ar&tl=hr&text=',
            'http://localhost:5000/?lwt_translator=libretranslate&source=ar&target=en&q=lwt_term',
            'ggl.php?sl=ar&tl=en&text=###'
        ];
        foreach ($urls as $url) {
            $this->assertSame("ar", langFromDict($url));
        }
    }

}
