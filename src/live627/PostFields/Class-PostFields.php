<?php

/**
 * @package PostFields
 * @version 2.0
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2011-2016, John Rayes
 * @license http://opensource.org/licenses/ISC ISC
 */

namespace live627\PostFields;

if (!defined('SMF')) {
	die('Hacking attempt...');
}

interface postFields
{
	/*
	 * Constructs the field.
	 *
	 * @param array $field The field as returned by {@link total_getPostFields()}.
	 * @param string $value Field value.
	 * @param bool $exists Whether the value exists/is not empty.
	 * @access public
	 * @return void
	 */
	public function __construct($field, $value, $exists);

	/*
	 * Sets the input so the user can enter a value.
	 * Sets the output.
	 *
	 * @access public
	 * @return void
	 */
	public function setHtml();
	function validate();
}

abstract class postFieldsBase implements postFields
{
	public $input_html;
	public $output_html;
	protected $field;
	protected $value;
	protected $err;
	protected $exists;

	public function __construct($field, $value, $exists)
	{
		$this->field = $field;
		$this->value = $value;
		$this->exists = $exists;
		$this->err = false;
	}

	/*
	 * Gets the error generatedd by the validation method.
	 *
	 * @access public
	 * @return mixed The error string or false for no error.
	 */
	function getError()
	{
		return $this->err;
	}

	/*
	 * Gets the value. This method may be overridden if a specific field type must be sanitized.
	 *
	 * @access public
	 * @return string
	 */
	function getValue()
	{
		return $this->value;
	}

	/**
	 * Returns the input so the user can enter a value.
	 *
	 * @access public
	 * @return mixed
	 */
	public function getInputHtml()
	{
		return $this->input_html;
	}

	/**
	 * Returns the output. It's the field's value formatted acccording to its criteria.
	 *
	 * @access public
	 * @return mixed
	 */
	public function getOutputHtml()
	{
		return $this->output_html;
	}
}

class postFields_check extends postFieldsBase
{
	function setHtml()
	{
		global $txt;
		$true = (!$this->exists && $this->field['default_value']) || $this->value;
		$this->input_html = '<input type="checkbox" name="postfield[' . $this->field['id_field'] . ']"' . ($true ? ' checked' : '') . '>';
		$this->output_html = $true ? $txt['yes'] : $txt['no'];
	}
	function validate()
	{
		// Nothing needed here, really. It's just a get out of jail free card. "This card may be kept until needed, or sold."
	}
	function getValue()
	{
		return $this->exists ? 1 : 0;
	}
}

class postFields_select extends postFieldsBase
{
	function setHtml()
	{
		$this->input_html = '<select name="postfield[' . $this->field['id_field'] . ']" style="width: 90%;">';
		foreach (explode(',', $this->field['options']) as $v)
		{
			$true = (!$this->exists && $this->field['default_value'] == $v) || $this->value == $v;
			$this->input_html .= '<option' . ($true ? ' selected="selected"' : '') . '>' . $v . '</option>';
			if ($true)
				$this->output_html = $v;
		}

		$this->input_html .= '</select>';
	}
	function validate()
	{
		global $txt;
		$found = false;
		$opts = array_flip(explode(',', $this->field['options']));
		if (isset($this->value, $opts[$this->value]))
			$found = true;

		if (!$found)
			$this->err = array('pf_invalid_value', $this->field['name']);
	}
	function getValue()
	{
		$value = $this->field['default_value'];
		$opts = array_flip(explode(',', $this->field['options']));
		if (isset($this->value, $opts[$this->value]))
			$value = $this->value;

		return $value;
	}
}

class postFields_radio extends postFieldsBase
{
	function setHtml()
	{
		$this->input_html = '<fieldset>';
		foreach (explode(',', $this->field['options']) as $v)
		{
			$true = (!$this->exists && $this->field['default_value'] == $v) || $this->value == $v;
			$this->input_html .= '<label><input type="radio" name="postfield[' . $this->field['id_field'] . ']"' . ($true ? ' checked="checked"' : '') . '> ' . $v . '</label><br>';
			if ($true)
				$this->output_html = $v;
		}
		$this->input_html .= '</fieldset>';
	}
	function validate()
	{
		$helper = new postFields_select($this->field, $this->value, $this->exists);
		$helper->validate();
	}
	function getValue()
	{
		$helper = new postFields_select($this->field, $this->value, $this->exists);
		$helper->getValue();
	}
}

class postFields_text extends postFieldsBase
{
	function setHtml()
	{
		$this->output_html = $this->value;
		$this->input_html = '<input type="text" name="postfield[' . $this->field['id_field'] . ']" ' . ($this->field['size'] != 0 ? 'maxsize="' . $this->field['size'] . '"' : '') . ' style="width: 90%;" size="' . ($this->field['size'] == 0 || $this->field['size'] >= 50 ? 50 : ($this->field['size'] > 30 ? 30 : ($this->field['size'] > 10 ? 20 : 10))) . '" value="' . $this->value . '">';
	}
	function validate()
	{
		if (!empty($this->field['length']))
			$value = substr($this->value, 0, $this->field['length']);

		$class_name = '\\live627\\PostFields\\postFieldMask_' . $this->field['mask'];
		if (!class_exists($class_name))
			die('Mask "' . $this->field['mask'] . '" not found for field "' . $this->field['name'] . '" at ID #' . $this->field['id_field'] . '.');

		$mask = new $class_name($this->value, $this->field);
		$mask->validate();
		if (false !== ($err = $mask->getError()))
			$this->err = $err;
	}
}

class postFields_textarea extends postFieldsBase
{
	function setHtml()
	{
		$this->output_html = $this->value;
		@list ($rows, $cols) = @explode(',', $this->field['default_value']);
		$this->input_html = '<textarea name="postfield[' . $this->field['id_field'] . ']" ' . (!empty($rows) ? 'rows="' . $rows . '"' : '') . ' ' . (!empty($cols) ? 'cols="' . $cols . '"' : '') . '>' . $this->value . '</textarea>';
	}
	function validate()
	{
		$helper = new postFields_text($this->field, $this->value, $this->exists);
		$helper->validate();
	}
}

interface postFieldMask
{
	function __construct($value, $field);
	function validate();
}

abstract class postFieldMaskBase implements postFieldMask
{
	protected $value;
	protected $field;
	protected $err;
	function __construct($value, $field)
	{
		$this->value = $value;
		$this->field = $field;
		$this->err = false;
	}

	function getError()
	{
		return $this->err;
	}
}

class postFieldMask_email extends postFieldMaskBase
{
	function validate()
	{
		global $txt;
		if (!filter_var($this->value, FILTER_VALIDATE_EMAIL))
			$this->err = array('pf_invalid_value', $this->field['name']);
	}
}

class postFieldMask_regex extends postFieldMaskBase
{
	function validate()
	{
		global $txt;
		if (!preg_match($this->field['regex'], $this->value))
			if (!empty($this->field['err']))
				$this->err = $this->field['err'];
			else
				$this->err = array('pf_invalid_value', $this->field['name']);
	}
}

class postFieldMask_number extends postFieldMaskBase
{
	function validate()
	{
		global $txt;
		if (!preg_match('/^\s*([0-9]+)\s*$/', $this->value))
			$this->err = array('pf_invalid_value', $this->field['name']);
	}
}

class postFieldMask_float extends postFieldMaskBase
{
	function validate()
	{
		global $txt;
		if (!preg_match('/^\s*([0-9]+(\.[0-9]+)?)\s*$/', $this->value))
			$this->err = array('pf_invalid_value', $this->field['name']);
	}
}

class postFieldMask_nohtml extends postFieldMaskBase
{
	function validate()
	{
		global $txt;
		if (strip_tags($this->value) != $this->value)
			$this->err = array('pf_invalid_value', $this->field['name']);
	}
}

?>
