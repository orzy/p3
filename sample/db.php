<?php
/**
 *	Last update 2013/03/14
 */

/*
-- Create Sample DB
CREATE DATABASE sample;
CREATE TABLE sample.animals (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  name TEXT,
  created_at DATETIME,
  updated_at DATETIME,
  PRIMARY KEY (id)
);
*/

$this->param('title', 'P3_Db Sample');

$db = $this->db('sample', 'user_name', 'password');

$db->columnCreatedAt();
$db->columnUpdatedAt();

$db->insert('animals', array('name' => 'Tiger'));

foreach ($db->select('*', 'animals', array('name' => 'Tiger'), 'ORDER BY id') as $row) {
	dump($row);
}

$db->update('animals', array('name' => 'Cat'), array('id' => 1));

$db->delete('animals', array('id' => 2));

dump($db->count('animals', array(
	'name' => 'Tiger',
	'id BETWEEN ? AND ?' => array(1, 4),
)));
