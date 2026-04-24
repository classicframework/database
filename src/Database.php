<?php

namespace classicframework\database;

class Database
{
  protected $connection = null;
  protected $config = array();

  public function __construct($config = array())
  {
    $this->config = is_array($config) ? $config : array();
  }

  public function connect()
  {
    if ($this->connection instanceof \mysqli) {
      return $this->connection;
    }

    $host = isset($this->config['host']) ? (string) $this->config['host'] : '127.0.0.1';
    $username = isset($this->config['username']) ? (string) $this->config['username'] : '';
    $password = isset($this->config['password']) ? (string) $this->config['password'] : '';
    $database = isset($this->config['name']) ? (string) $this->config['name'] : '';
    $port = isset($this->config['port']) ? (int) $this->config['port'] : 3306;
    $charset = isset($this->config['charset']) ? (string) $this->config['charset'] : 'utf8mb4';

    $this->connection = new \mysqli($host, $username, $password, $database, $port);

    if ($this->connection->connect_error) {
      throw new \Exception('Database connection failed: ' . $this->connection->connect_error);
    }

    if ($charset !== '') {
      $this->connection->set_charset($charset);
    }

    return $this->connection;
  }

  public function connection()
  {
    return $this->connect();
  }

  public function query($sql)
  {
    return $this->connect()->query((string) $sql);
  }

  public function escape($value)
  {
    return $this->connect()->real_escape_string((string) $value);
  }

  public function insert_id()
  {
    return $this->connect()->insert_id;
  }

  public function affected_rows()
  {
    return $this->connect()->affected_rows;
  }

  public function config()
  {
    return $this->config;
  }

  public function rows($sql)
  {
    $result = $this->query($sql);

    $rows = array();

    if ($result instanceof \mysqli_result) {
      while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
      }

      $result->free();
    }

    return $rows;
  }

  public function row($sql)
  {
    $result = $this->query($sql);

    if ($result instanceof \mysqli_result) {
      $row = $result->fetch_assoc();
      $result->free();

      return $row ? $row : null;
    }

    return null;
  }

  public function field($sql)
  {
    $row = $this->row($sql);

    if ($row) {
      return reset($row);
    }

    return null;
  }

  public function execute($sql)
  {
    return $this->query($sql);
  }

  protected function quote_identifier($name)
  {
    return '`' . str_replace('`', '``', (string) $name) . '`';
  }

  protected function value($value)
  {
    if ($value === null) {
      return 'NULL';
    }

    if (is_bool($value)) {
      return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
      return (string) $value;
    }

    return "'" . $this->escape($value) . "'";
  }

  public function insert($table, $data)
  {
    $fields = array();
    $values = array();

    foreach ($data as $field => $value) {
      $fields[] = $this->quote_identifier($field);
      $values[] = $this->value($value);
    }

    $sql = 'INSERT INTO ' . $this->table($table)
      . ' (' . implode(', ', $fields) . ')'
      . ' VALUES (' . implode(', ', $values) . ')';

    $this->execute($sql);

    return $this->insert_id();
  }

  public function update($table, $data, $where)
  {
    $sets = array();

    foreach ($data as $field => $value) {
      $sets[] = $this->quote_identifier($field) . ' = ' . $this->value($value);
    }

    $conditions = array();

    foreach ($where as $field => $value) {
      $conditions[] = $this->quote_identifier($field) . ' = ' . $this->value($value);
    }

    $sql = 'UPDATE ' . $this->table($table)
      . ' SET ' . implode(', ', $sets)
      . ' WHERE ' . implode(' AND ', $conditions);

    $this->execute($sql);

    return $this->affected_rows();
  }

  public function delete($table, $where)
  {
    $conditions = array();

    foreach ($where as $field => $value) {
      $conditions[] = $this->quote_identifier($field) . ' = ' . $this->value($value);
    }

    $sql = 'DELETE FROM ' . $this->table($table)
      . ' WHERE ' . implode(' AND ', $conditions);

    $this->execute($sql);

    return $this->affected_rows();
  }

  public function table($name)
  {
    $prefix = isset($this->config['prefix']) ? (string) $this->config['prefix'] : '';

    return $this->quote_identifier($prefix . (string) $name);
  }

  public function table_exists($table)
  {
    $table = (string) $table;
    $prefix = isset($this->config['prefix']) ? (string) $this->config['prefix'] : '';
    $full = $prefix . $table;

    $sql = "SHOW TABLES LIKE " . $this->value($full);

    $result = $this->query($sql);

    if ($result instanceof \mysqli_result) {
      $exists = $result->num_rows > 0;
      $result->free();

      return $exists;
    }

    return false;
  }
}