<?php

namespace JeroenG\Packager;

use Illuminate\Console\Command;
use JeroenG\Packager\PackagerHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Get an existing package from a remote Github repository.
 *
 * @package Packager
 * @author JeroenG
 *
 **/
class PackagerGetCommand extends Command
{

    /**
     * Then name of the console command
     *
     * @var string
     */
    protected $name = 'packager:get';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packager:get
                            {url : The url of the Github repository}
                            {--branch=master : The branch to download}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve an existing package from Github.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PackagerHelper $helper)
    {
        parent::__construct();
        $this->helper = $helper;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Start the progress bar
        $bar = $this->helper->barSetup($this->output->createProgressBar(5));
        $bar->start();

        // Common variables
        $origin = rtrim($this->argument('url'), '/').'/archive/'.$this->option('branch').'.zip';
        $pieces = explode('/', $origin);
        $vendor = $pieces[3];
        $name = $pieces[4];
        $cVendor = ucfirst($vendor);
        $cName = ucfirst($name);
        $path = getcwd().'/packages/';
        $fullPath = $path.$vendor.'/'.$name;
        $requirement = '"psr-4": {
            "'.$cVendor.'\\\\'.$cName.'\\\\": "packages/'.$vendor.'/'.$name.'/src",';
        $appConfigLine = 'App\Providers\RouteServiceProvider::class,

        '.ucfirst($vendor).'\\'.ucfirst($name).'\\'.ucfirst($name).'ServiceProvider::class,';

        // Start creating the package
        $this->info('Creating package '.$vendor.'\\'.$name.'...');
            $this->helper->checkExistingPackage($path, $vendor, $name);
        $bar->advance();

        // Create the package directory
        $this->info('Creating packages directory...');
            $this->helper->makeDir($path);
        $bar->advance();

        // Create the vendor directory
        $this->info('Creating vendor...');
         $this->helper->makeDir($path.$vendor);
        $bar->advance();

        // Get the skeleton repo from the PHP League
        $this->info('Downloading from Github...');
            $this->helper->download($zipFile = $this->helper->makeFilename(), $origin)
                 ->extract($zipFile, $path.$vendor)
                 ->cleanUp($zipFile);
            rename($path.$vendor.'/'.$name. '-'.$this->option('branch'), $fullPath);
        $bar->advance();

        // Add it to composer.json
        $this->info('Adding package to composer and app...');
            $this->helper->replaceAndSave(getcwd().'/composer.json', '"psr-4": {', $requirement);
            // And add it to the providers array in config/app.php
            $this->helper->replaceAndSave(getcwd().'/config/app.php', 'App\Providers\RouteServiceProvider::class,', $appConfigLine);
        $bar->advance();

        // Finished creating the package, end of the progress bar
        $bar->finish();
        $this->info('Package created successfully!');
        $this->output->newLine(2);
        $bar = null;
    }

    /**
     * Command's arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['url', InputArgument::REQUIRED, 'The url of the Github repository']
        ];
    }

    /**
     * Command's options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['branch', null, InputOption::VALUE_OPTIONAL, 'The branch to download', 'master']
        ];
    }
}
