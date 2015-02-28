<?php namespace Dotink\Lab\Coverage
{
	class Report implements ReportInterface
	{
		public function __construct($base_directory)
		{
			$this->baseDirectory = realpath($base_directory);
		}

		public function cleanFile($file)
		{
			$file = realpath($file);

			if (strpos($file, $this->baseDirectory) === 0) {
				return substr($file, strlen($this->baseDirectory) + 1);
			}

			return $file;
		}
	}
}
