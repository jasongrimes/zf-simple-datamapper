<?php
/**
 * Abstract class for a simple domain object.
 *
 * @author     Jason Grimes <jason@grimesit.com>
 * @link       https://github.com/jasongrimes/zf-simple-datamapper
 * @category   Jg
 * @package    DomainObject
 * @copyright  Copyright (c) 2011 Jason Grimes <jason@grimesit.com>
 */

abstract class Jg_DomainObject_AbstractDomainObject implements Jg_DomainObject {

  /**
   * Define allowed property names and their default values, in the format name => default value
   * Lazy-loaded properties should be defined in $_lazy_properties instead.
   *
   * @var array
   */
  protected $_data = array();

  /**
   * @var string The name of the mapper class.
   */
  protected $_mapper_class = '';

  /**
   * @var Jg_Mapper
   */
  protected $_mapper;

  /**
   * Get the name of the data mapper class for this model
   *
   * @throws Jg_DomainObject_Exception if $_mapper_class is not defined.
   * @return string The name of the mapper class, which must implement Jg_Mapper
   */
  protected function _getMapperClass() {
    if (!$this->_mapper_class) {
      throw new Jg_DomainObject_Exception('$_mapper_class not defined.');
    }
    return $this->_mapper_class;
  }

  /**
   * Constructor
   *
   * @param array $data Initial data to populate. @see populate()
   * @return void
   * @throws Jg_DomainObject_Exception if required properties are not set
   */
  public function __construct($data = null) {
    if (null != $data) $this->populate($data);

    /*
    // Specify required properties
    if (!isset($this->id)) {
      throw new Jg_DomainObject_Exception('Initial data must contain an id');
    }
     */
  }

  /**
   * Populate object properties from an array.
   *
   * @param array $data
   * @return Jg_DomainObject The object itself. Provides a fluent interface.
   * @throws Jg_DomainObject_Exception if $data is invalid
   */
  public function populate(array $data) {
    if (!is_array($data)) {
      throw new Jg_DomainObject_Exception('Data to populate must be an array');
    }

    foreach ($data as $key => $value) {
      $this->$key = $value;
    }
    return $this;
  }

  /**
   * Magic set method
   *
   * @param string $name
   * @param mixed $value
   * @return void
   * @throws Jg_DomainObject_Exception if property is invalid
   * @todo Mark properties as "dirty" when they are set, so the mapper can check which properties have changed and save only those. Need a markClean() method so properties can be unmarked as dirty after populating initial values.
   */
  public function __set($name, $value) {
    if (array_key_exists($name, $this->_data)) {
      $this->_data[$name] = $value;
    } else {
      throw new Jg_DomainObject_Exception(__METHOD__ . ': Invalid property "' . $name . '"');
    }
  }

  /**
   * Magic get method
   *
   * @param string $name
   * @return mixed
   */
  public function __get($name) {
    if (array_key_exists($name, $this->_data)) {
      return $this->_data[$name];
    }
    return null;
  }

  /**
   * Magic isset method
   *
   * @param string $name
   * @return boolean
   */
  public function __isset($name) {
    return (isset($this->_data[$name]));
  }

  /**
   * Magic unset method
   *
   * @param string $name
   * @return void
   */
  public function __unset($name) {
    if (array_key_exists($name, $this->_data)) {
      $this->_data[$name] = null;
    }
  }

  protected function _getMapper() {
    if (is_null($this->_mapper)) {
      $class_name = $this->_getMapperClass();
      $this->_mapper = new $class_name();
    }
    return $this->_mapper;
  }

  /**
   * Save the object to persistent storage (i.e. the database)
   *
   * @return void
   */
  public function save() {
    $this->_getMapper()->save($this);
  }

  /**
   * Delete the object from persistent storage (i.e. the database)
   *
   * @return void
   */
  public function delete() {
    $this->_getMapper()->delete($this);
  }

  /**
   * Convert the object to an array. It should be possible to pass the result to populate().
   *
   * @return array
   */
  public function toArray() {
    return $this->_data;
  }

}
