<?php

namespace App\Console\Commands;

use App\Console\Commands\CmBase;
use Symfony\Component\Process\Process;

class CreateSite extends CmBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm:site {domain} {--bare}';

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
        $domain = $this->argument('domain');

        if(!$domain)
            $this->error(static::$MSG_NO_DOMAIN);

        extract($this->get_env());

        if(file_exists($domain_dir)){
            $this->error(sprintf(static::$MSG_ALREADY_EXISTS, $domain_dir));
        }

        $this->command(sprintf('sudo mkdir %s', $domain_dir));
        $this->command(sprintf('sudo chown %s:%s %s', $user, $group, $domain_dir));

        $template = file_get_contents($this->wd('templates/nginx-site.conf'));
        $template = str_replace('[[domain]]', $domain, $template);

        $site_root = '/current/public';
        if($this->option('bare')){
            $site_root = '';
        }
        $template = str_replace('[[site_root]]', $site_root, $template);

        $this->command(sprintf('echo "%s" | sudo tee %s/sites-available/%s > /dev/null', $template, $conf_dir, $domain));
        $this->command(sprintf('sudo ln -s %s/sites-available/%s %s/sites-enabled/%s', $conf_dir, $domain, $conf_dir, $domain));

        if($this->option('bare')){
            $template = file_get_contents($this->wd('templates/index.html'));
            $template = str_replace('[[name]]', $domain, $template);
            $this->command(sprintf('echo "%s" | sudo tee %s/%s/index.html > /dev/null', $template, $sites_dir, $domain));
            $this->command(sprintf('sudo chown %s:%s %s/%s/index.html', $user, $group, $sites_dir, $domain));
        }

        $this->command('sudo systemctl reload nginx');

        $response = $this->command('sudo nginx -t', true);
        if(strpos($response, 'test is success')){
            $this->error(sprintf(static::$MSG_NGINX_FAILED, sprintf('%s/sites-available/%s', $conf_dir, $domain)));
        }
        $this->nginx_test();

        $this->create_cron();
    }

    public function nginx_test(){
        $response = $this->command('sudo nginx -t', true);
        if(strpos($response, 'test is success')){
            $this->error(sprintf(static::$MSG_NGINX_FAILED, sprintf('%s/sites-available/%s', $conf_dir, $domain)));
        }
    }

    public function create_cron(){
        if(!$this->option('bare')){
            $a = $this->command('crontab -l > ~/.tmp.cron');
            $b = $this->command(sprintf('echo "* * * * * cd %s/%s/current && php artisan schedule:run >> /dev/null 2>&1" >> ~/.tmp.cron', $sites_dir, $domain));
            $c = $this->command('crontab ~/.tmp.cron');
            $d = $this->command('rm ~/.tmp.cron');
            if($a && $b && $c && $d){
                $this->good(static::$MSG_CRON_SUCCESS);
            }
        }
    }
}
