<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\ConditionalField\Choices;

use Mautic\FormBundle\Model\FieldModel;

class ChoicesFactory
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * PropertiesProcessor constructor.
     *
     * @param FieldModel $fieldModel
     */
    public function __construct(FieldModel $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function getChoicesFromFields(array $fields)
    {
        return $this->getChoicesFromArray($fields);
    }

    /**
     * @param Field $field
     */
    public function getChoicesFromField(Field $field)
    {
    }

    /**
     * @param array $field
     *
     * @return array|mixed
     */
    public function getOptionsFromField(array $field)
    {
        if (!empty($field['leadField']) && !empty($field['properties']['syncList'])) {
            $contactFields = $this->fieldModel->getObjectFields('Lead');
            foreach ($contactFields as $contactField) {
                if ($contactField['alias'] === $field['leadField']) {
                    return $this->getOptionsFromProperties($contactField['properties']);
                }
            }
        } elseif (!empty($field['properties'])) {
            return $this->getOptionsFromProperties($field['properties']);
        }

        return [];
    }

    /**
     * @return array
     */
    public function getChoicesFromArray(array $options)
    {
        $choices = [];
        foreach ($options as $option) {
            if (is_array($option)) {
                if (isset($option['label']) && isset($option['alias'])) {
                    $choices[$option['alias']] = $option['label'];
                } elseif (isset($option['label']) && isset($option['value'])) {
                    $choices[$option['value']] = $option['label'];
                } else {
                    foreach ($option as $group => $opt) {
                        $choices[$opt] = $opt;
                    }
                }
            } else {
                $choices[$option] = $option;
            }
        }

        return $choices;
    }

    /**
     * @param array $properties
     *
     * @return array|mixed
     */
    private function getOptionsFromProperties(array $properties)
    {
        if (!empty($properties['list']['list'])) {
            return $properties['list']['list'];
        } elseif (!empty($properties['optionlist']['list'])) {
            return $properties['optionlist']['list'];
        }

        return [];
    }
}
