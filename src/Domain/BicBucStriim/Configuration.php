<?php
declare(strict_types=1);


namespace App\Domain\BicBucStriim;

use App\Infrastructure\Mail\Mailer;
use ArrayAccess;
use Psr\Log\LoggerInterface;

class Configuration implements ArrayAccess
{
    /**
     * @var BicBucStriimRepository
     */
    protected BicBucStriimRepository $bbs;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    protected array $config = array(
        AppConstants::CALIBRE_DIR => '',
        AppConstants::DB_VERSION => AppConstants::DB_SCHEMA_VERSION,
        AppConstants::KINDLE => 0,
        AppConstants::KINDLE_FROM_EMAIL => '',
        AppConstants::THUMB_GEN_CLIPPED => 1,
        AppConstants::PAGE_SIZE => 30,
        AppConstants::DISPLAY_APP_NAME => 'BicBucStriim',
        AppConstants::MAILER => Mailer::MAIL,
        AppConstants::SMTP_USER => '',
        AppConstants::SMTP_PASSWORD => '',
        AppConstants::SMTP_SERVER => '',
        AppConstants::SMTP_PORT => 25,
        AppConstants::SMTP_ENCRYPTION => 0,
        AppConstants::METADATA_UPDATE => 0,
        AppConstants::LOGIN_REQUIRED => 1,
        AppConstants::TITLE_TIME_SORT => AppConstants::TITLE_TIME_SORT_TIMESTAMP,
        AppConstants::RELATIVE_URLS => 1,
    );

    /**
     * Configuration constructor.
     * @param LoggerInterface $logger
     * @param BicBucStriimRepository $bbs
     */
    public function __construct(LoggerInterface $logger, BicBucStriimRepository $bbs)
    {
        $this->logger = $logger;
        $this->bbs = $bbs;
        $this->load();
    }

    /**
     * Load existing configuration data
     */
    public function load() {
        if (!is_null($this->bbs) && $this->bbs->dbOk()) {
            $this->logger->debug("loading configuration");
            $css = $this->bbs->configs();
            foreach ($css as $cs) {
                if (array_key_exists($cs->name, $this->config)) {
                    $this->logger->debug("configuring value {$cs->val} for {$cs->name}");
                    $this->config[$cs->name] = $cs->val;
                } else {
                    $this->logger->warn("ignoring unknown configuration, name: {$cs->name}, value: {$cs->val}");
                }
            }
        } else {
            $this->logger->debug("no configuration loaded");
        }
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset): bool
    {
        return isset($this->config[$offset]);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return isset($this->config[$offset]) ? $this->config[$offset] : null;
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->config[] = $value;
        } else {
            $this->config[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->config[$offset]);
    }
}