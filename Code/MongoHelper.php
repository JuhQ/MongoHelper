<?php

/**
 * MongoHelper is a class that will speed up development with MongoDB in PHP
 * @author Juha Tauriainen juha@bin.fi @juha_tauriainen
 */
class MongoHelper {
	
	/**
	 * You might want to define this. Your collections will be stored in this database.
	 * @var string
	 */
	public $databaseName = "mydatabase";
	
	/**
	 * Mongo database object
	 * @var object
	 */
	public $mongo;
	
	/**
	 * Database class is stored in this variable
	 * @var object
	 */
	public $collection;
	
	/**
	 * Table name
	 * @var string
	 */
	public $collectionName = false;
	
	/**
	 * Define server configuration here
	 * http://www.php.net/manual/en/mongo.construct.php
	 * @var string
	 */
	private $server = "localhost";
	
	/**
	 * Possible options
	 * @var array
	 */
	private $options = array("persist" => true);
	
	/**
	 * Mongo username
	 * @var string
	 */
	private $username = false;
	
	/**
	 * Mongo password
	 * @var string
	 */
	private $password = false;
	
	/**
	 * Cached version of MongoDB object
	 * @var object
	 */
	private static $mongoObj = false;
	
	/**
	 * Name of collection where to the auto-increment values
	 * You can change it but only before you store your first value.
	 * Once you begin storing values of you auto-increments, it's best not to change this, ever!
	 *
	 * @author Dmitri Snytkine - http://lampcms.blogspot.com/2010/09/php-class-for-simulating-auto-increment.html
	 * @var string name of collection
	 */
	const AUTOINCREMENT_COLLECTION_NAME = 'Autoincrement';
	
	/**
	 * Open database connection and define collection
	 * @param mixed string / boolean $collection
	 */
	public function __construct($collection = false) {
		
		// enable dynamic collections
		// this will help to create new collections even faster, without the need to create new classes
		if($collection !== false) {
			$this->collectionName = $collection;
		}
		
		// if no collection is defined, use class name as collection name
		if ($this->collectionName === false) {
			$this->collectionName = get_class($this);
		}
		
		// if mongo object is already cached, use that instead of opening new connection
		if (self::$mongoObj !== false) {
			$mongo = self::$mongoObj;
		} else {
			
			$mongo = new Mongo();
			
			// cache mongodb object
			self::$mongoObj = $mongo;
		}
		
		$this->mongo = $mongo->{$this->databaseName};
		
		// authentication
		if ($this->username !== false && $this->password !== false) {
			$this->authenticate($this->username, $this->password);
		}
		
		$this->collection = $this->mongo->{$this->collectionName};
	}
	
	/**
	 * Login to mongo
	 * @param string $username
	 * @param string $password
	 * @return array
	 */
	public function authenticate($username, $password) {
		return $this->mongo->authenticate($username, $password);
	}
	
	/**
	 * Return next increment id for the table
	 * @return int
	 */
	public function nextIncrement() {
		return $this->autoIncrement($this->mongo, $this->collectionName);
	}
	
	/**
	 * Return all rows
	 * @return array
	 */
	public function getAll() {
		return $this->collection->find();
	}
	
	/**
	 * Find one matching rows
	 * @param array $filters
	 * @return array
	 */
	public function find($filters, $fields = array()) {
		return $this->collection->find($filters, $fields);
	}
	
	/**
	 * Find one matching row
	 * @param array $filters
	 * @return array
	 */
	public function findOne($filters, $fields = array()) {
		return $this->collection->findOne($filters, $fields);
	}
	
	/**
	 * Insert row(s) to database
	 * @return mixed array / boolean
	 */
	public function insert($data) {
		return $this->collection->insert($data);
	}
	
	/**
	 * Update row(s) from database
	 * @return mixed array / boolean
	 */
	public function update($filters, $newData) {
		return $this->collection->update($filters, $newData);
	}
	
	/**
	 * Remove row(s) from database
	 * @return mixed array / boolean
	 */
	public function remove($filters) {
		return $this->collection->remove($filters);
	}
	
	/**
	 * Creates an index on the given field(s), or does nothing if the index already exists
	 * @param array $keys
	 * @param array $options
	 * @return boolean
	 */
	public function ensureIndex($keys = array(), $options = array()) {
		return $this->collection->ensureIndex($keys, $options);
	}
	
	/**
	 * Return MongoDate object with possible date
	 * @param mixed int / string $timestamp
	 * @return object
	 */
	public function date($timestamp = false) {
		
		// if no timestamp, return current time
		if ($timestamp === false) {
			return new MongoDate();
		}
		
		if (!is_numeric($timestamp)) {
			$timestamp = strtotime($timestamp);
		}
		
		return new MongoDate($timestamp);
	}
	
	/**
	 * Create MongoId object
	 * @param string $id
	 * @return object
	 */
	public function mongoid($id) {
		return new MongoId($id);
	}
	
	/**
	 * Create MongoRegex object
	 * @param string $regex
	 * @return object
	 */
	public function regex($regex) {
		return new MongoRegex($regex);
	}

	/**
	 * Represents JavaScript code for the database.
	 * @param string $func
	 * @param array $scope
	 * @return object
	 */
	public function code($func, $scope = array()) {
		return new MongoCode($func, $scope);
	}
	
	/**
	 * Execute code on the server
	 * @param mixed object / string $code
	 * @param array $args
	 * @return mixed
	 */
	public function execute($code, $args = array()) {
		return $this->mongo->execute($code, $args);
	}
	
	/**
	 * The pseudo auto increment handling is done by storing collectionName => id in Autoincrements collection
	 *
	 * We get value, increment it and resave it but watch for Errors/Exceptions in order to prevent race condition
	 * 
	 * @author Dmitri Snytkine - http://lampcms.blogspot.com/2010/09/php-class-for-simulating-auto-increment.html
	 *
	 * @param obj $db must pass object of type MongoDB
	 *
	 * @param string $collName which collection this id is for. This has nothing to do with the name of the collection
	 * where these generated sequence numbers are stored.
	 * For example if you need the next id for collection 'STUDENTS', then you pass the 'STUDENTS' as $collName value
	 * This way different values of 'next id' are maintained per collection name
	 *
	 * @param int initialId if there is no record for the collection yet, then start the increment counter
	 * with this value.
	 *
	 * @param int $try this is used for recursive calling this method
	 * You should NEVER pass this value yourself
	 *
	 * @return int value of next id for the collection
	 */
	private function autoIncrement(MongoDB $db, $collName, $minId = 0, $try = 1) {
		
		if ($try > 100) {
			throw new RuntimeException('Unable to get nextID for collection ' . $collName . ' after 100 tries');
		}
		
		$prevRecordID = null;
		$coll = $db->selectCollection(self::AUTOINCREMENT_COLLECTION_NAME);
		$coll->ensureIndex(array('coll' => 1, 'id' => 1), array('unique' => true));
		
		/**
		 * We use find() instead of findOne() for a reason!
		 * It's just more reliable this way
		 */
		$cursor = $coll->find(array('coll' => $collName))->sort(array('id' => -1))->limit(1);
		if ($cursor && $cursor->hasNext()) {
			$a = $cursor->getNext();
			$prevRecordID = $a['_id'];
		} else {
			$a = array('coll' => $collName, 'id' => $minId);
		}
		
		$prevID = $a['id'];
		$newId = ($a['id'] + 1);
		
		/**
		 * Remove the _id from record, otherwise
		 * we will be unable to insert
		 * a new record if it already has the same _id
		 * This way a new _id will be auto-generated for us
		 */
		unset($a['_id']);
		$a['id'] = $newId;
		
		/**
		 * Wrapping this inside try/catch so that if
		 * another process inserts the same value of coll/id
		 * between the time we selected and updated this
		 * it will throw exception or return false and then
		 * we will try again up to 100 times
		 *
		 * In Case of duplicate key Mongo throws Exception,
		 * but just in case it will change in the future,
		 * we also test if $ret is false
		 */
		try {
			/**
			 * Using fsync=>true because its very critically important
			 * to actually write the row to disc, otherwise if database
			 * goes down we will lose the correct value
			 * of our increment ID
			 */
			$ret = $coll->insert($a, array('fsync' => true));
			if (!$ret) {
				$try++;
				
				return $this->autoIncrement($db, $collName, $initialId, $try);
			}
			
			/**
			 * Insert successfull
			 * now delete previous record(s)
			 */
			if (null !== $prevRecordID) {
				$removed = $coll->remove(array('_id' => $prevRecordID)); //, array('fsync' => true) // not very important to fsync
			}
		
		} catch ( MongoException $e ) {
			
			$try++;
			
			return $this->autoIncrement($db, $collName, $initialId, $try);
		}
		
		return $newId;
	}
}