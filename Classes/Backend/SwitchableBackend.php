<?php
declare(strict_types=1);

namespace Sitegeist\TreasureMap\Backend;

use Neos\Cache\Backend\AbstractBackend;
use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Backend\IterableBackendInterface;
use Neos\Cache\Backend\PhpCapableBackendInterface;
use Neos\Cache\Backend\TaggableBackendInterface;
use Neos\Cache\Backend\WithSetupInterface;
use Neos\Cache\Backend\WithStatusInterface;
use Neos\Cache\BackendInstantiationTrait;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception\InvalidBackendException;
use Neos\Error\Messages\Result;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\ObjectManagement\Exception\UnknownObjectException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * A backend that switches between subBackends based on the given discriminator.
 */
class SwitchableBackend extends AbstractBackend implements TaggableBackendInterface, WithSetupInterface, WithStatusInterface
{
    use BackendInstantiationTrait;

    protected string $backendDiscriminator = '';
    protected array $backendConfigurations = [];
    protected ?BackendInterface $selectedBackend = null;

    protected function getSelectedBackend(): BackendInterface
    {
        if ($this->selectedBackend instanceof BackendInterface) {
            return $this->selectedBackend;
        }

        if (empty($this->backendDiscriminator)) {
            throw new MissingDiscrimintorException('No "backendDiscriminator" was set');
        }

        if (!array_key_exists($this->backendDiscriminator, $this->backendConfigurations)) {
            throw new MissingBackendConfigurationException(sprintf('No "backendConfiguration" for "backendDiscriminator" "%s" was found', $this->backendDiscriminator));
        }

        $backendClassName = $this->backendConfigurations[$this->backendDiscriminator]['backend'] ?? '';
        $backendOptions = $this->backendConfigurations[$this->backendDiscriminator]['backendOptions'] ?? [];

        $this->selectedBackend = $this->instantiateBackend($backendClassName, $backendOptions, $this->environmentConfiguration);
        $this->selectedBackend->setCache($this->cache);

        return $this->selectedBackend;
    }

    public function set(string $entryIdentifier, string $data, array $tags = [], int $lifetime = null): void
    {
        $backend = $this->getSelectedBackend();
        $backend->set($entryIdentifier, $data, $tags, $lifetime);
    }

    public function get(string $entryIdentifier)
    {
        $backend = $this->getSelectedBackend();
        return $backend->get($entryIdentifier);
    }

    public function has(string $entryIdentifier): bool
    {
        $backend = $this->getSelectedBackend();
        return $backend->has($entryIdentifier);
    }

    public function remove(string $entryIdentifier): bool
    {
        $backend = $this->getSelectedBackend();
        return $backend->remove($entryIdentifier);
    }

    public function flush(): void
    {
        $backend = $this->getSelectedBackend();
        $backend->flush();
    }

    public function collectGarbage(): void
    {
        $backend = $this->getSelectedBackend();
        $backend->collectGarbage();
    }

    /**
     * @see TaggableBackendInterface
     * @inheritDoc
     */
    public function flushByTag(string $tag): int
    {
        $backend = $this->getSelectedBackend();
        if ($backend instanceof TaggableBackendInterface) {
            return $backend->flushByTag($tag);
        }
        return 0;
    }

    /**
     * @see TaggableBackendInterface
     * @inheritDoc
     */
    public function flushByTags(array $tags): int
    {
        $backend = $this->getSelectedBackend();
        if ($backend instanceof TaggableBackendInterface) {
            return $backend->flushByTags($tags);
        }
        return 0;
    }

    /**
     * @see TaggableBackendInterface
     * @inheritDoc
     */
    public function findIdentifiersByTag(string $tag): array
    {
        $backend = $this->getSelectedBackend();
        if ($backend instanceof TaggableBackendInterface) {
            return $backend->findIdentifiersByTag($tag);
        }
        return [];
    }

    /**
     * @see WithSetupInterface
     * @inheritDoc
     */
    public function setup(): Result
    {
        $backend = $this->getSelectedBackend();
        if ($backend instanceof WithSetupInterface) {
            return $backend->setup();
        }
        return new Result();
    }

    /**
     * @see WithStatusInterface
     * @inheritDoc
     */
    public function getStatus (): Result
    {
        $backend = $this->getSelectedBackend();
        if ($backend instanceof WithStatusInterface) {
            return $backend->getStatus();
        }
        return new Result();
    }

    /**
     * This setter is used by AbstractBackend::setProperties()
     */
    protected function setBackendDiscriminator(string $discriminator): void
    {
        $this->backendDiscriminator = $discriminator;
    }

    /**
     * This setter is used by AbstractBackend::setProperties()
     */
    protected function setBackendConfigurations(array $backendConfigurations): void
    {
        $this->backendConfigurations = $backendConfigurations;
    }
}
