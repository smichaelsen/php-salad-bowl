# Salad Bowl

Tasty salad from the best ingredients of the PHP world. 🥗

This PHP framework doesn't take itself too important but uses and emphasizes robust, proven and well known components
from the PHP world.

When creating your web application with salad bowl you'll be using:

* [Doctrine ORM](http://www.doctrine-project.org/projects/orm.html) for your models and persistence
* [Twig](https://twig.sensiolabs.org/) as templating engine
* [Aura.Router](https://github.com/auraphp/Aura.Router) for routing 

and several more projects that are backend by their strong communities. The salad bowl just tries to collect them
in a usable way and set some good defaults.

## `config.json` reference:

* `app`: This section is reserved for your application. Place your custom options here.
* `authentication`: Can contain the following keys:
  * `columns`
  * `table`
* `database`: Can contain the following keys:
  * `dbname`
  * `driver`
  * `host`
  * `password`
  * `user`
* `entity`: Can contain the following keys:
  * `entityDirectory`
* `routesClass`: Name of the class that registers your application's routes