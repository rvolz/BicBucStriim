<?php


namespace App\Application\Actions\Admin;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Calibre\Calibre;
use Psr\Http\Message\ResponseInterface as Response;

class UpdateConfigurationAction extends AdminAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $this->logger->debug('admin_change: started');
        # Check access permission
        if (!$this->is_admin()) {
            $this->logger->warning('admin_change: no admin permission');
            return $this->respondWithPage('admin_configuration.twig', array(
                'page' => $this->mkPage($this->getMessageString('admin')),
                'messages' => array($this->getMessageString('invalid_password')),
                'isadmin' => false));
        }
        $nconfigs = array();
        $req_configs = $this->request->getParsedBody();
        $errors = array();
        $messages = array();
        // $this->logger->debug('admin_change: ' . var_export($req_configs, true));

        ## Check for consistency - calibre directory
        # Calibre dir is still empty and no change in sight --> error
        if (!$this->has_valid_calibre_dir() && empty($req_configs[AppConstants::CALIBRE_DIR]))
            array_push($errors, 1);
        # Calibre dir changed, check it for existence, delete thumbnails of old calibre library
        elseif (array_key_exists(AppConstants::CALIBRE_DIR, $req_configs)) {
            $req_calibre_dir = $req_configs[AppConstants::CALIBRE_DIR];
            if ($req_calibre_dir != $this->config[AppConstants::CALIBRE_DIR]) {
                if (!Calibre::checkForCalibre($req_calibre_dir)) {
                    array_push($errors, 1);
                } elseif ($this->bbs->clearThumbnails())
                    $this->logger->info('admin_change: Lib changed, deleted existing thumbnails.');
                else {
                    $this->logger->info('admin_change: Lib changed, deletion of existing thumbnails failed.');
                }
            }
        }
        ## More consistency checks - kindle feature
        # Switch off Kindle feature, if no valid email address supplied
        if ($req_configs[AppConstants::KINDLE] == "1") {
            if (empty($req_configs[AppConstants::KINDLE_FROM_EMAIL])) {
                array_push($errors, 5);
            } elseif (!$this->isEMailValid($req_configs[AppConstants::KINDLE_FROM_EMAIL])) {
                array_push($errors, 5);
            }
        }

        ## Check for a change in the thumbnail generation method
        if ($req_configs[AppConstants::THUMB_GEN_CLIPPED] != $this->config[AppConstants::THUMB_GEN_CLIPPED]) {
            $this->logger->info('admin_change: Thumbnail generation method changed. Existing Thumbnails will be deleted.');
            # Delete old thumbnails if necessary
            if ($this->bbs->clearThumbnails())
                $this->logger->info('admin_change: Deleted exisiting thumbnails.');
            else {
                $this->logger->info('admin_change: Deletion of exisiting thumbnails failed.');
            }
        }

        ## Check for a change in page size, min 1, max 100
        if ($req_configs[AppConstants::PAGE_SIZE] != $this->config[AppConstants::PAGE_SIZE]) {
            if ($req_configs[AppConstants::PAGE_SIZE] < 1 || $req_configs[AppConstants::PAGE_SIZE] > 100) {
                $this->logger->warning('admin_change: Invalid page size requested: ' . $req_configs[AppConstants::PAGE_SIZE]);
                array_push($errors, 4);
            }
        }

        ## Check for a change in the "remember me" cookie feature and generate a new key if enabled
        if ($req_configs[AppConstants::REMEMBER_COOKIE_ENABLED] != $this->config[AppConstants::REMEMBER_COOKIE_ENABLED]) {
            if ($req_configs[AppConstants::REMEMBER_COOKIE_ENABLED] == 1) {
                $req_configs[AppConstants::REMEMBER_COOKIE_KEY] =  random_bytes(20);
            }
        }

        # Don't save just return the error status
        if (count($errors) > 0) {
            $this->logger->error('admin_change: ended with error ' . var_export($errors, true));
            return $this->respondWithPage('admin_configuration.twig', array(
                'page' => $this->mkPage($this->getMessageString('admin')),
                'config' => array_merge($this->config->getConfig(), $nconfigs),
                'mailers' => $this->mkMailers(),
                'ttss' => $this->mkTitleTimeSortOptions(),
                'isadmin' => $this->is_admin(),
                'errors' => $errors));
        } else {
            ## Apply changes
            foreach ($req_configs as $key => $value) {
                if (!isset($globalSettings[$key]) || $value != $globalSettings[$key]) {
                    $nconfigs[$key] = $value == 'on' ? '1' : $value;
                    $globalSettings[$key] = $value;
                    $this->logger->debug('admin_change: ' . $key . ' changed: ' . $value);
                }
            }
            # Save changes
            if (count($nconfigs) > 0) {
                $this->bbs->saveConfigs($nconfigs);
                $this->logger->debug('admin_change: changes saved');
            }
            $this->logger->debug('admin_change: ended');
            return $this->respondWithPage('admin_configuration.twig', array(
                'page' => $this->mkPage($this->getMessageString('admin'), 0, 2),
                'messages' => array($this->getMessageString('changes_saved')),
                'config' => array_merge($this->config->getConfig(), $nconfigs),
                'mailers' => $this->mkMailers(),
                'ttss' => $this->mkTitleTimeSortOptions(),
                'isadmin' => $this->is_admin(),
            ));
        }
    }

    /**
     * Is there a valid - existing - Calibre directory?
     * @return boolean    true if available
     */
    protected function has_valid_calibre_dir(): bool
    {
        return ($this->has_global_setting(AppConstants::CALIBRE_DIR) &&
            Calibre::checkForCalibre($this->config[AppConstants::CALIBRE_DIR]));
    }


    /**
     * Is the key in globalSettings?
     * @param string $key key for config value
     * @return boolean        true = key available
     */
    protected function has_global_setting(string $key): bool
    {
        return (isset($this->config[$key]) && !empty($this->config[$key]));
    }


    /**
     * Check for valid email address format
     * @param string $mail
     * @return bool
     */
    protected function isEMailValid(string $mail): bool
    {
        return (filter_var($mail, FILTER_VALIDATE_EMAIL) !== false);
    }

}