<?php

namespace MakinaCorpus\Dashboard\Drupal\PropertyInfo;

use Drupal\Core\Entity\EntityInterface;

/**
 * This is basically a hack that will allow property info to access Drupal's
 * entity weird array structure via the ProperyInfo component, thus allowing
 * field introspection as builtin types, so we can render them.
 */
class EntityField
{
    const LIST_VIEW_MODE = 'udashboard_property';

    /**
     * Render field value
     *
     * @param string $value
     * @param array $options
     * @param mixed $item
     *
     * @return string
     */
    static public function renderField($value, array $options, $item)
    {
        if (empty($options['name'])) {
            return '';
        }
        if (empty($value) || !is_array($value)) {
            return '';
        }

        if (!$item instanceof EntityInterface) {
            return '';
        }

        $output = field_view_field($item->getEntityTypeId(), $item, $options['name'], self::LIST_VIEW_MODE);

        return drupal_render($output);
    }

    /**
     * Render timestamp as date
     *
     * @param int $value
     *
     * @return string
     */
    static public function renderTimestampAsDate($value)
    {
        return $value ? format_date($value) : t("Never");
    }

    /**
     * Render timestamp as interval
     *
     * @param int $value
     *
     * @return string
     */
    static public function renderTimestampAsInterval($value)
    {
        return $value ? format_interval(time() - $value) : t("Never");
    }
}
