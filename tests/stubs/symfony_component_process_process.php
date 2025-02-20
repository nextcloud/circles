<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Process;

use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Pipes\UnixPipes;
use Symfony\Component\Process\Pipes\WindowsPipes;

/**
 * Process is a thin wrapper around proc_* functions to easily
 * start independent PHP processes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Romain Neutron <imprec@gmail.com>
 *
 * @implements \IteratorAggregate<string, string>
 */
class Process implements \IteratorAggregate
{
    public const ERR = 'err';
    public const OUT = 'out';

    public const STATUS_READY = 'ready';
    public const STATUS_STARTED = 'started';
    public const STATUS_TERMINATED = 'terminated';

    public const STDIN = 0;
    public const STDOUT = 1;
    public const STDERR = 2;

    // Timeout Precision in seconds.
    public const TIMEOUT_PRECISION = 0.2;

    public const ITER_NON_BLOCKING = 1; // By default, iterating over outputs is a blocking call, use this flag to make it non-blocking
    public const ITER_KEEP_OUTPUT = 2;  // By default, outputs are cleared while iterating, use this flag to keep them in memory
    public const ITER_SKIP_OUT = 4;     // Use this flag to skip STDOUT while iterating
    public const ITER_SKIP_ERR = 8;

    /**
     * Exit codes translation table.
     *
     * User-defined errors must use exit codes in the 64-113 range.
     */
    public static $exitCodes = [
        0 => 'OK',
        1 => 'General error',
        2 => 'Misuse of shell builtins',

        126 => 'Invoked command cannot execute',
        127 => 'Command not found',
        128 => 'Invalid exit argument',

        // signals
        129 => 'Hangup',
        130 => 'Interrupt',
        131 => 'Quit and dump core',
        132 => 'Illegal instruction',
        133 => 'Trace/breakpoint trap',
        134 => 'Process aborted',
        135 => 'Bus error: "access to undefined portion of memory object"',
        136 => 'Floating point exception: "erroneous arithmetic operation"',
        137 => 'Kill (terminate immediately)',
        138 => 'User-defined 1',
        139 => 'Segmentation violation',
        140 => 'User-defined 2',
        141 => 'Write to pipe with no one reading',
        142 => 'Signal raised by alarm',
        143 => 'Termination (request to terminate)',
        // 144 - not defined
        145 => 'Child process terminated, stopped (or continued*)',
        146 => 'Continue if stopped',
        147 => 'Stop executing temporarily',
        148 => 'Terminal stop signal',
        149 => 'Background process attempting to read from tty ("in")',
        150 => 'Background process attempting to write to tty ("out")',
        151 => 'Urgent data available on socket',
        152 => 'CPU time limit exceeded',
        153 => 'File size limit exceeded',
        154 => 'Signal raised by timer counting virtual time: "virtual timer expired"',
        155 => 'Profiling timer expired',
        // 156 - not defined
        157 => 'Pollable event',
        // 158 - not defined
        159 => 'Bad syscall',
    ];

    /**
     * @param array          $command The command to run and its arguments listed as separate entries
     * @param string|null    $cwd     The working directory or null to use the working dir of the current PHP process
     * @param array|null     $env     The environment variables or null to use the same environment as the current PHP process
     * @param mixed          $input   The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout The timeout in seconds or null to disable
     *
     * @throws LogicException When proc_open is not installed
     */
    public function __construct(array $command, ?string $cwd = null, ?array $env = null, mixed $input = null, ?float $timeout = 60)
    {
    }

    /**
     * Creates a Process instance as a command-line to be run in a shell wrapper.
     *
     * Command-lines are parsed by the shell of your OS (/bin/sh on Unix-like, cmd.exe on Windows.)
     * This allows using e.g. pipes or conditional execution. In this mode, signals are sent to the
     * shell wrapper and not to your commands.
     *
     * In order to inject dynamic values into command-lines, we strongly recommend using placeholders.
     * This will save escaping values, which is not portable nor secure anyway:
     *
     *   $process = Process::fromShellCommandline('my_command "${:MY_VAR}"');
     *   $process->run(null, ['MY_VAR' => $theValue]);
     *
     * @param string         $command The command line to pass to the shell of the OS
     * @param string|null    $cwd     The working directory or null to use the working dir of the current PHP process
     * @param array|null     $env     The environment variables or null to use the same environment as the current PHP process
     * @param mixed          $input   The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout The timeout in seconds or null to disable
     *
     * @throws LogicException When proc_open is not installed
     */
    public static function fromShellCommandline(string $command, ?string $cwd = null, ?array $env = null, mixed $input = null, ?float $timeout = 60): static
    {
    }

    public function __sleep(): array
    {
    }

    /**
     * @return void
     */
    public function __wakeup()
    {
    }

    public function __destruct()
    {
    }

    public function __clone()
    {
    }

    /**
     * Runs the process.
     *
     * The callback receives the type of output (out or err) and
     * some bytes from the output in real-time. It allows to have feedback
     * from the independent process during execution.
     *
     * The STDOUT and STDERR are also available after the process is finished
     * via the getOutput() and getErrorOutput() methods.
     *
     * @param callable|null $callback A PHP callback to run whenever there is some
     *                                output available on STDOUT or STDERR
     *
     * @return int The exit status code
     *
     * @throws RuntimeException         When process can't be launched
     * @throws RuntimeException         When process is already running
     * @throws ProcessTimedOutException When process timed out
     * @throws ProcessSignaledException When process stopped after receiving signal
     * @throws LogicException           In case a callback is provided and output has been disabled
     *
     * @final
     */
    public function run(?callable $callback = null, array $env = []): int
    {
    }

    /**
     * Runs the process.
     *
     * This is identical to run() except that an exception is thrown if the process
     * exits with a non-zero exit code.
     *
     * @return $this
     *
     * @throws ProcessFailedException if the process didn't terminate successfully
     *
     * @final
     */
    public function mustRun(?callable $callback = null, array $env = []): static
    {
    }

    /**
     * Starts the process and returns after writing the input to STDIN.
     *
     * This method blocks until all STDIN data is sent to the process then it
     * returns while the process runs in the background.
     *
     * The termination of the process can be awaited with wait().
     *
     * The callback receives the type of output (out or err) and some bytes from
     * the output in real-time while writing the standard input to the process.
     * It allows to have feedback from the independent process during execution.
     *
     * @param callable|null $callback A PHP callback to run whenever there is some
     *                                output available on STDOUT or STDERR
     *
     * @return void
     *
     * @throws RuntimeException When process can't be launched
     * @throws RuntimeException When process is already running
     * @throws LogicException   In case a callback is provided and output has been disabled
     */
    public function start(?callable $callback = null, array $env = [])
    {
    }

    /**
     * Restarts the process.
     *
     * Be warned that the process is cloned before being started.
     *
     * @param callable|null $callback A PHP callback to run whenever there is some
     *                                output available on STDOUT or STDERR
     *
     * @throws RuntimeException When process can't be launched
     * @throws RuntimeException When process is already running
     *
     * @see start()
     *
     * @final
     */
    public function restart(?callable $callback = null, array $env = []): static
    {
    }

    /**
     * Waits for the process to terminate.
     *
     * The callback receives the type of output (out or err) and some bytes
     * from the output in real-time while writing the standard input to the process.
     * It allows to have feedback from the independent process during execution.
     *
     * @param callable|null $callback A valid PHP callback
     *
     * @return int The exitcode of the process
     *
     * @throws ProcessTimedOutException When process timed out
     * @throws ProcessSignaledException When process stopped after receiving signal
     * @throws LogicException           When process is not yet started
     */
    public function wait(?callable $callback = null): int
    {
    }

    /**
     * Waits until the callback returns true.
     *
     * The callback receives the type of output (out or err) and some bytes
     * from the output in real-time while writing the standard input to the process.
     * It allows to have feedback from the independent process during execution.
     *
     * @throws RuntimeException         When process timed out
     * @throws LogicException           When process is not yet started
     * @throws ProcessTimedOutException In case the timeout was reached
     */
    public function waitUntil(callable $callback): bool
    {
    }

    /**
     * Returns the Pid (process identifier), if applicable.
     *
     * @return int|null The process id if running, null otherwise
     */
    public function getPid(): ?int
    {
    }

    /**
     * Sends a POSIX signal to the process.
     *
     * @param int $signal A valid POSIX signal (see https://php.net/pcntl.constants)
     *
     * @return $this
     *
     * @throws LogicException   In case the process is not running
     * @throws RuntimeException In case --enable-sigchild is activated and the process can't be killed
     * @throws RuntimeException In case of failure
     */
    public function signal(int $signal): static
    {
    }

    /**
     * Disables fetching output and error output from the underlying process.
     *
     * @return $this
     *
     * @throws RuntimeException In case the process is already running
     * @throws LogicException   if an idle timeout is set
     */
    public function disableOutput(): static
    {
    }

    /**
     * Enables fetching output and error output from the underlying process.
     *
     * @return $this
     *
     * @throws RuntimeException In case the process is already running
     */
    public function enableOutput(): static
    {
    }

    /**
     * Returns true in case the output is disabled, false otherwise.
     */
    public function isOutputDisabled(): bool
    {
    }

    /**
     * Returns the current output of the process (STDOUT).
     *
     * @throws LogicException in case the output has been disabled
     * @throws LogicException In case the process is not started
     */
    public function getOutput(): string
    {
    }

    /**
     * Returns the output incrementally.
     *
     * In comparison with the getOutput method which always return the whole
     * output, this one returns the new output since the last call.
     *
     * @throws LogicException in case the output has been disabled
     * @throws LogicException In case the process is not started
     */
    public function getIncrementalOutput(): string
    {
    }

    /**
     * Returns an iterator to the output of the process, with the output type as keys (Process::OUT/ERR).
     *
     * @param int $flags A bit field of Process::ITER_* flags
     *
     * @return \Generator<string, string>
     *
     * @throws LogicException in case the output has been disabled
     * @throws LogicException In case the process is not started
     */
    public function getIterator(int $flags = 0): \Generator
    {
    }

    /**
     * Clears the process output.
     *
     * @return $this
     */
    public function clearOutput(): static
    {
    }

    /**
     * Returns the current error output of the process (STDERR).
     *
     * @throws LogicException in case the output has been disabled
     * @throws LogicException In case the process is not started
     */
    public function getErrorOutput(): string
    {
    }

    /**
     * Returns the errorOutput incrementally.
     *
     * In comparison with the getErrorOutput method which always return the
     * whole error output, this one returns the new error output since the last
     * call.
     *
     * @throws LogicException in case the output has been disabled
     * @throws LogicException In case the process is not started
     */
    public function getIncrementalErrorOutput(): string
    {
    }

    /**
     * Clears the process output.
     *
     * @return $this
     */
    public function clearErrorOutput(): static
    {
    }

    /**
     * Returns the exit code returned by the process.
     *
     * @return int|null The exit status code, null if the Process is not terminated
     */
    public function getExitCode(): ?int
    {
    }

    /**
     * Returns a string representation for the exit code returned by the process.
     *
     * This method relies on the Unix exit code status standardization
     * and might not be relevant for other operating systems.
     *
     * @return string|null A string representation for the exit status code, null if the Process is not terminated
     *
     * @see http://tldp.org/LDP/abs/html/exitcodes.html
     * @see http://en.wikipedia.org/wiki/Unix_signal
     */
    public function getExitCodeText(): ?string
    {
    }

    /**
     * Checks if the process ended successfully.
     */
    public function isSuccessful(): bool
    {
    }

    /**
     * Returns true if the child process has been terminated by an uncaught signal.
     *
     * It always returns false on Windows.
     *
     * @throws LogicException In case the process is not terminated
     */
    public function hasBeenSignaled(): bool
    {
    }

    /**
     * Returns the number of the signal that caused the child process to terminate its execution.
     *
     * It is only meaningful if hasBeenSignaled() returns true.
     *
     * @throws RuntimeException In case --enable-sigchild is activated
     * @throws LogicException   In case the process is not terminated
     */
    public function getTermSignal(): int
    {
    }

    /**
     * Returns true if the child process has been stopped by a signal.
     *
     * It always returns false on Windows.
     *
     * @throws LogicException In case the process is not terminated
     */
    public function hasBeenStopped(): bool
    {
    }

    /**
     * Returns the number of the signal that caused the child process to stop its execution.
     *
     * It is only meaningful if hasBeenStopped() returns true.
     *
     * @throws LogicException In case the process is not terminated
     */
    public function getStopSignal(): int
    {
    }

    /**
     * Checks if the process is currently running.
     */
    public function isRunning(): bool
    {
    }

    /**
     * Checks if the process has been started with no regard to the current state.
     */
    public function isStarted(): bool
    {
    }

    /**
     * Checks if the process is terminated.
     */
    public function isTerminated(): bool
    {
    }

    /**
     * Gets the process status.
     *
     * The status is one of: ready, started, terminated.
     */
    public function getStatus(): string
    {
    }

    /**
     * Stops the process.
     *
     * @param int|float $timeout The timeout in seconds
     * @param int|null  $signal  A POSIX signal to send in case the process has not stop at timeout, default is SIGKILL (9)
     *
     * @return int|null The exit-code of the process or null if it's not running
     */
    public function stop(float $timeout = 10, ?int $signal = null): ?int
    {
    }

    /**
     * Adds a line to the STDOUT stream.
     *
     * @internal
     */
    public function addOutput(string $line): void
    {
    }

    /**
     * Adds a line to the STDERR stream.
     *
     * @internal
     */
    public function addErrorOutput(string $line): void
    {
    }

    /**
     * Gets the last output time in seconds.
     */
    public function getLastOutputTime(): ?float
    {
    }

    /**
     * Gets the command line to be executed.
     */
    public function getCommandLine(): string
    {
    }

    /**
     * Gets the process timeout in seconds (max. runtime).
     */
    public function getTimeout(): ?float
    {
    }

    /**
     * Gets the process idle timeout in seconds (max. time since last output).
     */
    public function getIdleTimeout(): ?float
    {
    }

    /**
     * Sets the process timeout (max. runtime) in seconds.
     *
     * To disable the timeout, set this value to null.
     *
     * @return $this
     *
     * @throws InvalidArgumentException if the timeout is negative
     */
    public function setTimeout(?float $timeout): static
    {
    }

    /**
     * Sets the process idle timeout (max. time since last output) in seconds.
     *
     * To disable the timeout, set this value to null.
     *
     * @return $this
     *
     * @throws LogicException           if the output is disabled
     * @throws InvalidArgumentException if the timeout is negative
     */
    public function setIdleTimeout(?float $timeout): static
    {
    }

    /**
     * Enables or disables the TTY mode.
     *
     * @return $this
     *
     * @throws RuntimeException In case the TTY mode is not supported
     */
    public function setTty(bool $tty): static
    {
    }

    /**
     * Checks if the TTY mode is enabled.
     */
    public function isTty(): bool
    {
    }

    /**
     * Sets PTY mode.
     *
     * @return $this
     */
    public function setPty(bool $bool): static
    {
    }

    /**
     * Returns PTY state.
     */
    public function isPty(): bool
    {
    }

    /**
     * Gets the working directory.
     */
    public function getWorkingDirectory(): ?string
    {
    }

    /**
     * Sets the current working directory.
     *
     * @return $this
     */
    public function setWorkingDirectory(string $cwd): static
    {
    }

    /**
     * Gets the environment variables.
     */
    public function getEnv(): array
    {
    }

    /**
     * Sets the environment variables.
     *
     * @param array<string|\Stringable> $env The new environment variables
     *
     * @return $this
     */
    public function setEnv(array $env): static
    {
    }

    /**
     * Gets the Process input.
     *
     * @return resource|string|\Iterator|null
     */
    public function getInput()
    {
    }

    /**
     * Sets the input.
     *
     * This content will be passed to the underlying process standard input.
     *
     * @param string|resource|\Traversable|self|null $input The content
     *
     * @return $this
     *
     * @throws LogicException In case the process is running
     */
    public function setInput(mixed $input): static
    {
    }

    /**
     * Performs a check between the timeout definition and the time the process started.
     *
     * In case you run a background process (with the start method), you should
     * trigger this method regularly to ensure the process timeout
     *
     * @return void
     *
     * @throws ProcessTimedOutException In case the timeout was reached
     */
    public function checkTimeout()
    {
    }

    /**
     * @throws LogicException in case process is not started
     */
    public function getStartTime(): float
    {
    }

    /**
     * Defines options to pass to the underlying proc_open().
     *
     * @see https://php.net/proc_open for the options supported by PHP.
     *
     * Enabling the "create_new_console" option allows a subprocess to continue
     * to run after the main process exited, on both Windows and *nix
     *
     * @return void
     */
    public function setOptions(array $options)
    {
    }

    /**
     * Returns whether TTY is supported on the current operating system.
     */
    public static function isTtySupported(): bool
    {
    }

    /**
     * Returns whether PTY is supported on the current operating system.
     */
    public static function isPtySupported(): bool
    {
    }

    /**
     * Builds up the callback used by wait().
     *
     * The callbacks adds all occurred output to the specific buffer and calls
     * the user callback (if present) with the received output.
     *
     * @param callable|null $callback The user defined PHP callback
     */
    protected function buildCallback(?callable $callback = null): \Closure
    {
    }

    /**
     * Updates the status of the process, reads pipes.
     *
     * @param bool $blocking Whether to use a blocking read call
     *
     * @return void
     */
    protected function updateStatus(bool $blocking)
    {
    }

    /**
     * Returns whether PHP has been compiled with the '--enable-sigchild' option or not.
     */
    protected function isSigchildEnabled(): bool
    {
    }
}
