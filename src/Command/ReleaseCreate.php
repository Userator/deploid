<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Deploid\Payload;

/**
 * @method \Deploid\Application getApplication() return application object
 */
class ReleaseCreate extends Command {

	protected function configure() {
		$this->setName('release:create');
		$this->setDescription('Creates new release directory');
		$this->setHelp('This command creates a release directory');
		$this->addArgument('release', InputArgument::OPTIONAL, 'release name', date('YmdHis'));
		$this->addArgument('path', InputArgument::OPTIONAL, 'structure path', getcwd());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$payload = $this->getApplication()->deploidStructureValidate($input->getArgument('release'), $input->getArgument('path'));
		if ($payload->getType() == Payload::STRUCTURE_VALIDATE_FAIL) {
			$output->writeln($payload->getMessage());
			return $payload->getCode();
		}

		$payload = $this->getApplication()->deploidReleaseCreate($input->getArgument('release'), $input->getArgument('path'));
		if ($payload->getType() == Payload::RELEASE_CREATE_FAIL) {
			$output->writeln($payload->getMessage());
			return $payload->getCode();
		}

		$output->writeln($payload->getMessage());

		return $payload->getCode();
	}

}