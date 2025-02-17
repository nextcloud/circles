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

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

/**
 * Base class for output classes.
 *
 * There are five levels of verbosity:
 *
 *  * normal: no option passed (normal output)
 *  * verbose: -v (more output)
 *  * very verbose: -vv (highly extended output)
 *  * debug: -vvv (all debug output)
 *  * quiet: -q (no output)
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class Output implements OutputInterface
{
    /**
     * @param int|null                      $verbosity The verbosity level (one of the VERBOSITY constants in OutputInterface)
     * @param bool                          $decorated Whether to decorate messages
     * @param OutputFormatterInterface|null $formatter Output formatter instance (null to use default OutputFormatter)
     */
    public function __construct(?int $verbosity = self::VERBOSITY_NORMAL, bool $decorated = false, ?OutputFormatterInterface $formatter = null)
    {
    }

    /**
     * @return void
     */
    public function setFormatter(OutputFormatterInterface $formatter)
    {
    }

    public function getFormatter(): OutputFormatterInterface
    {
    }

    /**
     * @return void
     */
    public function setDecorated(bool $decorated)
    {
    }

    public function isDecorated(): bool
    {
    }

    /**
     * @return void
     */
    public function setVerbosity(int $level)
    {
    }

    public function getVerbosity(): int
    {
    }

    public function isQuiet(): bool
    {
    }

    public function isVerbose(): bool
    {
    }

    public function isVeryVerbose(): bool
    {
    }

    public function isDebug(): bool
    {
    }

    /**
     * @return void
     */
    public function writeln(string|iterable $messages, int $options = self::OUTPUT_NORMAL)
    {
    }

    /**
     * @return void
     */
    public function write(string|iterable $messages, bool $newline = false, int $options = self::OUTPUT_NORMAL)
    {
    }

    /**
     * Writes a message to the output.
     *
     * @return void
     */
    abstract protected function doWrite(string $message, bool $newline)
    {
    }
}
