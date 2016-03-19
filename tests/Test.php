<?php

namespace Tests;
use \live627\PostFields\Util;

function fatal_error($msg, $log) {
    die($msg);
}

class Test extends \PHPUnit_Framework_TestCase
{
    public $Fields = array();

    public function setFields(array $columns, array $data)
    {
        foreach ($data as $dataRow)
            $this->Fields[] = array_combine(array_keys($columns), $dataRow);
    }

    protected function setUp()
    {
        global $scripturl, $settings, $sourcedir;

        // What are you doing here, SMF?
        if (!defined('SMF')) {
            define('SMF', 1);
        }
        $settings['default_images_url'] = '';
        $settings['images_url'] = '';
        $scripturl = '';
        $sourcedir = __DIR__  . '/../src/live627/PostFields';

        $in_col = array(
            'name' => 'string', 'type' => 'string', 'size' => 'string', 'options' => 'string', 'active' => 'string', 'default_value' => 'string',
            'can_search' => 'string', 'groups' => 'string', 'boards' => 'string', 'topic_only' => 'string', 'bbc' => 'string', 'mask' => 'string',
        );
        $in_data = array(
            array(
                'Notice', 'text', 80, '', 'yes', '',
                'no', '1', '1', 'yes', 'no', 'nohtml',
            ),
            array(
                'When', 'select', 80, 'ASAP,Tomorrow,This Week', 'yes', 'Tomorrow',
                'no', '1', '1', 'yes', 'no', '',
            ),
            array(
                'Price', 'radio', 80, 'Quote Me,Fixed', 'yes', 'Quote Me',
                'no', '1', '1', 'yes', 'yes', '',
            ),
            array(
                '£', 'text', 80, '', 'yes', '',
                'no', '1', '1', 'yes', 'yes', 'float',
            ),
            array(
                'To', 'text', 80, '', 'yes', '',
                'no', '1', '1', 'yes', 'yes', 'email',
            ),
            array(
                'Human', 'check', 80, '', 'yes', '',
                'no', '1', '1', 'yes', 'yes', '',
            ),
            array(
                '$', 'text', 80, '', 'yes', '',
                'no', '1', '1', 'yes', 'no', 'number',
            ),
        );

        $this->setFields($in_col, $in_data);
        $i = 0;
        require_once($sourcedir . '/Class-PostFields.php');
        foreach ($this->Fields as &$field)
        {
            $field['id_field'] = ++$i;
            $field['description'] = '';
            $field['bbc'] = 'no';
            $class_name = '\\live627\\PostFields\\postFields_' . $field['type'];
            $type = new $class_name($field, '', false);
            if (false !== ($value = $type->getValue()))
                $field['value'] = $value;
        }
    }

    public function testExistingFields()
    {
        $i = 0;
        foreach ($this->Fields as $field)
        {
            $actual = (new Util)->renderField($field, '', false);
            $this->assertSame($field['name'], $actual['name']);
        }
    }

    public function testFieldErrors()
    {
        foreach ($this->Fields as $field)
        {
            if (empty($field['value'])) {
                $value = $field['id_field'];
            } else {
                $value = $field['value'];
            }
            if ($field['type'] == 'text') {
                switch ($field['mask']) {
                    case 'regex':
                    $value = '/^def//';
                    break;
                    case 'email':
                    $value = 'live627@gmail.com';
                    break;
                    case 'float':
                    $value = 6.8;
                    break;
                    case 'number':
                    $value = 6;
                    break;
                }
            }
            $class_name = '\\live627\\PostFields\\postFields_' . $field['type'];
            $type = new $class_name($field, $value, !empty($value));
            $type->validate();
            $this->assertFalse($type->getError());
        }
    }

    public function testFieldCount()
    {
        $this->assertCount(7, $this->Fields);
    }
}
