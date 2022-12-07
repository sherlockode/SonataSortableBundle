Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require sherlockode/sonata-sortable-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require sherlockode/sonata-sortable-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Sherlockode\SonataSortableBundle\SherlockodeSonataSortableBundle::class => ['all' => true],
];
```

Sortable behavior in admin listing
==================================

Pre-requisites
--------------

You need to have a working Sonata admin and to have installed and configured `gedmo/doctrine-extensions` (check `stof/doctrine-extensions-bundle` for easy integration).

The recipe
----------

First of all, add a position property in your entity

```php
// src/Entity/Category.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="category")
 * @ORM\Entity
 */
class Category
{
    /**
     * @var int
     * 
     * @Gedmo\SortablePosition
     * @ORM\Column(name="position", type="integer")
     */
    private $position;
}
```

To change this position with Sonata, we need to add a new route in our admin:

```php
// src/Admin/CategoryAdmin.php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollectionInterface;

class CategoryAdmin extends AbstractAdmin
{
    // ...
    
    /**
     * @param RouteCollectionInterface $collection
     *
     * @return void
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('move', $this->getRouterIdParameter().'/move/{direction}');
    }
}
```

Update the admin configuration to use our custom controller

```yaml
services:
    admin.category:
        class: 'App\Admin\CategoryAdmin'
        arguments: [ ~, App\Entity\Category, 'Sherlockode\SonataSortableBundle\Controller\SortableController' ]
        tags:
            - { name: sonata.admin, manager_type: orm, label: Categories }
```

Then, add default sort by position in the admin:

```php
// src/Admin/CategoryAdmin.php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;

class CategoryAdmin extends AbstractAdmin
{
    // ...
    
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'ASC';
        $sortValues[DatagridInterface::SORT_BY] = 'position';
    }
}
```

Add controls to allow the user to change the position of items:

```php
// src/Admin/CategoryAdmin.php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;

class CategoryAdmin extends AbstractAdmin
{
    // ...
    
    /**
     * @inheritDoc
     */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            // your other fields
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'move' => [
                        'template' => '@SherlockodeSonataSortable/list__action_move.html.twig',
                    ],
                ],
            ])
        ;
    }
}
```
