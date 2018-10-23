<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class EchoEnvoyer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm:envoyer {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays info for setting up Envoyer';

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

        $process = new Process('dig +short myip.opendns.com @resolver1.opendns.com');
        $process->run();
        if(!$process->isSuccessful()){
            echo "Could not get IP.\n";
            exit;
        }
        $ip = trim($process->getOutput());

        $process = new Process('whoami');
        $process->run();
        if(!$process->isSuccessful()){
            echo "Could not get User.\n";
            exit;
        }
        $user = trim($process->getOutput());

        $process = new Process('which php');
        $process->run();
        if(!$process->isSuccessful()){
            echo "Could not get PHP.\n";
            exit;
        }
        $php_path = trim($process->getOutput());

        $process = new Process('which composer');
        $process->run();
        if(!$process->isSuccessful()){
            echo "Could not get Composer.\n";
            exit;
        }
        $composer_path = trim($process->getOutput());

        $process = new Process('php -v');
        $process->run();
        if(!$process->isSuccessful()){
            echo "Could not get Composer.\n";
            exit;
        }
        $php_ver_output = trim($process->getOutput());
        $php_version = preg_replace('/^PHP\\s([0-9\\.]+)(.*[\\n\\r].*)*/', '$1', $php_ver_output);

        $path = sprintf("%s/%s", $sites_dir, $domain);
        $port = '22';
        $code_deployments = true;
        $restart_fpm = true;
        $free_bsd = false;

        $headers = ['Label', 'Value'];
        $data = [
            ['IP Address', $ip],
            ['Port', $port],
            ['Connect As', $user],
            ['Receives Code Deployments', $code_deployments],
            ['Project Path', $path],
            ['Restart FPM After Deployments', $restart_fpm],
            ['FreeBSD', $free_bsd],
            ['PHP Version', $php_version],
            ['PHP Path', $php_path],
            ['Composer Path', $composer_path]
        ];
        $this->table($headers, $data);
    }
}
