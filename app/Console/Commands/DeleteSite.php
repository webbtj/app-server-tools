<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DeleteSite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm:delete {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a site';

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

        $conf_dir = env('SYS_SITES_CONF');
        if(!$conf_dir){
            echo "Env Conf Dir 'SYS_SITES_CONF' not configured! See .env file.\n";
            exit;
        }

        if(!file_exists(sprintf('%s/%s', $sites_dir, $domain))){
            echo sprintf("Domain not yet installed.\n Run `appserv site %s` first.\n", $domain);
            exit;
        }

        if($this->confirm(sprintf('Are you super sure you want to delete %s? All files and configurations will be deleted and this cannot be undone.', $domain))){
            $errors = [];
            $successes = [];
            $paths = [
                sprintf('%s/%s', $sites_dir, $domain),
                sprintf('%s/sites-available/%s', $conf_dir, $domain),
                sprintf('%s/sites-enabled/%s', $conf_dir, $domain),
                sprintf('/etc/letsencrypt/archive/%s', $domain),
                sprintf('/etc/letsencrypt/live/%s', $domain),
                sprintf('/etc/letsencrypt/renewal/%s', $domain),
            ];

            foreach($paths as $path){
                $process = new Process(sprintf('sudo rm -rf %s', $path));
                $process->run();
                if(!$process->isSuccessful()){
                    $error[] = $path;
                }else{
                    $successes[] = $path;
                }
            }

            foreach($successes as $path){
                echo sprintf("`%s` successfully removed.\n", $path);
            }
            foreach($errors as $path){
                echo sprintf("\033[1;30m\033[41m`%s` could not be removed.\033[0m\n", $path);
            }

        }
    }
}
