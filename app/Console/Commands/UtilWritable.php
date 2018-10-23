<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class UtilWritable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm:writable {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make the storage directory writable.';

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
        $domain = $this->argument('domain');
        if(!$domain){
            echo "Could not get Domain.\n";
            exit;
        }

        $sites_dir = env('SYS_SITES_ROOT');
        if(!$sites_dir){
            echo "Env Site Root 'SYS_SITES_ROOT' not configured! See .env file.\n";
            exit;
        }

        if(!file_exists(sprintf('%s/%s', $sites_dir, $domain))){
            echo sprintf("Domain not yet installed.\n Run `appserv site %s` first.\n", $domain);
            exit;
        }

        $command = sprintf("sudo chgrp -R www-data %s/%s/storage", $sites_dir, $domain);
        $process = new Process($command);
        $process->run();
        if(!$process->isSuccessful()){
            echo sprintf("\033[1;30m\033[41mCommand `%s` Failed.\033[0m\n", $command);
        }

        $command = sprintf("sudo chmod -R ug+rwx %s/%s/storage", $sites_dir, $domain);
        $process = new Process($command);
        $process->run();
        if(!$process->isSuccessful()){
            echo sprintf("\033[1;30m\033[41mCommand `%s` Failed.\033[0m\n", $command);
        }
    }
}
