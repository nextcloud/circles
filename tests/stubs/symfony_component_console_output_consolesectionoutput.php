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
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Terminal;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 * @author Gabriel Ostrolucký <gabriel.ostrolucky@gmail.com>
 */
class ConsoleSectionOutput extends StreamOutput
{
    /**
     * @param resource               $stream
     * @param ConsoleSectionOutput[] $sections
     */
    public function __construct($stream, array &$sections, int $verbosity, bool $decorated, OutputFormatterInterface $formatter)
    {
    }

    /**
     * Defines a maximum number of lines for this section.
     *
     * When more lines are added, the section will automatically scroll to the
     * end (i.e. remove the first lines to comply with the max height).
     */
    public function setMaxHeight(int $maxHeight): void
    {
    }

    /**
     * Clears previous output for this section.
     *
     * @param int $lines Number of lines to clear. If null, then the entire output of this section is cleared
     */
    public function clear(?int $lines = null): void
    {
    }

    /**
     * Overwrites the previous output with a new message.
     */
    public function overwrite(string|iterable $message): void
    {
    }

    public function getContent(): string
    {
    }

    public function getVisibleContent(): string
    {
    }

    /**
     * @internal
     */
    public function addContent(string $input, bool $newline = true): int
    {
    }

    /**
     * @internal
     */
    public function addNewLineOfInputSubmit(): void
    {
    }

    protected function doWrite(string $message, bool $newline): void
    {
    }
}
