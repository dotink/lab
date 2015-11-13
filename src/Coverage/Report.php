<?php namespace Dotink\Lab\Coverage
{
	class Report implements ReportInterface
	{
		/**
		 *
		 */
		private $methods = array();


		/**
		 *
		 */
		private $methodCoverage = array();


		/**
		 *
		 */
		private $files = array();


		/**
		 *
		 */
		private $fileCoverage = array();


		/**
		 *
		 */
		public function __construct($base_directory)
		{
			$this->baseDirectory = realpath($base_directory);
		}


		/**
		 *
		 */
		public function addClass($class)
		{

		}


		/**
		 *
		 */
		public function addFunction($function)
		{

		}


		/**
		 *
		 */
		public function addMethod($method)
		{
			$this->methods[] = $method;
			$this->methodCoverage[$method->getPrettyName()] = [
				'dead'      => 0,
				'covered'   => 0,
				'uncovered' => 0
			];
		}


		/**
		 *
		 */
		public function addNamespace($namespace)
		{

		}


		/**
		 *
		 */
		public function checkFileCoverage($file, $type = NULL)
		{
			return $this->checkCoverage($this->fileCoverage, $file, $type);
		}


		/**
		 *
		 */
		public function checkMethodCoverage($method, $type = NULL)
		{
			return $this->checkCoverage($this->methodCoverage, $method, $type);
		}


		/**
		 *
		 */
		public function cleanFile($file)
		{
			$file = realpath($file);

			if (strpos($file, $this->baseDirectory) === 0) {
				return substr($file, strlen($this->baseDirectory) + 1);
			}

			return $file;
		}


		/**
		 *
		 */
		public function generate($data)
		{
			foreach (array_keys($data) as $file) {
				$this->source[$file]       = file($file);
				$this->fileCoverage[$file] = [
					'dead'      => 0,
					'covered'   => 0,
					'uncovered' => 0
				];
			}

			$this->generateMethodCoverage($data);
			$this->generateFunctionCoverage($data);


			//
			// TODO: Figure out what's not covered and put it somewhere
			//

		}


		/**
		 *
		 */
		public function generateMethodCoverage($data)
		{
			foreach ($this->methods as $method) {
				if (!$data[$file = $method->getFileName()]) {
					continue;
				}

				for ($x = $method->getStartLine(); $x <= $method->getEndLine(); $x++) {
					if (!isset($data[$file][$x])) {
						continue;
					}

					switch ($data[$file][$x]) {
						case 1:
							$this->fileCoverage[$file]['covered']++;
							$this->methodCoverage[$method->getPrettyName()]['covered']++;
							break;

						case -1:
							$this->fileCoverage[$file]['uncovered']++;
							$this->methodCoverage[$method->getPrettyName()]['uncovered']++;
							break;

						case -2:
							$this->fileCoverage[$file]['dead']++;
							$this->methodCoverage[$method->getPrettyName()]['dead']++;
							break;
					}
				}
			}
		}


		/**
		 *
		 */
		public function generateFunctionCoverage($data)
		{
			foreach ($this->functions as $function) {
			}
		}


		/**
		 *
		 */
		private function checkCoverage($coverage_data, $key, $type)
		{
			if (!isset($coverage_data[$key])) {
				return 'N/A';
			}

			extract($coverage_data[$key]);

			if (!$type) {
				return number_format($covered / ($covered + $uncovered) * 100, 2);
			}

			return $$type;
		}
	}
}
