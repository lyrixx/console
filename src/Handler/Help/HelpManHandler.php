<?php

/*
 * This file is part of the webmozart/console package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Console\Handler\Help;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Api\Handler\CommandHandler;
use Webmozart\Console\Api\IO\IO;
use Webmozart\Console\Assert\Assert;
use Webmozart\Console\Process\ProcessLauncher;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class HelpManHandler implements CommandHandler
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $manBinary;

    /**
     * @var ExecutableFinder
     */
    private $executableFinder;

    /**
     * @var ProcessLauncher
     */
    private $processLauncher;

    /**
     * Creates a new AsciiDoc descriptor.
     *
     * @param ExecutableFinder $executableFinder The finder used to find the
     *                                           "man" binary.
     * @param ProcessLauncher  $processLauncher  The launcher for executing the
     *                                           "man" binary.
     */
    public function __construct($path, ExecutableFinder $executableFinder = null, ProcessLauncher $processLauncher = null)
    {
        Assert::file($path);

        $this->path = $path;
        $this->executableFinder = $executableFinder ?: new ExecutableFinder();
        $this->processLauncher = $processLauncher ?: new ProcessLauncher();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Command $command, Args $args, IO $io)
    {
        if (!$this->processLauncher->isSupported()) {
            throw new RuntimeException('The ProcessLauncher must be supported for the man help to run.');
        }

        if (!$this->manBinary) {
            $this->manBinary = $this->executableFinder->find('man');
        }

        if (!$this->manBinary) {
            throw new RuntimeException('The "man" binary was not found.');
        }

        return $this->processLauncher->launchProcess(sprintf(
            '%s -l %s',
            $this->manBinary,
            escapeshellarg($this->path)
        ), false);
    }

    /**
     * @return string
     */
    public function getManBinary()
    {
        return $this->manBinary;
    }

    /**
     * @param string $manBinary
     */
    public function setManBinary($manBinary)
    {
        $this->manBinary = $manBinary;
    }
}
