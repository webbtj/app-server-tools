<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DomainAlias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm:alias {alias} {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup a new domain that points to an existing app.';

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
        $domain = $this->argument('domain');
        $alias = $this->argument('alias');
        $user =  env('SYS_USER');
        $group = env('SYS_GROUP');
        $sites_dir = env('SYS_SITES_ROOT');
        $conf_dir = env('SYS_SITES_CONF');

        if(!$domain)
            $this->error('Domain not provided!');
        if(!$user)
            $this->error('Env User "SYS_USER" not configured! See .env file.');
        if(!$group)
            $this->error('Env Group "SYS_GROUP" not configured! See .env file.');
        if(!$sites_dir)
            $this->error('Env Site Root "SYS_SITES_ROOT" not configured! See .env file.');
        if(!$conf_dir)
            $this->error('Env Site Root "SYS_SITES_CONF" not configured! See .env file.');

        if(!file_exists(sprintf('%s/%s', $sites_dir, $domain))){
            $this->error('Existing app not found!');
        }

        if(file_exists(sprintf('%s/%s', $sites_dir, $alias))){
            $this->error('Alias domain found as its own app!');
        }

        if(!file_exists(sprintf('%s/sites-available/%s', $conf_dir, $domain))){
            $this->error('Existing app nginx config already not found!');
        }

        if(file_exists(sprintf('%s/sites-available/%s', $conf_dir, $alias))){
            $this->error('Alias domain nginx config already found!');
        }

        //
        $template = file_get_contents($wd . '/templates/nginx-site.conf');
        $template = str_replace('[[domain]]', $alias, $template);

        $site_root = '/current/public';
        if($this->option('bare')){
            $site_root = '';
        }
        $template = str_replace('[[site_root]]', $site_root, $template);

        $root_dir = sprintf('%s/%s%s', $sites_dir, $domain, $site_root);
        $template = str_replace('[[root_dir]]', $root_dir, $template);

        $this->command(sprintf('echo "%s" | sudo tee %s/sites-available/%s > /dev/null', $template, $conf_dir, $alias));
        $this->command(sprintf('sudo ln -s %s/sites-available/%s %s/sites-enabled/%s', $conf_dir, $alias, $conf_dir, $alias));
        //

        $response = $this->command('sudo nginx -t', true);
        if(strpos($response, 'test is success')){
            $this->error(sprintf('nginx config test failed! Check %s/sites-available/%s.', $conf_dir, $alias));
        }

        $this->command('sudo systemctl reload nginx');
        echo "Done.\n";
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
            $this->error(sprintf("\033[1;30m\033[41mCommand `%s` Failed!\033[0m\n", $command));
        }
    }

    public function error($message, $verbosity = NULL){
        echo sprintf("\033[1;30m\033[41mError! %s Aborting!\033[0m\n", $message);
        exit;
    }
}
