<?php

namespace App\Console\Commands;

use App\Console\Commands\CmBase;

class GetHelp extends CmBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm:help';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays help about the toolset';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "php artisan cm:site {domain}\n";
        echo "\tThis will setup a new site, configure nginx, and restart.\n";
    }
}
