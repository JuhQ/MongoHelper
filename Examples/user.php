<?php
include ("../Code/MongoHelper.php");

/**
 * Define user class, MongoHelper will fallback to defaults if no configurations is given
 * This will use "user" collection by default
 * @author juhatauriainen
 */
class user extends MongoHelper {
}

$user = new user();

// insert some rows
$user->insert(array("username" => "Pekka", "city" => "Helsinki", "sex" => "Yes please"));
$user->insert(array("username" => "Simo", "city" => "Helsinki"));
$user->insert(array("username" => "Jaana", "city" => "Tampere"));
$user->insert(array("username" => "Bill", "city" => "Seattle"));

// find single row
$pekka = $user->findOne(array("username" => "Pekka"));
echo '<p>Found ' . $pekka['username'] . ' from ' . $pekka['city'] . '</p>';

// fetch all rows
$rows = $user->getAll();
echo '<p>Found ' . $rows->count() . ' rows</p>';
foreach ($rows as $row) {
	echo '<p>Found ' . $row['username'] . ' from ' . $row['city'] . '</p>';
}

// sort users by username
$rows->sort(array("username" => -1));
echo '<p>Sorting users</p>';
foreach ($rows as $row) {
	echo '<p>Found ' . $row['username'] . ' from ' . $row['city'] . '</p>';
}


// Pekka moved to Espoo, update row
$user->update(array("username" => "Pekka"), array("city" => "Espoo"));

// remove pekka from collection
$user->remove(array("username" => "Pekka"));


// finally some other demonstrations

// MongoHelper will return you MongoDB and MongoCollection classes also, which you can use how ever you like
$collection = $user->collection;
print_r($collection->find(array("city" => "Helsinki")));

$mongodb = $user->mongo->authenticate("Pekka", "Pouta");

