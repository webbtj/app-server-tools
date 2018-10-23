<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CreateSite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm:site {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets up a new site';

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
        $wd = base_path();
        $primary_domain = $this->argument('domain');
        $file_name = str_replace('.', '-', $primary_domain);
        $user =  env('SYS_USER');
        $group = env('SYS_GROUP');
        $dir = env('SYS_SITES_ROOT');
        $conf_dir = env('SYS_SITES_CONF');

        if(!$primary_domain)
            $this->error('Domain not provided!');
        if(!$user)
            $this->error('Env User "SYS_USER" not configured! See .env file.');
        if(!$group)
            $this->error('Env Group "SYS_GROUP" not configured! See .env file.');
        if(!$dir)
            $this->error('Env Site Root "SYS_SITES_ROOT" not configured! See .env file.');
        if(!$conf_dir)
            $this->error('Env Site Root "SYS_SITES_CONF" not configured! See .env file.');

        if(file_exists(sprintf('%s/%s', env('SYS_SITES_ROOT'), $primary_domain))){
            $this->error(sprintf('%s/%s already exists!', env('SYS_SITES_ROOT'), $primary_domain));
        }

        $this->command(sprintf('sudo mkdir %s/%s', env('SYS_SITES_ROOT'), $primary_domain));
        $this->command(sprintf('sudo chown %s:%s %s/%s', env('SYS_USER'), env('SYS_GROUP'), env('SYS_SITES_ROOT'), $primary_domain));

        $template = file_get_contents($wd . '/templates/nginx-site.conf');
        $template = str_replace('[[domain]]', $primary_domain, $template);
        $this->command(sprintf('echo "%s" | sudo tee %s/%s > /dev/null', $template, $conf_dir, $file_name));

        $template = file_get_contents($wd . '/templates/index.html');
        $template = str_replace('[[name]]', $primary_domain, $template);
        $this->command(sprintf('echo "%s" | sudo tee %s/%s/index.html > /dev/null', $template, $primary_domain, $file_name));

        $this->command('sudo systemctl reload nginx');

        $response = $this->command('sudo nginx -t', true);
        if(!strpos($response, 'test is success')){
            $this->error(sprintf('nginx config test failed! Check %s/%s.', $conf_dir, $file_name));
        }
    }

    public function command($command, $return=false){
        $verbose = false;
        $process = new Process($command);
        $process->run();
        if($process->isSuccessful()){
            if($verbose){
                echo sprintf("%s\n", $process->getOutput());
            }
            if($return){
                return $process->getOutput();
            }
        }else{
            $this->error(sprintf("Command `%s` Failed!\033[0m\n", $command));
        }
    }

    public function error($message, $verbosity = NULL){
        echo sprintf("\033[1;30m\033[41mError! %s Aborting!\033[0m\n", $message);
        exit;
    }
}
