<?php

use Spatie\Browsershot\Browsershot as Browsershot;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;
use Symfony\Component\Process\Process;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Screenshot extends Browsershot
{
    public function createScreenshotCommand(string $workingDirectory): string
    {
        $command = 'cd '
            .escapeshellarg($workingDirectory)
            .';'
            .escapeshellarg($this->findChrome())
            .' --headless --screenshot --virtual-time-budget=10000 '
            .escapeshellarg($this->url);

        if ($this->disableGpu) {
            $command .= ' --disable-gpu';
        }

        if ($this->windowWidth > 0) {
            $command .= ' --window-size='
                .escapeshellarg($this->windowWidth)
                .','
                .escapeshellarg($this->windowHeight);
        }

        if ($this->hideScrollbars) {
            $command .= ' --hide-scrollbars';
        }

        if (! empty($this->userAgent)) {
            $command .= ' --user-agent='.escapeshellarg($this->userAgent);
        }

        return $command;
    }

    public function createDownloadCommand(string $workingDirectory): string
    {
        $command = 'cd '
            .escapeshellarg($workingDirectory)
            .';'
            .escapeshellarg($this->findChrome())
            .' --no-sandbox --headless --disable-dev-shm-usage --virtual-time-budget=10000 '
            .escapeshellarg($this->url);

        if ($this->disableGpu) {
            $command .= ' --disable-gpu';
        }

        if ($this->windowWidth > 0) {
            $command .= ' --window-size='
                .escapeshellarg($this->windowWidth)
                .','
                .escapeshellarg($this->windowHeight);
        }

        if ($this->hideScrollbars) {
            $command .= ' --hide-scrollbars';
        }

        if (! empty($this->userAgent)) {
            $command .= ' --user-agent='.escapeshellarg($this->userAgent);
        }

        return $command;
    }

    public function downloadExcel()
    {
        $temporaryDirectory = (new TemporaryDirectory())->create();

        try {
            $command = $this->createDownloadCommand($temporaryDirectory->path());

            $process = (new Process($command))->setTimeout($this->timeout);

            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } finally {
            $temporaryDirectory->delete();
        }
    }
}
