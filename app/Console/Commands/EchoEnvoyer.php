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
    protected $signature = 'cm:envoyer {domain} {--key} {--hooks}';

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

        if($this->option('key')){
            $key = $this->ask("Please paste your envoyer key.");
            if($key){
                $process = new Process(sprintf('echo "%s" >> ~/.ssh/authorized_keys', $key . "\n"));
                $process->run();
                if(!$process->isSuccessful()){
                    echo "Could not get store key.\n";
                    exit;
                }
                echo "Key added successfully.\n";
            }
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

        if(strpos($php_version, '7.2') !== false){
            $php_version .= " (you may want to select 7.0)";
        }

        $path = sprintf("%s/%s", $sites_dir, $domain);
        $port = '22';
        $code_deployments = 'Yes';
        $restart_fpm = 'Yes';
        $free_bsd = 'No';

        $headers = ['Config', 'Value'];
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

        if($this->option('hooks')){
            $this->hooks($user, $path);
        }
    }

    public function hooks($user, $path){
        $hooks = [
            [
                'name' => 'Run Migrations',
                'user' => $user,
                'order' => 'After Activate New Release',
                'script' => "cd $path/current\nphp artisan migrate"
            ],
            [
                'name' => 'Link Storage',
                'user' => $user,
                'order' => 'After Activate New Release',
                'script' => "cd $path/current\nphp artisan storage:link"
            ]
        ];
        echo "\nSetup the following Envoyer hooks.\n";
        echo "==================================\n\n";
        foreach($hooks as $hook){
            echo sprintf("Name: %s\n", $hook['name']);
            echo sprintf("Run as user: %s\n", $hook['user']);
            echo sprintf("Run %s\n", $hook['order']);
            echo sprintf("SCRIPT:\n%s\n\n\n", $hook['script']);
            echo "==================================\n\n";
        }

    }
}
