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
                'Price', 'select', 80, 'Quote Me,Fixed', 'yes', '',
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
                'From', 'text', 80, '', 'yes', '',
                'yes', '1', '1', 'yes', 'yes', 'nohtml',
            ),
            array(
                '$', 'text', 80, '', 'yes', '',
                'no', '1', '1', 'yes', 'no', 'number',
            ),
            array(
                'Contact me via', 'select', 80, 'Mobile,Email,Personal Message,Telephone', 'yes', '',
                'no', '1', '1', 'yes', 'no', '',
            ),
        );

        $this->setFields($in_col, $in_data);
    }

    public function testExistingFields()
    {
        global $scripturl, $settings, $sourcedir;

        define('SMF', 1);
        $settings['default_images_url'] = '';
        $settings['images_url'] = '';
        $scripturl = '';
        $sourcedir = __DIR__  . '/../src/live627/PostFields';
        $i = 0;

        foreach ($this->Fields as $field)
        {
            $field['id_field'] = ++$i;
            $field['description'] = '';
            $field['bbc'] = 'no';
            $actual = (new Util)->renderField($field, '', false);
            $this->assertSame($field['name'], $actual['name']);
        }
    }

    public function testFieldErrors()
    {
        $i = 0;

        foreach ($this->Fields as $field)
        {
            $field['id_field'] = ++$i;
            $value = $field['id_field'];
            switch ($field['mask']) {
                case 'regex':
                $value = '/^def//';
                case 'email':
                $value = 'live627@gmail.com';
            }
            $class_name = '\\live627\\PostFields\\postFields_' . $field['type'];
            $type = new $class_name($field, $value, !empty($value));
            $type->validate();
            $this->assertFalse($type->getError());
        }
    }

    public function testFieldCount()
    {
        $this->assertCount(8, $this->Fields);
    }
}
