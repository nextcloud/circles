<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;

/**
 * ConsoleOutput is the default class for all CLI output. It uses STDOUT and STDERR.
 *
 * This class is a convenient wrapper around `StreamOutput` for both STDOUT and STDERR.
 *
 *     $output = new ConsoleOutput();
 *
 * This is equivalent to:
 *
 *     $output = new StreamOutput(fopen('php://stdout', 'w'));
 *     $stdErr = new StreamOutput(fopen('php://stderr', 'w'));
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ConsoleOutput extends StreamOutput implements ConsoleOutputInterface
{
    /**
     * @param int                           $verbosity The verbosity level (one of the VERBOSITY constants in OutputInterface)
     * @param bool|null                     $decorated Whether to decorate messages (null for auto-guessing)
     * @param OutputFormatterInterface|null $formatter Output formatter instance (null to use default OutputFormatter)
     */
    public function __construct(int $verbosity = self::VERBOSITY_NORMAL, ?bool $decorated = null, ?OutputFormatterInterface $formatter = null)
    {
    }

    /**
     * Creates a new output section.
     */
    public function section(): ConsoleSectionOutput
    {
    }

    /**
     * @return void
     */
    public function setDecorated(bool $decorated)
    {
    }

    /**
     * @return void
     */
    public function setFormatter(OutputFormatterInterface $formatter)
    {
    }

    /**
     * @return void
     */
    public function setVerbosity(int $level)
    {
    }

    public function getErrorOutput(): OutputInterface
    {
    }

    /**
     * @return void
     */
    public function setErrorOutput(OutputInterface $error)
    {
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDOUT.
     */
    protected function hasStdoutSupport(): bool
    {
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDERR.
     */
    protected function hasStderrSupport(): bool
    {
    }
}
