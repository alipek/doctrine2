## Doctrine 2 fork with optimization

This fork contains some performance optimizations.
Currently implements:
- Batch inserts for processing big amount of entities
- Disabling internal events when you need

The main object you need is `PerformanceConfiguration`.
Here is an example for demonstration:

```
//Getting performance configuration
$performanceConfiguration = $em->getPerformanceConfiguration();

//Disable internal events
$performanceConfiguration->setIsEventsSystemEnabled(false);

//Enable batch inserts
$performanceConfiguration->enableBatchInsert();

//Set how many entities will be inserted per one insert
$performanceConfiguration->setMaxPerInsert(500);
```

## More resources:

* [Website](http://www.doctrine-project.org)
* [Documentation](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/index.html)
