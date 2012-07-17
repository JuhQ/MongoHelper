<?php
include ("../Code/MongoHelper.php");

class blog extends MongoHelper {
	
	/**
	 * This class will use blog_entries collection
	 * @var string
	 */
	public $collectionName = "blog_entries";
	
	/**
	 * Insert new blog entry
	 * @param string $title
	 * @param string $text
	 * @return boolean
	 */
	public function addEntry($title, $text) {
		return $this->insert(array("id" => $this->nextIncrement(), "title" => $title, "text" => $text, "date" => $this->date()));
	}

	/**
	 * Edit entry
	 * @param int $id
	 * @param string $title
	 * @param string $text
	 * @return boolean
	 */
	public function editEntry($id, $title, $text) {
		return $this->update(array("id" => (int)$id), array('$set' => array("title" => $title, "text" => $text, "editDate" => $this->date())));
	}
	
	/**
	 * Delete entry
	 * @param int $id
	 * @return boolean
	 */
	public function deleteEntry($id) {
		return $this->remove(array("id" => (int)$id));
	}
}


$blog = new blog();
$blog->addEntry("Cheeseburgers", "This entry is full of winning.");


// get all blog entries
$entries = $blog->getAll();
foreach($entries AS $entry) {
	echo '<h2>' . $entry['title'] . '</h2>';
	echo '<p>' . $entry['text'] . '</p>';
}


// get the first ten entries
$ten = $blog->find();
$ten->sort(array("id" => -1));
$ten->limit(10);

foreach($entries AS $entry) {
	echo '<h2>' . $entry['title'] . '</h2>';
	echo '<p>' . $entry['text'] . '</p>';
}




