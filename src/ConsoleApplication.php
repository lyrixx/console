<?php

/*
 * This file is part of the webmozart/console package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Console;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Webmozart\Console\Adapter\ApplicationAdapter;
use Webmozart\Console\Adapter\OutputInterfaceAdapter;
use Webmozart\Console\Adapter\CompositeInput;
use Webmozart\Console\Api\Application\Application;
use Webmozart\Console\Api\Args\Format\ArgsFormat;
use Webmozart\Console\Api\Args\RawArgs;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Api\Command\CommandCollection;
use Webmozart\Console\Api\Command\NamedCommand;
use Webmozart\Console\Api\Config\ApplicationConfig;
use Webmozart\Console\Api\Input\Input;
use Webmozart\Console\Api\Output\Output;
use Webmozart\Console\Args\ArgvArgs;
use Webmozart\Console\Adapter\CompositeOutput;
use Webmozart\Console\Input\StandardInput;

/**
 * A console application.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ConsoleApplication implements Application
{
    /**
     * @var ApplicationConfig
     */
    private $config;

    /**
     * @var CommandCollection
     */
    private $commands;

    /**
     * @var Command[]
     */
    private $unnamedCommands = array();

    /**
     * @var Command[]
     */
    private $defaultCommands = array();

    /**
     * @var ArgsFormat
     */
    private $globalArgsFormat;

    /**
     * @var ApplicationAdapter
     */
    private $applicationAdapter;

    /**
     * Creates a new console application.
     *
     * @param ApplicationConfig $config The application configuration.
     */
    public function __construct(ApplicationConfig $config)
    {
        $this->config = $config;
        $this->commands = new CommandCollection();

        $this->globalArgsFormat = new ArgsFormat(array_merge(
            $config->getOptions(),
            $config->getArguments()
        ));

        foreach ($config->getCommandConfigs() as $commandConfig) {
            if ($commandConfig->isEnabled()) {
                $this->commands->add(new NamedCommand($commandConfig, $this));
            }
        }

        foreach ($config->getUnnamedCommandConfigs() as $commandConfig) {
            $this->unnamedCommands[] = $this->defaultCommands[] = new Command($commandConfig, $this);
        }

        foreach ($config->getDefaultCommands() as $commandName) {
            $this->defaultCommands[] = $this->commands->get($commandName);
        }

        $this->applicationAdapter = new ApplicationAdapter($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobalArgsFormat()
    {
        return $this->globalArgsFormat;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand($name)
    {
        return $this->commands->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        return clone $this->commands;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCommand($name)
    {
        return $this->commands->contains($name);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCommands()
    {
        return !$this->commands->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function getUnnamedCommands()
    {
        return $this->unnamedCommands;
    }

    /**
     * {@inheritdoc}
     */
    public function hasUnnamedCommands()
    {
        return count($this->unnamedCommands) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultCommands()
    {
        return $this->defaultCommands;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDefaultCommands()
    {
        return count($this->defaultCommands) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function run(RawArgs $args = null, Input $input = null, Output $output = null, Output $errorOutput = null)
    {
        $dimensions = $this->config->getOutputDimensions();
        $styleSet = $this->config->getStyleSet();

        if (null === $args) {
            $args = new ArgvArgs();
        }

        if (null === $input) {
            $input = new StandardInput();
        }

        if (null === $output) {
            $output = new OutputInterfaceAdapter(new ConsoleOutput(), $dimensions);
        }

        if (null === $errorOutput) {
            $errorOutput = $output instanceof ConsoleOutputInterface
                ? new OutputInterfaceAdapter($output->getErrorOutput(), $dimensions)
                : $output;
        }

        $output->setDimensions($dimensions);
        $errorOutput->setDimensions($dimensions);

        if ($styleSet) {
            $output->setStyleSet($styleSet);
            $errorOutput->setStyleSet($styleSet);
        }

        // Wrap inputs and outputs while passing them through Symfony
        return $this->applicationAdapter->run(
            new CompositeInput($args, $input),
            new CompositeOutput($output, $errorOutput)
        );
    }
}
