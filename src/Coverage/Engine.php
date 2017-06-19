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


		public function __construct($base_directory)
		{
			$this->baseDirectory = $base_directory;
		}


		/**
		 * Create a new coverage engine
		 *
		 * @access public
		 * @return void
		 */
		public function baseFile($file)
		{
			$file = realpath($file);

			if (strpos($file, $this->baseDirectory) === 0) {
				return substr($file, strlen($this->baseDirectory) + 1);
			}

			return $file;
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
			$this->ignoredFiles[] = realpath($file) ?: $file;
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
		public function process()
		{
			foreach (array_keys($this->coverageData) as $file) {
				if (in_array($file, $this->ignoredFiles)) {
					unset($this->coverageData[$file]);
					continue;
				}

				$this->preserving = in_array($file, $this->preservedFiles);
				$file_reflection  = $this->broker->processFile($file, TRUE);
				$file_namespaces  = $file_reflection->getNamespaces();

				foreach ($file_namespaces as $namespace) {
					if ($this->ignoreByPattern($file, $namespace, $this->ignoredNamespaces)) {
						break;
					}

					foreach ($namespace->getClasses() as $class) {
						if ($this->ignoreByPattern($file, $class, $this->ignoredClasses)) {
							break;
						}

						foreach ($class->getMethods() as $method) {
							if ($this->ignoreByPattern($file, $method, $this->ignoredMethods)) {
								break;
							}
						}
					}

					foreach ($namespace->getFunctions() as $function) {
						if ($this->ignoreByPattern($file, $function, $this->ignoredFunctions)) {
							break;
						}
					}
				}
			}

			return $this->coverageData;
		}


		/**
		 *
		 */
		public function stop(OutputterInterface $outputter = NULL)
		{
			if (!$this->isStarted()) {
				return;
			}

			xdebug_stop_code_coverage(FALSE);

			$output             = NULL;
			$this->backend      = new Broker\Backend\Memory();
			$this->broker       = new Broker($this->backend);
			$this->coverageData = xdebug_get_code_coverage();

			$this->report->generate(
				$this->process(),
				$this->broker
			);

			foreach (array_keys($this->report->getFiles()) as $file) {
				if (!$this->report->checkFileCoverage($file, 'tested')) {
					continue;
				}

				$output .= PHP_EOL . sprintf(
					"\t" . '%s [ %s | %s ] (%s)',
					str_pad($this->report->checkFileCoverage($file) . '%', 7),
					str_pad($this->report->checkFileCoverage($file, 'covered'), 5),
					str_pad($this->report->checkFileCoverage($file, 'uncovered'), 5),
					$this->baseFile($file)
				);
			}

			if ($output) {
				echo PHP_EOL . "\t\033[37mCode Coverage\033[0m" . PHP_EOL;
				echo $output;
				echo PHP_EOL;
			}
		}


		/**
		 *
		 */
		private function ignore($file, $start, $end)
		{
			if ($end === NULL) {
				end($this->coverageData[$file]);

				$end = key($this->coverageData[$file]);
			}

			for ($x = $start; $x <= $end; $x++) {
				$this->coverageData[$file][$x] = -3;
			}
		}


		/**
		 *
		 */
		private function ignoreByPattern($file, $reflection, $ignore_list)
		{
			if (!$this->preserving) {
				foreach ($ignore_list as $pattern) {
					$pattern = str_replace('\\', '\\\\', $pattern);

					if (!preg_match('#' . $pattern . '#i', $reflection->getName())) {
						continue;
					}

					$this->ignore($file, $reflection->getStartLine(), $reflection->getEndLine());
					return TRUE;
				}
			}

			return FALSE;
		}
	}
}
