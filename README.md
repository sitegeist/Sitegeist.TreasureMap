# Sitegeist.TreasureMap

Switch between multiple cache `backendConfigurations` based on a `backendDiscriminator` configuration. 

This allows switching between cache configurations based on environment variables which enables things 
like green/blue caches to control cache invalidation during rollouts of larger applications.

### Authors & Sponsors

* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored by our employer http://www.sitegeist.de.*

## Configuration

The 'Sitegeist\TreasureMap\Backend\SwitchableBackend' cache backend is configured by the `backendDiscriminator` and
the `backendConfigurations` the discriminator value will define which of the backend `backendConfigurations` will be
used to instantiate the actually used backend.

Caches.yaml:
```yaml
Neos_Fusion_Content: 
  backend: 'Sitegeist\TreasureMap\Backend\SwitchableBackend'
  backendOptions:
    backendDiscriminator: '%env:GREEN_OR_BLUE%'
    backendConfigurations:
      green:
        backend: 'Neos\Cache\Backend\RedisBackend'
        backendOptions:
          hostname: '%env:REDIS_HOST%'
          port: '%env:REDIS_PORT%'
          database: 11
      blue:
        backend: 'Neos\Cache\Backend\RedisBackend'
        backendOptions:
          hostname: '%env:REDIS_HOST%'
          port: '%env:REDIS_PORT%'
          database: 21 
```

## Contribution

We will gladly accept contributions. Please send us pull requests.

## License

See [LICENSE](LICENSE)
