<?php

namespace MakinaCorpus\Dashboard\Tests\Twig;

use MakinaCorpus\Dashboard\Tests\Mock\ContainerAwareTestTrait;
use MakinaCorpus\Dashboard\Tests\Mock\IntItem;
use MakinaCorpus\Dashboard\Twig\PageExtension;
use MakinaCorpus\Dashboard\View\PropertyView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyInfo\Type;
use MakinaCorpus\Dashboard\Error\ConfigurationError;

/**
 * Tests the views
 */
class TwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAwareTestTrait;

    public function displayValue($value)
    {
        if (null === $value) {
            return 'callback called';
        }
        return 'callback called: ' . $value;
    }

    /**
     * Tests PropertyView display
     */
    public function testPageExtensionRenderPropertyView()
    {
        $pageExtension = new PageExtension(
            new RequestStack(),
            $this->createPropertyAccessor(),
            $this->createPropertyInfoExtractor()
        );

        // Property does not exists, declared as virtual, has no callback
        // Debug mode: exception is thrown
        $pageExtension->setDebug(true);
        $propertyView = new PropertyView('foo', null, ['virtual' => true]);
        try {
            $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertTrue(true);
        }

        // Property does not exists, declared as virtual, has no callback
        // Production mode: render not possible
        $pageExtension->setDebug(false);
        $propertyView = new PropertyView('foo', null, ['virtual' => true]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame(PageExtension::RENDER_NOT_POSSIBLE, $output);

        // Reset debug mode: prefer exceptions
        $pageExtension->setDebug(true);

        // Property does not exists, declared as virtual, has a callback: callback is executed
        $propertyView = new PropertyView('foo', null, ['virtual' => true, 'callback' => [$this, 'displayValue']]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame('callback called', $output);
        $this->assertTrue($propertyView->isVirtual());

        // Property exists, declared as virtual, has a callback: callback is executed, value is not accessed
        $propertyView = new PropertyView('id', null, ['virtual' => true, 'callback' => [$this, 'displayValue']]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame("callback called", $output);
        $this->assertTrue($propertyView->isVirtual());

        // Property exists, is not virtual, has no type: type is determined dynamically: displayed properly
        $propertyView = new PropertyView('id', null, ['virtual' => false]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame("1", $output);
        $this->assertFalse($propertyView->isVirtual());

        // Property exists, is not virtual, has a type: displayed property
        $propertyView = new PropertyView('id', new Type(Type::BUILTIN_TYPE_INT));
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame("1", $output);
        $this->assertFalse($propertyView->isVirtual());

        // Property exists, has a type, but there is no value since it's not
        // defined on the item, it should display '' since it's null
        $propertyView = new PropertyView('neverSet');
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame('', $output);
        $this->assertFalse($propertyView->isVirtual());

        // Property does not exists so has no value, has a type, it should just display normally
        $propertyView = new PropertyView('neverSet', new Type(Type::BUILTIN_TYPE_INT));
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame('', $output);
        $this->assertFalse($propertyView->isVirtual());
    }

    /**
     * Tests property display without a property view object
     */
    public function testPageExtensionRenderPropertyRaw()
    {
        $pageExtension = new PageExtension(
            new RequestStack(),
            $this->createPropertyAccessor(),
            $this->createPropertyInfoExtractor()
        );

        // Property does not exists on object, it must return '' and there
        // should not be any exception thrown (since it's null)
        $output = $pageExtension->renderItemProperty(new IntItem(1), 'foo');
        $this->assertSame('', $output);

        // Property exists, and the property info component will be able to
        // find its real type, it must display something
        $output = $pageExtension->renderItemProperty(new IntItem(1), 'id');
        $this->assertSame("1", $output);

        // Same as upper, with an array
        $output = $pageExtension->renderItemProperty(new IntItem(1), 'thousands');
        $this->assertNotEquals(PageExtension::RENDER_NOT_POSSIBLE, $output);
        $this->assertNotEmpty($output);
    }
}
