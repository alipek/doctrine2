## Doctrine 2 fork with optimization

This fork contains some performance optimizations.
Currently implements:
- Batch inserts for processing big amount of entities
- Disabling internal events when you need

The main object you need is `PerformanceConfiguration`.
Here is example for demonstration:

```
$performanceConfiguration = $em->getPerformanceConfiguration(); //Getting performance configuration
$performanceConfiguration->setIsEventsSystemEnabled(false); //Disable internal events
$performanceConfiguration->enableBatchInsert(); //Enable batch inserts
$performanceConfiguration->setMaxPerInsert(500); //Set how many entities will be inserted per one insert
```

## More resources:

* [Website](http://www.doctrine-project.org)
* [Documentation](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/index.html)
