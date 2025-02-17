<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleList extends Command
{
    protected $signature = 'schedule:list';

    protected $description = 'List when scheduled commands are executed.';

    /**
     * @var Schedule
     */
    protected $schedule;

    /**
     * ScheduleList constructor.
     */
    public function __construct(Schedule $schedule)
    {
        parent::__construct();

        $this->schedule = $schedule;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $events = array_map(function ($event) {
            return [
                'cron' => $event->expression,
                'description' => $event->description,
                'command' => static::fixupCommand($event->command),
            ];
        }, $this->schedule->events());

        $this->table(
            ['Cron', 'Description', 'Command'],
            $events
        );
    }

    /**
     * If it's an artisan command, strip off the PHP
     *
     * @return string
     */
    protected static function fixupCommand($command)
    {
        $parts = explode(' ', $command);
        if (count($parts) > 2 && $parts[1] === "'artisan'") {
            array_shift($parts);
        }

        return implode(' ', $parts);
    }
}
