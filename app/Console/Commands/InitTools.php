<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class InitTools extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initializes the toolset (creates default .env file)';

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
        if(file_exists(".env")){
            echo ".env file already exists. Skipping...\n";
            exit;
        }

        $template = file_get_contents('templates/.env');
        $process = new Process(sprintf('echo "%s" > .env', addslashes($template)));
        $process->run();
        if($process->isSuccessful()){
            echo ".env file created successfully\n";
        }else{
            echo "\033[1;30m\033[41m.env could NOT be created!\033[0m\n";
        }
    }
}
