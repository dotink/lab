<?php namespace Dotink\Lab\Coverage
{
	class Report implements ReportInterface
	{
		/**
		 *
		 */
		private $classes = array();


		/**
		 *
		 */
		private $classCoverage = array();


		/**
		 *
		 */
		private $fileCoverage = array();


		/**
		 *
		 */
		private $files = array();


		/**
		 *
		 */
		private $functionCoverage = array();


		/**
		 *
		 */
		private $functions = array();


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
		private $namespaces = array();


		/**
		 *
		 */
		private $namespaceCoverage = array();


		/**
		 *
		 */
		public function checkClassCoverage($class, $type = NULL)
		{
			return $this->checkCoverage($this->fileCoverage, $file, $type);
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
		public function checkFunctionCoverage($function, $type = NULL)
		{
			return $this->checkCoverage($this->functionCoverage, $function, $type);
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
		public function checkNamespaceCoverage($namespace, $type = NULL)
		{
			return $this->checkCoverage($this->namespaceCoverage, $method, $type);
		}


		/**
		 *
		 */
		public function generate($data, $broker)
		{
			foreach (array_keys($data) as $file) {
				$values                    = array_count_values($data[$file]);
				$this->files[$file]        = $broker->processFile($file, TRUE);
				$this->fileCoverage[$file] = [
					'dead'      => isset($values[-2]) ? $values[-2] : 0,
					'ignored'   => isset($values[-3]) ? $values[-3] : 0,
					'uncovered' => isset($values[-1]) ? $values[-1] : 0,
					'covered'   => isset($values[1])  ? $values[1]  : 0
				];
			}

			foreach ($this->files as $file) {
				foreach ($file->getNamespaces() as $namespace) {

					$this->generateFunctionCoverage($namespace, $data[$file->getName()]);

					foreach ($namespace->getClasses() as $class) {
						$this->classes[$class->getPrettyName()] = $class;

						$this->generateMethodCoverage($namespace, $class, $data[$file->getName()]);
					}
				}
			}
		}


		/**
		 *
		 */
		public function getClasses()
		{
			return $this->classes;
		}


		/**
		 *
		 */
		public function getFiles()
		{
			return $this->files;
		}


		/**
		 *
		 */
		public function getFunctions()
		{
			return $this->functions;
		}


		/**
		 *
		 */
		public function getMethods()
		{
			return $this->methods;
		}


		/**
		 *
		 */
		public function getNamespaces()
		{
			return $this->namespaces;
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
				return number_format($covered / ($covered + $uncovered) * 100, 2). '%';
			}

			switch ($type) {
				case 'tested':
					return $covered + $uncovered;

				case 'dead':
				case 'ignored':
				case 'covered':
				case 'uncovered':
					return $$type;

				default:
					return 'N/A';
			}
		}


		/**
		 *
		 */
		private function generateMethodCoverage($namespace, $class, $data)
		{
			$this->classes[$class_name = $class->getPrettyName()]            = $class;
			$this->namespaces[$namespace_name = $namespace->getPrettyName()] = $namespace;

			foreach ($class->getMethods() as $method) {

				$this->$methods[$method_name = $method->getPrettyName()] = $method;

				for ($x = $method->getStartLine(); $x <= $method->getEndLine(); $x++) {
					if (!isset($data[$x])) {
						continue;
					}

					switch ($data[$x]) {
						case 1:
							$this->classCoverage[$class_name]['covered']++;
							$this->methodCoverage[$method_name]['covered']++;
							$this->namespaceCoverage[$namespace_name]['covered']++;
							break;

						case -1:
							$this->classCoverage[$class_name]['uncovered']++;
							$this->methodCoverage[$method_name]['uncovered']++;
							$this->namespaceCoverage[$namespace_name]['uncovered']++;
							break;

						case -2:
							$this->classCoverage[$class_name]['dead']++;
							$this->methodCoverage[$method_name]['dead']++;
							$this->namespaceCoverage[$namespace_name]['dead']++;
							break;

						case -3:
							$this->classCoverage[$class_name]['ignored']++;
							$this->methodCoverage[$method_name]['ignored']++;
							$this->namespaceCoverage[$namespace_name]['ignored']++;
							break;
					}
				}
			}
		}


		/**
		 *
		 */
		private function generateFunctionCoverage($namespace, $data)
		{
			$this->namespaces[$namespace_name = $namespace->getPrettyName()] = $namespace;

			foreach ($namespace->getFunctions() as $function) {

				$this->$functions[$function_name = $function->getPrettyName()] = $function;

				for ($x = $function->getStartLine(); $x <= $function->getEndLine(); $x++) {
					if (!isset($data[$x])) {
						continue;
					}

					switch ($data[$x]) {
						case 1:
							$this->namespaceCoverage[$namespace_name]['covered']++;
							$this->functionCoverage[$function_name]['covered']++;
							break;

						case -1:
							$this->namespaceCoverage[$namespace_name]['uncovered']++;
							$this->functionCoverage[$function_name]['uncovered']++;
							break;

						case -2:
							$this->namespaceCoverage[$namespace_name]['dead']++;
							$this->functionCoverage[$function_name]['dead']++;
							break;

						case -3:
							$this->namespaceCoverage[$namespace_name]['ignored']++;
							$this->functionCoverage[$function_name]['ignored']++;
							break;
					}
				}
			}
		}
	}
}
