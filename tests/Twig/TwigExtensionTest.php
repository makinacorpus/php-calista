<?php

namespace MakinaCorpus\Dashboard\Tests\Twig;

use MakinaCorpus\Dashboard\Tests\Mock\ContainerAwareTestTrait;
use MakinaCorpus\Dashboard\Tests\Mock\IntItem;
use MakinaCorpus\Dashboard\Twig\PageExtension;
use MakinaCorpus\Dashboard\View\PropertyView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyInfo\Type;

/**
 * Tests the views
 */
class TwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAwareTestTrait;

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

        // Property does not exists on object, it must return '' and there
        // should not be any exception thrown
        $propertyView = new PropertyView('foo', true, null, []);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame('', $output);

        // Property exists, but has no given type nor callback, it should
        // display "N/A" to raise the error
        $propertyView = new PropertyView('id', true, null, []);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame(PageExtension::RENDER_NOT_POSSIBLE, $output);

        // Property exists, has a type, but there is no value since it's not
        // defined on the item, it should display '' since it's null
        $propertyView = new PropertyView('nope', true, new Type(Type::BUILTIN_TYPE_INT), []);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame('', $output);

        // Property exists, has a type, it should just display normally
        $propertyView = new PropertyView('nope', true, new Type(Type::BUILTIN_TYPE_INT), []);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame('', $output);
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
