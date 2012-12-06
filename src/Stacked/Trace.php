<?php

/**
 * @author Matthew Patterson <matthew.s.patterson@gmail.com>
 * @version 1.0
 */

namespace Stacked;

/**
 * @property-read type $args Description
 * @property-read type $class Description
 * @property-read type $file Description
 * @property-read type $function Description
 * @property-read type $line Description
 * @property-read type $object Description
 * @property-read type $type Description
 * @property-read type $next Description
 * @property-read type $previous Description
 */
class Trace {

	protected $args;

	protected $class;

	protected $file;

	protected $function;

	protected $line;

	protected $object;

	protected $type;

	protected $next;

	protected $previous;

	protected function __construct(array $data, Trace $next = null) {
		// Return if there is no more data left to parse
		if (empty($data)) return;

		$current = array_shift($data);

		foreach ($current as $key => $val) {
			$this->{$key} = $val;
		}

		if ($next instanceof self) {
			$this->next = $next;
		}

		if (!empty($data)) {
			$this->previous = new self($data, $this);
		}
	}

	public function __call($name, $args) {
		$expected = $args[0];
		$desiredResult = true;

		switch($name) {
			case 'notClass':
				$desiredResult = false;
				// No break
			case 'class':
				if ($this->test('class', $expected) == $desiredResult) {
					return $this;
				} elseif ($this->previous instanceof self) {
					return $this->previous->{$name}($expected);
				} else {
					return new self(array(), $this);
				}
				break;

			case 'notFile':
				$desiredResult = false;
				// No break
			case 'file':
				if ($this->test('file', $expected) == $desiredResult) {
					return $this;
				} elseif ($this->previous instanceof self) {
					return $this->previous->{$name}($expected);
				} else {
					return new self(array(), $this);
				}
				break;

			case 'notFunction':
			case 'notMethod':
				$desiredResult = false;
				// No break
			case 'function':
			case 'method':
				if ($this->test('function', $expected) == $desiredResult) {
					return $this;
				} elseif ($this->previous instanceof self) {
					return $this->previous->{$name}($expected);
				} else {
					return new self(array(), $this);
				}
				break;

			case 'notLine':
				$desiredResult = false;
				// No break
			case 'line':
				if ($this->test('line', $expected) == $desiredResult) {
					return $this;
				} elseif ($this->previous instanceof self) {
					return $this->previous->{$name}($expected);
				} else {
					return new self(array(), $this);
				}
				break;

			case 'notInstanceOf':
				$desiredResult = false;
				// No break
			case 'instanceOf':
				break;

			case 'nonStatic':
				$desiredResult = false;
				// No break
			case 'static':
				break;
		}
		return $this;
	}

	/**
	 * Allows read-only access to class properties
	 * @param string $name The name of the property being accessed
	 * @return mixed The value of the property.  If the property is not defined,
	 *		then null is returned EXCEPT for the $next and $previous properties,
	 *		which will return an empty instance of Trace if they are not defined.
	 *		This is to prevent errors when calling methods off of chained
	 *		instances of Trace.
	 */
	public function __get($name) {
		switch ($name) {
			case 'next':
			case 'previous':
				return ($this->{$name} instanceof self) ? $this->{$name} : new self(array(), $this);
				break;
			default:
				return isset($this->{$name}) ? $this->{$name} : null;
		}
	}

	public function test($propName, $propTest) {
		switch ($propName) {
			case 'file':
			case 'class':
				if ($propTest[0] != '/') {
					$propTest = '/' . $propTest . '/';
				}
				return (preg_match($propTest, $this->{$propName}));
				break;

			case 'line':
			case 'function':
			default:
				return ($this->{$propName} == $propTest);
		}
	}

	public static function start($limit = 0) {
		$options = DEBUG_BACKTRACE_IGNORE_ARGS;
		if ($limit > 0) {
			// Add 1 to the limit, since we shift off the first call
			$limit += 1;
		}

		if (PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION >= 4)) {
			$trace = debug_backtrace($options, $limit);
		} else {
			$trace = debug_backtrace($options);
			if ($limit > 0) {
				$trace = array_slice($trace, 0, $limit);
			}
		}

		array_shift($trace);
		return new self($trace);
	}

}
