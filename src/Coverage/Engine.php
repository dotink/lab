<?php namespace Dotink\Lab\Coverage
{
	use TokenReflection\Broker;

	class Engine
	{
		/**
		 * The reflection analysis broker
		 *
		 * @access private
		 * @var array
		 */
		private $broker = NULL;


		/**
		 * Classes which are being ignored
		 *
		 * @access private
		 * @var array
		 */
		private $ignoredClasses = array();


		/**
		 * Functions which are being ignored
		 *
		 * @access private
		 * @var array
		 */
		private $ignoredFunctions = array();


		/**
		 * Files which are being ignored
		 *
		 * @access private
		 * @var array
		 */
		private $ignoredFiles = array();


		/**
		 *
		 */
		private $ignoredMethods = array();


		/**
		 * Namespaces which are being ignored
		 *
		 * @access private
		 * @var array
		 */
		private $ignoredNamespaces = array();


		/**
		 * Files which are being preserved from ignore
		 *
		 * @access private
		 * @var array
		 */
		private $preservedFiles = array();


		/**
		 *
		 */
		private $started = FALSE;


		/**
		 * Create a new coverage engine
		 *
		 * @access public
		 * @return void
		 */
		public function __construct()
		{
		}


		/**
		 * Begin code coverage checks
		 *
		 * @access public
		 * @param ReportInterface $report The report to add information to
		 * @return void
		 */
		public function start(ReportInterface $report)
		{
			if (function_exists('xdebug_start_code_coverage')) {
				$this->started = TRUE;
				$this->report  = $report;
				xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
			}
		}


		/**
		 *
		 */
		public function ignoreFile($file)
		{
			$this->ignoredFiles[] = realpath($file);
		}


		/**
		 *
		 */
		public function ignoreClass($pattern)
		{
			$this->ignoredClasses[] = $pattern;
		}


		/**
		 *
		 */
		public function ignoreFunction($pattern)
		{
			$this->ignoredFunctions[] = $pattern;
		}


		/**
		 *
		 */
		public function ignoreNamespace($pattern)
		{
			$this->ignoredNamespaces[] = $pattern;
		}


		/**
		 *
		 */
		public function isStarted()
		{
			return $this->started;
		}

		/**
		 *
		 */
		public function preserveFile($file)
		{
			$this->preservedFiles[] = realpath($file);
		}


		/**
		 *
		 */
		public function processCoverageData($file)
		{
			if (in_array($file, $this->ignoredFiles)) {
				unset($this->coverageData[$file]);
				return;
			}

			$this->preserving = in_array($file, $this->preservedFiles);
			$file_reflection  = $this->broker->processFile($file, TRUE);
			$file_namespaces  = $file_reflection->getNamespaces();

			foreach ($file_namespaces as $namespace) {
				if ($this->removeByPattern($file, $namespace, $this->ignoredNamespaces)) {
					break;
				}

				$this->report->addNamespace($namespace);

				foreach ($namespace->getClasses() as $class) {
					if ($this->removeByPattern($file, $class, $this->ignoredClasses)) {
						break;
					}

					$this->report->addClass($class);

					foreach ($class->getMethods() as $method) {
						if ($this->removeByPattern($file, $method, $this->ignoredMethods)) {
							break;
						}

						$this->report->addMethod($method);
					}
				}

				foreach ($namespace->getFunctions() as $function) {
					if ($this->removeByPattern($file, $function, $this->ignoredFunctions)) {
						break;
					}

					$this->report->addFunction($file, $function);
				}
			}

			if (!count($this->coverageData[$file])) {
				unset($this->coverageData[$file]);
			}
		}


		/**
		 *
		 */
		public function stop(OutputterInterface $outputter = NULL)
		{
			if (!$this->isStarted()) {
				return;
			}

			$this->coverageData = xdebug_get_code_coverage();
			$this->broker       = new Broker(new Broker\Backend\Memory());

			foreach (array_keys($this->coverageData) as $file) {
				$this->processCoverageData($file);
			}

			$this->report->generate($this->coverageData);

			if ($this->coverageData) {

				echo PHP_EOL . "\t\033[37mCode Coverage\033[0m" . PHP_EOL;

				foreach (array_keys($this->coverageData) as $file) {
					echo PHP_EOL . sprintf(
						"\t" . '%s [ %s | %s ] (%s)',
						str_pad($this->report->checkFileCoverage($file) . '%', 7),
						str_pad($this->report->checkFileCoverage($file, 'covered'), 5),
						str_pad($this->report->checkFileCoverage($file, 'uncovered'), 5),
						$this->report->cleanFile($file)
					);
				}

				echo PHP_EOL;
			}


			xdebug_stop_code_coverage();
		}


		/**
		 *
		 */
		private function remove($file, $start, $end)
		{
			if ($end === NULL) {
				end($this->coverageData[$file]);

				$end = key($this->coverageData[$file]);
			}

			for ($x = $start; $x <= $end; $x++) {
				unset($this->coverageData[$file][$x]);
			}
		}


		/**
		 *
		 */
		private function removeByPattern($file, $reflection, $ignore_list)
		{
			if (!$this->preserving) {
				foreach ($ignore_list as $pattern) {
					$pattern = str_replace('\\', '\\\\', $pattern);

					if (!preg_match('#' . $pattern . '#i', $reflection->getName())) {
						continue;
					}

					$this->remove($file, $reflection->getStartLine(), $reflection->getEndLine());
					return TRUE;
				}
			}

			return FALSE;
		}
	}
}
