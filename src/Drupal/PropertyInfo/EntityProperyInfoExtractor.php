<?php

namespace MakinaCorpus\Dashboard\Drupal\PropertyInfo;

use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Property extractor for the IntItem class, for testing
 */
class EntityProperyInfoExtractor implements PropertyInfoExtractorInterface
{
    /**
     * Get entity type from class
     *
     * @param string $class
     */
    private function getEntityType($class)
    {
        // As of now, we have no other way to determine the entity type from
        // the class, sad but true story
        if (is_a($class, NodeInterface::class, true)) {
            return 'node';
        }
        if (is_a($class, UserInterface::class, true)) {
            return 'user';
        }

        // For all others, in Drupal 7, entities will be \stdClass so we cannot
        // derivate the entity type from the class.
    }

    /**
     * Attempt to guess the property description, knowing that Drupalisms are
     * strong and a lot of people will always use the same colum names
     *
     * @param string $property
     *
     * @param string
     */
    private function getLabelFor($property)
    {
        switch ($property) {

            case 'nid':
                return t("Node ID.");

            case 'vid':
                return t("Revision ID.");

            case 'type':
                return t("Type");

            case 'language':
                return t("Language");

            case 'label':
                return t("Label");

            case 'title':
                return t("Title");

            case 'uid':
                return t("User ID.");

            case 'status':
                return t("Status");

            case 'changed':
                return t("Latest update at");

            case 'created':
                return t("Created at");

            case 'promote':
                return t("Promoted");

            case 'sticky':
                return t("Sticked");

            case 'name':
                return t("Name");

            case 'pass':
                return t("Password");

            case 'mail':
                return t("E-mail");

            case 'theme':
                return t("Theme");

            case 'signature':
                return t("Signature");

            case 'signature_format':
                return t("Signature format");

            case 'access':
                return t("Lastest access at");

            case 'login':
                return t("Lastest login at");

            case 'timezone':
                return t("Timezone");

            case 'description':
                return t("Description");

            case 'format':
                return t("Format");
        }

        return $property;
    }

    /**
     * Convert schema API type to property info type
     *
     * @param string $type
     *
     * @return null|Type
     */
    private function schemaApiTypeToPropertyInfoType($type)
    {
        switch ($type) {

          case 'serial':
          case 'int':
              return new Type(Type::BUILTIN_TYPE_INT, true);

          case 'float':
          case 'numeric':
            return new Type(Type::BUILTIN_TYPE_FLOAT, true);

          case 'varchar':
          case 'text':
          case 'blob':
              return new Type(Type::BUILTIN_TYPE_STRING, true);
        }
    }

    /**
     * Get entity type info for the given class
     *
     * @param string $class
     *
     * @return array
     *   If entity type is found, first key is the entity type, second value
     *   is the entity info array
     */
    private function getEntityTypeInfo($class)
    {
        $entityType = $this->getEntityType($class);

        if (!$entityType) {
            return [null, null];
        }

        $info = entity_get_info($entityType);

        if (!$info) {
            return [null, null];
        }

        return [$entityType, $info];
    }

    /**
     * Guess the while field instances for the given entity type
     *
     * @param string $entityType
     * @param array $entityInfo
     * @param string $property
     *
     * @return null|array
     */
    private function findFieldInstances($entityType, $entityInfo)
    {
        $ret = [];

        // Add field properties, since Drupal does not differenciate entity
        // bundles using different class for different object, we are just
        // gonna give the whole field list
        foreach (field_info_instances($entityType) as $fields) {
            foreach ($fields as $name => $instance) {

                if (isset($ret[$name])) {
                    continue;
                }

                $ret[$name] = $instance;
            }
        }

        return $ret;
    }

    /**
     * Guess the field instance for a single property of the given entity type
     *
     * @param string $entityType
     * @param array $entityInfo
     * @param string $property
     *
     * @return null|array
     */
    private function findFieldInstanceFor($entityType, $entityInfo, $property)
    {
        $instances = $this->findFieldInstances($entityType, $entityInfo);

        if (isset($instances[$property])) {
            return $instances[$property];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getShortDescription($class, $property, array $context = [])
    {
        list($entityType, $entityInfo) = $this->getEntityTypeInfo($class);

        if (!$entityType) {
            return;
        }

        // Property lookup will be faster, do it before trying with fields
        if (isset($entityInfo['base table field types'][$property])) {
            return $this->getLabelFor($property);
        }

        $instance = $this->findFieldInstanceFor($entityType, $entityInfo, $property);

        if ($instance) {
            return isset($instance['label']) ? $instance['label'] : $property;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties($class, array $context = [])
    {
        list($entityType, $entityInfo) = $this->getEntityTypeInfo($class);

        if (!$entityType) {
            return;
        }

        $ret = [];

        // Add defined properties from the entity info array
        if (isset($entityInfo['base table field types'])) {
            foreach ($entityInfo['base table field types'] as $name => $type) {
                $ret[$name] = $name;
            }
        }

        // Add field properties, since Drupal does not differenciate entity
        // bundles using different class for different object, we are just
        // gonna give the whole field list
        foreach ($this->findFieldInstances($entityType, $entityInfo) as $instance) {
            if (isset($instance['label'])) {
                $ret[$name] = $instance['label'];
            } else {
                $ret[$name] = $name;
            }
        }

        // Because Drupal never stop to amaze me, it calls the accessors with
        // the same name as properties, and sometime they're not even accessors
        // and have a different business meaning, await for required arguments,
        // then explode when the PropertyAccessor attempt to call the method
        // as if it was a simple accessor.
        if ('node' === $entityType || 'user' === $entityType) {
            unset($ret['access']);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getLongDescription($class, $property, array $context = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($class, $property, array $context = [])
    {
        list($entityType, $entityInfo) = $this->getEntityTypeInfo($class);

        if (!$entityType) {
            return;
        }

        // Property lookup will be faster, do it before trying with fields
        if (isset($entityInfo['base table field types'][$property])) {
            // We need to use the schema API here, and a bit of guessing
            return [$this->schemaApiTypeToPropertyInfoType($entityInfo['base table field types'][$property])];
        }

        $instance = $this->findFieldInstanceFor($entityType, $entityInfo, $property);

        if ($instance) {
            return new Type(Type::BUILTIN_TYPE_ARRAY, true, null, false);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($class, $property, array $context = [])
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($class, $property, array $context = [])
    {
        return true;
    }
}
