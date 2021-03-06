<?php
	
	/**
	 * --------------------------------------------------------------------------
	 *   @Copyright : License MIT 2017
	 *
	 *   @Author : Alexandre Caillot
	 *   @WebSite : https://www.shiros.fr
	 *
	 *   @File : MySQLGateway.php
	 *   @Created_at : 24/11/2016
	 *   @Update_at : 02/12/2017
	 * --------------------------------------------------------------------------
	 */

	namespace ShirOSBundle\Database\Gateway;
	use ShirOSBundle\Config;
	use ShirOSBundle\Database\Database;
	use ShirOSBundle\Database\MySQLDatabase;
	use ShirOSBundle\Model\Model;
	use ShirOSBundle\Utils\Exception\DatabaseException;
	use ShirOSBundle\Utils\NameSupervisor\NameSupervisor;

	class MySQLGateway implements Gateway
	{
		/**
		 * Instance de la Classe de la base de données
		 * @var MySQLDatabase
		 */
		protected $DBModule;

		/**
		 * Instance de la Classe de gestion des Configs
		 * @var Config
		 */
		protected $ConfigModule;
		
		/**
		 * Instance de la Classe de gestion des noms
		 * @var NameSupervisor
		 */
		protected $NameSupervisorModule;
		
		/**
		 * Separateur pour les Group_Concat
		 * @var String
		 */
		protected $separator;

		/**
		 * Nom de la Table
		 * @var String
		 */
		protected $table;
		
		
		/**
		 * MySQLGateway constructor.
		 *
		 * @param MySQLDatabase $database
		 */
		public function __construct(MySQLDatabase $database)
		{
			$this->DBModule = $database;
			$this->ConfigModule = Config::getInstance();
			$this->NameSupervisorModule = NameSupervisor::getInstance();
			
			$this->separator = $this->ConfigModule->get('ShirOS.Separator');
			
			if(is_null($this->table))
			{
				$parse = explode('\\', get_class($this));
				$class_name = end($parse);
				$this->table = strtolower($this->NameSupervisorModule->removePSTo($class_name));
			}
		}




		/* ------------------------ Préparation des Reqêtes ------------------------ */
		
			/**
			 * Permet d'unifier l'appel d'une requete, et choisi automatiquement en fonction de si un attribut lui est passer, la méthode prepare ou query de la DB
			 *
			 * @param String $request
			 * @param array $attributes | Default Value = NULL
			 * @param bool $one | Default Value = false
			 *
			 * @return mixed
			 */
			public function query(String $request, array $attributes = NULL, $one = false)
			{
				switch ($this->ConfigModule->get('ShirOS.Database.FetchMode.Current')) {
					case $this->ConfigModule->get('ShirOS.Database.FetchMode.Name.Fetch_Class'):
						$class = $this->NameSupervisorModule->gatewayPath_To_modelPath(get_class($this));
						break;
					
					case $this->ConfigModule->get('ShirOS.Database.FetchMode.Name.Fetch_Into'):
						$class = $this;
						break;
					
					default:
						$class = NULL;
						break;
				}
				
				if($attributes) {
					return $this->DBModule->prepare(
						$request,
						$attributes,
						$class,
						$one
					);
				}
				
				else
					return $this->DBModule->query(
						$request,
						$class,
						$one
					);
			}




		/* ------------------------ Opérations CRUD ------------------------ */
		
			/**
			 * Récupère toutes les données
			 *
			 * @return mixed
			 */
			public function all()
			{
				$request =
					"SELECT *
					FROM $this->table";
				
				return $this->query($request);
			}
		
		
			/**
			 * Récupère certaines données en fonction des paramètres
			 *
			 * @param array $fields
			 *
			 * @return mixed
			 */
			public function select(array $fields)
			{
				$SQLParts = [];
				$attributes = [];

				foreach ($fields as $key => $value) {
					$SQLParts[] = "$key LIKE ?";
					$attributes[] = $value;
				}

				$SQLPart = implode(' AND ', $SQLParts);
				$request =
					"SELECT *
					FROM {$this->table}
					WHERE $SQLPart";
				
				return $this->query($request, $attributes);
			}
		
		
			/**
			 * Récupère une donnée en fonction des paramètres.
			 *
			 * @param string $column
			 * @param string $value
			 *
			 * @return mixed
			 */
			public function find(string $column, string $value)
			{
				$request =
					"SELECT *
					FROM {$this->table}
					WHERE {$column}
					LIKE ?";
				
				return $this->query($request, [$value], true);
			}
		
		
			/**
			 * Créer une donnée
			 *
			 * @param Model $object
			 * @param bool $date | Default Value = false
			 *
			 * @return mixed
			 */
			public function create(Model $object, bool $date = false)
			{
				$SQLParts = [];
				$attributes = [];
				$fields = get_object_vars($object);

				if($date)
					$SQLParts[] = 'date = "' . date('Y-m-d H:i:s') . '"';

				foreach ($fields as $key => $value) {
					$SQLParts[] = "$key = ?";
					$attributes[] = $value;
				}

				$SQLPart = implode(', ', $SQLParts);
				$request =
					"INSERT INTO {$this->table}
					SET $SQLPart";
				
				return $this->query($request, $attributes, true);
			}
		
		
			/**
			 * Modifie une donnée
			 *
			 * @param array $id
			 * @param Model $object
			 *
			 * @return mixed
			 */
			public function update(array $id, Model $object)
			{
				$SQLParts = [];
				$attributes = [];
				$fields = get_object_vars($object);

				foreach ($fields as $key => $value) {
					$SQLParts[] = "$key = ?";
					$attributes[] = $value;
				}
				$attributes[] = $id[Database::UPDATE_VALUE];

				$SQLPart = implode(', ', $SQLParts);
				$request =
					"UPDATE {$this->table}
					SET $SQLPart
					WHERE {$id[Database::UPDATE_COLUMN]}
					LIKE ?";
				
				return $this->query($request, $attributes, true);
			}
		
		
			/**
			 * Supprimer une donnée
			 *
			 * @param string $column
			 * @param string $value
			 *
			 * @return mixed
			 */
			public function delete(string $column, string $value)
			{
				$request =
					"DELETE FROM {$this->table}
					WHERE {$column}
					LIKE ?";
				
				return $this->query($request, [$value], true);
			}




		/* ------------------------ Opérations Supplémentaire ------------------------ */
			
			/**
			 * Retourne tout les éléments par rapport au couple clé/valeur
			 *
			 * @param string $key
			 * @param string $value
			 *
			 * @return array
			 */
			public function extract(string $key, string $value): array
			{
				$records = $this->all();
				$return = [];

				foreach ($records as $v)
					$return[$v->$key] = $v->$value;
				
				return $return;
			}


			/**
			 * Effectue une recherche par mot clé
			 *
			 * @param String $column
			 * @param String $value
			 *
			 * @return mixed
			 */
			public function search(String $column, String $value)
			{
				$request =
					"SELECT *
					FROM {$this->table}
					WHERE {$column}
					LIKE CONCAT(\"%\",?,\"%\")";
				
				return $this->query($request, [$value]);
			}


			/**
			 * Retourne le nombre d'élément dans une table
			 *
			 * @return int
			 */
			public function count(): int
			{
				$request =
					"SELECT count(*) as nb
					FROM {$this->table}";
				
				return (int)$this->query($request, NULL, true)->nb;
			}
	}
?>