<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class UtilSsl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm:ssl {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Makes  the site secure.';

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
            if($this->choice(sprintf("I couldn't find %s. Is this an alias of another domain?", $domain))){
                $original_domain = $this->ask("What domain is this an alias of?");
                !file_exists(sprintf('%s/%s', $sites_dir, $original_domain)){
                    echo sprintf("I couldn't find %s either. Aborting.\n", $original_domain);
                }
            }else{
                echo sprintf("Domain not yet installed.\n Run `appserv site %s` first.\n", $domain);
            }
            //exit;
        }

        echo "Starting, please wait. This may take a few moments...\n";

        $process = new Process(sprintf('yes 2 | sudo certbot --nginx -d %s -d www.%s', $domain, $domain));
        $process->run();
        if(!$process->isSuccessful()){
            echo "\033[1;30m\033[41mCould not automatically secure site.\033[0m\n";
        }else{
            echo "Site is now secure!\n";
        }
    }
}
