<?php

namespace Wallo\Transmatic\Commands;

use Illuminate\Console\Command;

class TransmaticCommand extends Command
{
    public $signature = 'transmatic';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
