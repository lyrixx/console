<?php

/*
 * This file is part of the webmozart/console package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Console\Adapter;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Console\Api\Command\NamedCommand;
use Webmozart\Console\Assert\Assert;

/**
 * Adapts a `Command` instance of this package to Symfony's {@link Command} API.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CommandAdapter extends Command
{
    /**
     * @var \Webmozart\Console\Api\Command\Command
     */
    private $adaptedCommand;

    /**
     * Creates the adapter.
     *
     * @param \Webmozart\Console\Api\Command\Command $adaptedCommand The adapted command.
     * @param Application                            $application    The application.
     */
    public function __construct(\Webmozart\Console\Api\Command\Command $adaptedCommand, Application $application)
    {
        parent::setName($this->createName($adaptedCommand, $application));

        parent::__construct();

        $this->adaptedCommand = $adaptedCommand;

        $config = $adaptedCommand->getConfig();

        parent::setDefinition(new ArgsFormatAdapter($this->adaptedCommand->getArgsFormat()));
        parent::setApplication($application);
        parent::setDescription($config->getDescription());
        parent::setHelp($config->getHelp());

        if ($adaptedCommand instanceof NamedCommand) {
            parent::setAliases($adaptedCommand->getAliases());
        }

        if ($helperSet = $config->getHelperSet()) {
            parent::setHelperSet($helperSet);
        }
    }

    /**
     * Returns the adapted command.
     *
     * @return Command The adapted command.
     */
    public function getAdaptedCommand()
    {
        return $this->adaptedCommand;
    }

    /**
     * Does nothing.
     *
     * @param Application $application The application.
     *
     * @return static The current instance.
     */
    public function setApplication(Application $application = null)
    {
        return $this;
    }

    /**
     * Does nothing.
     *
     * @param HelperSet $helperSet The helper set.
     *
     * @return static The current instance.
     */
    public function setHelperSet(HelperSet $helperSet)
    {
        return $this;
    }

    /**
     * Does nothing.
     *
     * @param callable $code The code.
     *
     * @return static The current instance.
     */
    public function setCode($code)
    {
        return $this;
    }

    /**
     * Does nothing.
     *
     * @param array|InputDefinition $definition The definition
     *
     * @return static The current instance.
     */
    public function setDefinition($definition)
    {
        return $this;
    }

    /**
     * Does nothing.
     *
     * @param string $name The name.
     *
     * @return static The current instance.
     */
    public function setName($name)
    {
        return $this;
    }

    /**
     * Does nothing.
     *
     * @param string $title The process title.
     *
     * @return static The current instance.
     */
    public function setProcessTitle($title)
    {
        return $this;
    }

    /**
     * Does nothing.
     *
     * @param string $description The description.
     *
     * @return static The current instance.
     */
    public function setDescription($description)
    {
        return $this;
    }

    /**
     * Does nothing.
     *
     * @param string $help The help.
     *
     * @return static The current instance.
     */
    public function setHelp($help)
    {
        return $this;
    }

    /**
     * Does nothing.
     *
     * @param string[] $aliases The aliases.
     *
     * @return static The current instance.
     */
    public function setAliases($aliases)
    {
        return $this;
    }

    /**
     * Does nothing.
     *
     * @param bool $mergeArgs
     *
     * @return static The current instance.
     */
    public function mergeApplicationDefinition($mergeArgs = true)
    {
        return $this;
    }

    /**
     * Does nothing.
     *
     * @param string $name
     * @param null   $mode
     * @param string $description
     * @param null   $default
     *
     * @return static The current instance.
     */
    public function addArgument($name, $mode = null, $description = '', $default = null)
    {
        return $this;
    }

    /**
     * Does nothing.
     *
     * @param string $name
     * @param null   $shortcut
     * @param null   $mode
     * @param string $description
     * @param null   $default
     *
     * @return static The current instance.
     */
    public function addOption($name, $shortcut = null, $mode = null, $description = '', $default = null)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->adaptedCommand->getConfig()->isEnabled();
    }

    /**
     * Executes the command.
     *
     * @param InputInterface  $input  The console input.
     * @param OutputInterface $output The console output.
     *
     * @return int The exit status.
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        /** @var ArgsAdapter $input */
        /** @var IOAdapter $output */
        Assert::isInstanceOf($input, 'Webmozart\Console\Adapter\ArgsAdapter');
        Assert::isInstanceOf($output, 'Webmozart\Console\Adapter\IOAdapter');

        return $this->adaptedCommand->handle($input->getArgs(), $output->getIO());
    }

    private function createName(\Webmozart\Console\Api\Command\Command $command, Application $application)
    {
        if ($command instanceof NamedCommand) {
            return $command->getName();
        }

        $i = 1;

        while ($application->has('unnamed'.$i)) {
            ++$i;
        }

        return 'unnamed'.$i;
    }
}
