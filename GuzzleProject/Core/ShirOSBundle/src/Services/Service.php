<?php
	
	/**
	 * --------------------------------------------------------------------------
	 *   @Copyright : License MIT 2017
	 *
	 *   @Author : Alexandre Caillot
	 *   @WebSite : https://www.shiros.fr
	 *
	 *   @File : Service.php
	 *   @Created_at : 02/12/2017
	 *   @Update_at : 02/12/2017
	 * --------------------------------------------------------------------------
	 */
	
	namespace ShirOSBundle\Services;

	use ShirOSBundle\ApplicationKernel;
	use ShirOSBundle\Config;
	use ShirOSBundle\Utils\NameSupervisor\NameSupervisor;
	use ShirOSBundle\Utils\Session\Session;

	class Service
	{
		protected const COMPARE_EQUALS = '=';
		protected const COMPARE_GREATER_THAN = '>';
		protected const COMPARE_LOWER_THAN = '<';
		
		/**
		 * Instance de la Classe de gestion des Configs
		 * @var Config
		 */
		protected $ApplicationModule;

		/**
		 * Instance de la Classe de gestion des Configs
		 * @var Config
		 */
		protected $ConfigModule;

		/**
		 * Instance de la Classe de gestion des Sessions
		 * @var Session
		 */
		protected $SessionModule;
		
		/**
		 * Instance de la Classe de gestion des noms
		 * @var NameSupervisor
		 */
		protected $NameSupervisorModule;
		
		/**
		 * @var string
		 */
		private $attributeName = '';
		
		/**
		 * @var string
		 */
		private $value = '';
		
		/**
		 * @var array
		 */
		private $compareOptions = [];
		
		
		/**
		 * Service constructor.
		 */
		public function __construct()
		{
			$this->ApplicationModule = ApplicationKernel::getInstance();

			$this->ConfigModule = Config::getInstance();
			$this->SessionModule = Session::getInstance();
			$this->NameSupervisorModule = NameSupervisor::getInstance();
		}


		/* ------------------------ Fonctions de Chargement du Manager ------------------------ */

			/**
			 * Permet de Charger au préalable un Manager, pour accerder à des requêtes SQL
			 *
			 * @param string $manager
			 */
			protected function loadManager(string $manager) {
				$managerName = $this->NameSupervisorModule->getGatewayName($manager);
				$this->$managerName = $this->ApplicationModule->getManager($manager);
			}
		
		
		/* ------------------------ Fonctions Complémentaires ------------------------ */
		
			/**
			 * Supprime un Objet dans une liste
			 *
			 * @param array $lists
			 * @param string $attributeName
			 * @param $value
			 *
			 * @return array
			 */
			public function unsetObjectElement(array $lists, string $attributeName, $value, $compareOptions = [self::COMPARE_EQUALS]): array
			{
				$this->attributeName = $attributeName;
				$this->value = $value;
				$this->compareOptions = $compareOptions;
				
				return array_filter($lists, __CLASS__ . '::arrayCallback', ARRAY_FILTER_USE_BOTH);
			}
			
			protected function arrayCallback($value, $key)
			{
				if (!empty($this->attributeName)) {
					$attributeName = $this->attributeName;
					
					if (property_exists($value, $attributeName)) {
						if (in_array(self::COMPARE_GREATER_THAN, $this->compareOptions)) {
							if ($value->$attributeName > $this->value) {
								return false;
							}
						}
						
						if (in_array(self::COMPARE_EQUALS, $this->compareOptions)) {
							if ($value->$attributeName === $this->value) {
								return false;
							}
						}
						
						if (in_array(self::COMPARE_LOWER_THAN, $this->compareOptions)) {
							if ($value->$attributeName < $this->value) {
								return false;
							}
						}
					}
					
				}
				return true;
			}
			
			/**
			 * Supprime une entrée dans une liste
			 *
			 * @param array $lists
			 * @param $value
			 *
			 * @return array
			 */
			public function unsetElement(array $lists, $value, bool $strict = true): array
			{
				if(($key = array_search($value, $lists, $strict)) !== FALSE) {
					unset($lists[$key]);
				}
				
				return array_values($lists);
			}
		
			/**
			 * Permet de créer un tableau associatif à partir d'un objet
			 *
			 * @param array $array
			 * @param string $attributeKey
			 * @param string $attributeValue
			 *
			 * @return array
			 */
			protected function convertObjectArrayToStringArray(array $array, string $attributeKey, string $attributeValue): array
			{
				$stringArray = [];
				foreach ($array as $element) { $stringArray[$element->$attributeKey] = $element->$attributeValue; }
				return $stringArray;
			}
	}
?>