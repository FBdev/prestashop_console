<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Hhennes\PrestashopConsole\Command\Dev;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of UpgradePsVersionSqlCommand
 *
 */
class UpgradePsVersionSqlCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('dev:upgradesql')
            ->setDescription('Upgrade Prestashop database sql')
            ->addArgument(
                        'version', InputArgument::REQUIRED, 'version to upgrade)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument('version');
        $fileName = _PS_ROOT_DIR_.'_install/upgrade/sql/'.$version.'.sql';
        if ( is_file($fileName)) {
            $content = file_get_contents($fileName);
            if ( Db::getInstance()->execute($content)) {
                $output->writeln('<info>Version '.$version.' upgraded with success</info>');
            } else {
                $output->writeln('<error>Sql error for version '.$version.'</error>');
            }
        } else {
            $output->writeln("<error>No files for update</error>");
        }
    }
}
