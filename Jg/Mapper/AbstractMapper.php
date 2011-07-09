<?php
/**
 * Abstract class for a simple data mapper.
 *
 * @author     Jason Grimes <jason@grimesit.com>
 * @link       https://github.com/jasongrimes/zf-simple-datamapper
 * @category   Jg
 * @package    Mapper
 * @copyright  Copyright (c) 2011 Jason Grimes <jason@grimesit.com>
 */

abstract class Jg_Mapper_AbstractMapper implements Jg_Mapper {

  /**
   * The class name of the domain objects this mapper works with.
   * Child classes must define this, or else override getDomainObjectClass()
   *
   * @var string
   */
  protected $_domain_object_class = '';

  /**
   * @var string The name of the table to which the domain object is mapped.
   */
  protected $_table = '';

  /**
   * @var string The name of the table's primary key
   */
  protected $_primary = '';

  /**
   * Define mapping of table column names to different object property names.
   * By default, the object will have a property named after each column.
   *
   * If the property name should be different than the column name, define
   * a mapping here.
   *
   * If the column should not be included in the object at all, set it to null.
   *
   * @var array An array of data in the following format:<pre>
   *   'col_1' => 'property_name',
   *   'col_2' => null, // Don't include col_2 in the object
   * </pre>
   */
  protected $_map_columns_to_properties = array();

  protected $_slave_db_name = 'db';
  protected $_master_db_name = 'db';

  /**
   * @var array An array of Zend_Db_Table instances
   */
  protected $_db_tables = array();
  
  /**
   * Get a domain object by its ID (i.e. primary key)
   *
   * @param mixed $id The primary key value
   * @param string $db_name Optional name of the database adapter to use.
   * @return Jg_DomainObject A domain object, or null if no object was found with that ID
   */
  public function get($id, $db_name = null) {
    $select = $this->_getSelect();
    $select->where($this->_primary . ' = ?', $id);
    $db_data = $select->query()->fetch();

    if (!empty($db_data)) {
      return $this->createObject($db_data);
    }
  }

  /**
   * Create and return a new Zend_Db_Select object that can be used to retrieve
   * data for the domain object.
   *
   * As-is, this query will fetch the data for *all* domain objects. Finder
   * methods can add clauses to this select object to control which
   * domain objects are returned.
   *
   * @param string $db_name The database adapter name to use. Default db_slave.
   * @return Zend_Db_Select The select object
   */
  protected function _getSelect($db_name = null) {
    $db = $this->_getDbAdapter($db_name);
    return $db->select()->from($this->_table);
  }

  /**
   * Get a database adapter
   *
   * @param string $db_name The name of the database adapter stored in Zend_Registry. Default $_slave_db_name.
   * @return Zend_Db_Adapter_Abstract
   */
  protected function _getDbAdapter($db_name = null) {
    if (!$db_name) $db_name = $this->_slave_db_name;
    return Zend_Registry::get($db_name);
  }

  /**
   * Maps the row data returned by the _getSelect() query to an array of data that can
   * be passed to the domain object's populate method.
   *
   * If you need to do any fancy mapping logic, this is a good place to do it.
   *
   * @param array $db_data An array of data from a single row as returned by the main select query. @see _getSelect()
   * @return array An array of data that can be passed to the object's populate method
   */
  protected function _mapDbDataToObjectData(array $db_data) {
    $object_data = array();

    foreach ($db_data as $col => $val) {
      if (array_key_exists($col, $this->_map_columns_to_properties)) {
        if (null == $this->_map_columns_to_properties[$col]) {
          continue;
        }
        $key = $this->_map_columns_to_properties[$col];

      } else {
        $key = $col;
      }

      $object_data[$key] = $val;
    }

    return $object_data;
  }

  /**
   * Find a collection of domain objects by the specified criteria.
   *
   * @param array $criteria An array of criteria to search by
   * @param array $options An array of options, with at least the following options:<pre>
   *   get_lazy_collection - Boolean. Whether to retrieve a Jg_Mapper_LazyCollection instead of a Jg_Mapper_Collection. Default false.
   *   db_name - The optional name of the database adapter to use.
   * </pre>
   * @return Jg_Mapper_Collection A collection of domain objects, or an empty collection if no objects were found
   */
  public function findBy(array $criteria, $options = array()) {
    $db = $this->_getDbAdapter($options['db_name']);
    $select = $this->_getSelect($options['db_name']);

    foreach ($criteria as $key => $val) {
      if (is_array($val)) {
        $select->where($db->quoteIdentifier($key) . ' IN (?)', $val);
      } else {
        $select->where($db->quoteIdentifier($key) . ' = ?', $val);
      }
    }

    if ($options['get_lazy_collection']) {
      return new Jg_Mapper_LazyCollection($select, $this);
    } else {
      $data = $select->query()->fetchAll();
      return new Jg_Mapper_Collection($data, $this);
    }
  }

  /**
   * Get a collection of all domain objects.
   *
   * @param array $options An array of options, with at least the following options:<pre>
   *   get_lazy_collection - Boolean. Whether to retrieve a Jg_Mapper_LazyCollection instead of a Jg_Mapper_Collection. Default false.
   *   db_name - The optional name of the database adapter to use.
   * </pre>
   * @return Jg_Mapper_Collection
   */
  public function findAll($options = array()) {
    return $this->findBy(array(), $options);
  }

  /**
   * Save a domain object. Modifies the passed object if saving caused any properties
   * to change (ex. set the ID of a new object)
   * 
   * @param Jg_DomainObject $object
   * @return void
   * @throws Jg_Mapper_Exception if $object is of the wrong type
   */
  public function save(Jg_DomainObject $object) {
    $class = $this->getDomainObjectClass();
    if (!($object instanceof $class)) {
      throw new Jg_Mapper_Exception(__METHOD__ . ': Expected instance of "' . $class . '"');
    }

    // Get a Zend_Db_Table_Row
    $db_table = $this->_getDbTable($this->_master_db_name);
    if ($object->getId()) {
      // Update
      $row = $db_table->find($object->getId())->current();
    } else {
      // Insert
      $row = $db_table->createRow();
    }

    // Save to the database
    $row->setFromArray($this->_mapObjectToDbData($object));
    $row->save();

    // Refresh the object, in case the database query triggered any changes.
    $object->populate($this->_mapDbDataToObjectData($row->toArray()));

    return $object;
  }

  /**
   * Get a Zend_Db_Table instance.
   *
   * @param $db_name The optional name of the database adapter to use. A Zend_Registry key for a Zend_Db_Adapter instance. Defaults to $_slave_db_name.
   * @return Zend_Db_Table
   */
  protected function _getDbTable($db_name = null) {
    if (!$db_name) $db_name = $this->_slave_db_name;
    $table_name = $this->_table;
    $primary = $this->_primary;

    // Create a new Zend_Db_Table instance if necessary
    if (null == $this->_db_tables[$db_name][$table_name]) {
      if (!$table_name) {
        throw new Jg_Mapper_Exception(__METHOD__ . ': $_table is not defined.');
      }
      if (!$primary) {
        throw new Jg_Mapper_Exception(__METHOD__ . ': $_primary is not defined.');
      }

      $this->_db_tables[$db_name][$table_name] = new Zend_Db_Table(array(
        'name' => $table_name,
        'primary' => $primary,
        'db' => $db_name,
      ));
    }
    return $this->_db_tables[$db_name][$table_name];
  }

  /**
   * Maps object properties to database columns.
   *
   * If you need to do any fancy mapping logic, this is a good place to do it.
   *
   * @param Jg_DomainObject $object
   * @return array An array of data that can be passed to the database row's setFromArray method
   */
  protected function _mapObjectToDbData(Jg_DomainObject $object) {
    $row_data = array();

    foreach ($object->toArray() as $prop_name => $val) {
      if ($col = array_search($prop_name, $this->_map_columns_to_properties)) {
        $row_data[$col] = $val;
      } else {
        $row_data[$prop_name] = $val;
      }
    }

    return $row_data;
  }

  /**
   * Delete a domain object from the database (or mark it as deleted)
   * 
   * @param Jg_DomainObject $object
   * @return void
   * @throws Jg_Mapper_Exception if $object is of the wrong type
   */
  public function delete(Jg_DomainObject $object) {
    // To be safe, we don't delete anything in the abstract implementation.
    // Child classes must explicitly implement this method in order for it to work.
    // A sample implementation is shown below.

    /*
    $class = $this->getDomainObjectClass();
    if (!($object instanceof $class)) {
      throw new Jg_Mapper_Exception(__METHOD__ . ': Expected instance of "' . $class . '"');
    }
    $db_table = $this->_getDbTable($this->_master_db_name);
    $row = $db_table->find($object->getId())->current();
    $row->delete();
     */
  }

  /**
   * Create a domain object instance from the specified data.
   * 
   * Normally, this method should not be called directly. It is used internally
   * to get additional data that wasn't retrieved by the select object, and by
   * Jg_Mapper_Collection to create objects as they are needed.
   *
   * @param array $data
   * @return Jg_DomainObject
   */
  public function createObject(array $db_data) {
    $object_data = $this->_mapDbDataToObjectData($db_data);

    // Here is where derived classes should handle any additional complex mapping.
    // I.e. instantiate other objects which compose the domain object, etc.

    $class = $this->getDomainObjectClass();
    return new $class($object_data);
  }

  /**
   * Get the class name of the domain objects this mapper works with.
   * 
   * @return string
   */
  public function getDomainObjectClass() {
    if (null == $this->_domain_object_class) {
      throw new Jg_Mapper_Exception('$_domain_object_class was not defined in ' . __CLASS__ . '.');
    }
    return $this->_domain_object_class;
  }

}
