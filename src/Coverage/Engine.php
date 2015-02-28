<?php namespace Dotink\Lab\Coverage
{
	use TokenReflection\Broker;

	class Engine
	{
		private $broker = NULL;
		private $fileReflections = array();
		private $ignoredClasses = array();
		private $ignoredFunctions = array();
		private $ignoredFiles = array();
		private $ignoredNamespaces = array();
		private $preservedFiles = array();


		public function __construct(ReportInterface $report)
		{
			$this->report = $report;
		}

		/**
		 *
		 */
		public function start(ReportInterface $report)
		{
			xdebug_start_code_coverage(XDEBUG_CC_UNUSED);
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
		public function preserveFile($file)
		{
			$this->preservedFiles[] = realpath($file);
		}


		/**
		 *
		 */
		public function processCoverageData($file)
		{
			if (in_array($file, $this->preservedFiles)) {
				return;
			}

			if (in_array($file, $this->ignoredFiles)) {
				unset($this->coverageData[$file]);
				return;
			}

			$file_reflection = $this->broker->processFile($file, TRUE);
			$file_namespaces = $file_reflection->getNamespaces();

			foreach ($file_namespaces as $namespace) {
				foreach ($this->ignoredNamespaces as $pattern) {
					$pattern = str_replace('\\', '\\\\', $pattern);
					if (!preg_match('#' . $pattern . '#i', $namespace->getName())) {
						continue;
					}

					$this->remove($file, $namespace->getStartLine(), $namespace->getEndLine());
					break 2;
				}

				foreach ($namespace->getClasses() as $class) {
					foreach ($this->ignoredClasses as $pattern) {
						$pattern = str_replace('\\', '\\\\', $pattern);
						if (!preg_match('#' . $pattern . '#i', $class->getName())) {
							continue;
						}

						$this->remove($file, $class->getStartLine(), $class->getEndLine());
						break;
					}
				}

				foreach ($namespace->getFunctions() as $function) {
					foreach ($this->ignoredFunctions as $pattern) {
						$pattern = str_replace('\\', '\\\\', $pattern);
						if (!preg_match('#' . $pattern . '#i', $function->getName())) {
							continue;
						}

						$this->remove($file, $function->getStartLine(), $function->getEndLine());
						break;
					}
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
			$this->coverageData = xdebug_get_code_coverage();
			$this->broker       = new Broker(new Broker\Backend\Memory());

			foreach (array_keys($this->coverageData) as $file) {
				$this->processCoverageData($file);
			}

			if ($this->coverageData) {

				echo PHP_EOL . "\033[37mCode Coverage\033[0m" . PHP_EOL;

				foreach (array_keys($this->coverageData) as $file) {
					$data      = array_count_values($this->coverageData[$file]);
					$covered   = $data['1'];
					$uncovered = $data['-1'];
					$coverage  = $covered / ($covered + $uncovered) * 100;

					echo PHP_EOL . sprintf(
						"\t" . '%s [ %s | %s ] (%s)',
						str_pad(number_format($coverage, 2) . '%', 7),
						str_pad($covered, 5),
						str_pad($uncovered ?: '0', 5),
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
	}
}
