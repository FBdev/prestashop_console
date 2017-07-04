<?php

/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Hennes Hervé <contact@h-hennes.fr>
 *  @copyright 2013-2016 Hennes Hervé
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  http://www.h-hennes.fr/blog/
 */

namespace Hhennes\PrestashopConsole\Command\Dev;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of UpgradePsVersionCommand
 *
 *
 */
class UpgradePsVersionCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('dev:upgrade')
            ->setDescription('Upgrade Prestashop to last version');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ( !function_exists('exec') &&  !strpos(ini_get("disable_functions"), "exec") ){
            $output->writeln("<error>Php exec function must be available to use this commande</error>");
            return;
        }

        //Pour fonctionner il faut que le module autoupgrade soit configuré
        if (!\Module::isInstalled('autoupgrade') || !\Module::isEnabled('autoupgrade')) {
            $output->writeln("<error>Module autoupgrade should be installed and enabled");
            return;
        }

        // Verification de la version avec la classe du module OneClickUpgrade
        $upgrade = new \Upgrader();
        $upgrade->checkPSVersion(true);

        $output->writeln("<info> Version actuelle de PS " . _PS_VERSION_ . "</info>");

        if (version_compare($upgrade->version_num, _PS_VERSION_) > 0) {
            $output->writeln("<info> Mise à jour disponible vers la version  " . $upgrade->version_num . "</info>");

            //Création d'un dossier temporaire et téléchargement du fichier de la dernière version
            $downloadDir = _PS_ROOT_DIR_ . '/download/upgrade';
            $downloadFile = 'prestashop' . $upgrade->version_num . '.zip';

            if (!is_dir($downloadDir)) {
                $output->writeln("<info> Create tempory directory for download</info>");
                mkdir($downloadDir, 777);
            }

            $output->writeln("<info>Start downloading archive</info>");

            if ( !is_file( $downloadDir.'/'.$downloadFile) ) {
                $ch = curl_init();
                $source = "https://www.prestashop.com/download/old/prestashop_" . $upgrade->version_num . ".zip";
                echo $source;
                curl_setopt($ch, CURLOPT_URL, $source);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $data = curl_exec($ch);
                $destination = _PS_ROOT_DIR_ . '/download/upgrade/prestashop' . $upgrade->version_num . '.zip';
                $file = fopen($destination, "w+");
                fputs($file, $data);
                fclose($file);
                $output->writeln("<info>File downloaded</info>");
            } else {
                $output->writeln("<info>Archive already downloaded</info>");
            }

            //On dézippe l'archive dans le dossier parent pour tout écraser
            $output->writeln("<info>Unziping file</info>");
            $zip = new \ZipArchive();
            if ($zip->open($downloadDir.'/'.$downloadFile)) {
                if ($zip->extractTo(_PS_ROOT_DIR_.'/') ) {
                    $output->writeln("<info>Archive extracted to "._PS_ROOT_DIR_."</info>");

                    //@Todo : génériser ça mais pour l'instant spécifique à mes installs
                    exec ('cp -R '._PS_ROOT_DIR_.'/prestashop/* '._PS_ROOT_DIR_.'/');
                    exec('rm -rf '._PS_ROOT_DIR_.'/prestashop/');
                    exec('rm -rf '._PS_ROOT_DIR_.'/admin-dev');
                    exec('mv '._PS_ROOT_DIR_.'/admin/ '._PS_ROOT_DIR_.'/admin-dev/');
                    exec('rm -rf '._PS_ROOT_DIR_.'/_install');
                    exec('mv '._PS_ROOT_DIR_.'/install/ '._PS_ROOT_DIR_.'/_install/');
                } else {
                    $output->writeln("<error>Unable to unzip downloaded archive</error>");
                }
                $zip->close();

                //Mise à jour de la version dans le fichier de settings
                $settingsFile = _PS_CONFIG_DIR_.'settings.inc.php';
                copy($settingsFile,_PS_CONFIG_DIR_.'settings.inc.php.old');
                $settingContents = file_get_contents($settingsFile);
                $settingContents = preg_replace("#define\('_PS_VERSION_', '1\.6\.1\.14'\);#","define('_PS_VERSION_', '".$upgrade->version_num."');",$settingContents);
                file_put_contents($settingsFile, $settingContents);

            } else {
                $output->writeln("<error>Unable to unzip downloaded archive</error>");
            }
        }
    }
}
