## Resource Bundle

> This bundle is currently a work in progress.

The idea of this bundle is to take FOSRestBundle one step further when building out RESTful API's. Imagine all of your controllers looking like this:

    <?php

    namespace Your\Namespace\Here;

    use Fortune\Controller\ResourceController;
    use Symfony\Component\HttpFoundation\Request;

    class DogsController extends ResourceController
    {
        public function getDogsAction()
        {
            return $this->index();
        }

        public function getDogAction($id)
        {
            return $this->show($id);
        }

        public function postDogsAction(Request $request)
        {
            return $this->create($request);
        }

        public function putDogAction(Request $request, $id)
        {
            return $this->update($request, $id);
        }

        public function deleteDogAction($id)
        {
            return $this->delete($id);
        }
    }

All of the CRUD logic for the 'dogs' resource is deferred to the parent ResourceController class, keeping you API controller very simple. The ResourceController also handles access control for each individual resource through simple configuration.

## Installation

#### Require with Composer
```
composer require jfortunato/resource-bundle dev-master
```

#### Configure Bundles in AppKernel.php
This bundle requires 2 other bundles to be configured in addition to FortuneResourceBundle:  
**FOSRestBundle**  
**JMSSerializerBundle**

So your AppKernel.php file should look like the following:

```
// app/AppKernel.php

...

$bundles = array(
    ... // any other bundles
    new JMS\SerializerBundle\JMSSerializerBundle(),
    new FOS\RestBundle\FOSRestBundle(),
    new Fortune\ResourceBundle\FortuneResourceBundle(),
);

...
```

## Usage

Usage is based on FOSRestBundle routing & controller configuration, with a few minor differences. It is recommended to contain each resource in its own bundle, so we will begin by creating a "Dogs" resource bundle.

```
// create DogsBundle and follow interactive prompt
php app/console generate:bundle --namespace=Acme/Bundle/DogsBundle
```

### Creating all the necessary resource components
There are 3 required components that make up a resource  

* **Entity**
* **Controller**
* **Form Type**

The bulk of the work for creating these 3 components can be done automatically with console generators.

##### Entity
---
Assuming you are using Doctrine you can generate a simple Dog entity:

```
// create Dog entity
php app/console generate:doctrine:entity --entity=AcmeDogsBundle:Dog --no-interaction
```

##### Controller
---

Next we are going to want to generate a resourceful dogs controller.

```
// create DogsController without prompt
php app/console generate:controller --controller=AcmeDogsBundle:Dogs --no-interaction
```

A few modifications will be made to this controller to ensure all the heavy lifting of the dogs resource is deferred to the parent class. So edit the controller we just created and make the following changes:

```
// src/Acme/Bundle/DogsBundle/Controller/DogsController.php
<?php

namespace Acme\Bundle\DogsBundle\Controller;

use Fortune\ResourceBundle\Controller\ResourceController;

// make sure to change "Controller" to "ResourceController"
class DogsController extends ResourceController
{
    public function getDogsAction()
    {
        return $this->index();
    }
}
```

> **NOTE:** The controller methods are taken from FOSRestBundle. To see a list of all available routes/methods visit the [FOSRestBundle Documentation](https://github.com/FriendsOfSymfony/FOSRestBundle/blob/master/Resources/doc/5-automatic-route-generation_single-restful-controller.md)

##### Form Type
---
The form generation is very simple as well.

```
// create form type
php app/console generate:doctrine:form AcmeDogsBundle:Dog
```

### Configuration

Now that all of our components are generated we are ready to start using the resource. While you could put all configuration directly in the `app/config/config.php` file, it is recommended to keep your resources in its own file and import it into the config.php file.

Start off by **creating the file app/config/resources.yml**, and using all the components we generated above.

```
// app/config/resources.yml
fortune_resource:
    resources:
        dogs:
            entity: Acme\Bundle\DogsBundle\Entity\Dog
            controller: Acme\Bundle\DogsBundle\Controller\DogsController
            form_type: Acme\Bundle\DogsBundle\Form\DogType
            access_control: ~

        ... // repeat for each resource
```

And finish by importing the file we just created into `app/config/config.php`

```
// app/config/config.php
imports:
    ... // other imports
    - { resource: resources.yml }
```

Now we are ready to update the routing to point to our DogsController component. This is basically just an FOSRestBundle route configuration with one minor change to the resource parameter.

```
// app/config/routing.yml
dogs:
    type: rest
    resource: "fortune.controller.dogs" // here we specify the dogs controller as a service
    prefix: /api/v1
```

### Try it out
> **NOTE:** It is assumed that your database has already been setup and updated with the new schema that you created with the Dogs entity. If you started from scratch while following this example you can get your db up and running with the following commands:
>
>   ```
>    php app/console doctrine:database:create
>    php app/console doctrine:schema:create
>   ```

With your server up and running and the database schema updated, navigate to the "get_dogs" route by pointing your browser to `localhost:8000/api/v1/dogs.json`. If all went well you shouldn't get any errors and you should see a simple empty JSON array `[]`. The result is an empty JSON array because there aren't any dogs in the database! Had there been any dogs in the database all of the columns would be output in the response. You are free to play around by adding dog records in the database.

## Access Control
> **NOTE:** It is recommended that you use OAuth for user authentication in your REST API. The examples that follow will assume you have configured OAuth for receiving an access token that will be used to interact with your API. Checkout [FOSOAuthServerBundle](https://github.com/FriendsOfSymfony/FOSOAuthServerBundle) to quickly get started with OAuth.

There are currently 2 ways to protect a resource:

1. By role

2. By owner/specific users

You can use these protections as a default for all route methods or on a per method basis for the following methods:

* default
* index
* show
* create
* update
* delete

#### Editing security.yml

Make sure your api routes that you are protecting are passed through the symfony firewall (This assumes you are using FOSOAuthServerBundle). **Important:** After setting up OAuth each request for a resource will need to include an access token either through a GET parameter or through an Authorization header.

```
// app/config/security.yml
security:
    ...
    firewalls:
        api:
            pattern: ^/api
            fos_oauth: true
            stateless: true
    ...
    access_control:
        ...
        - { path: ^/api, roles: [ IS_AUTHENTICATED_FULLY ] }
```

#### Restricting By Role
By default each resource is restricted to the role "ROLE_SUPER_ADMIN". You may likely wish to change this by specifying a different role for the resource.

```
// app/config/resources.yml
fortune_resource:
    resources:
        dogs:
            ...
            access_control:
                default: { role: ROLE_ADMIN }
```

Or perhaps you want to only restrict by role for a specific action.

```
// app/config/resources.yml
fortune_resource:
    resources:
        dogs:
            ...
            access_control:
                default: { role: ROLE_USER }
                delete: { role: ROLE_ADMIN }
```

#### Restricting By Owner/Specific Users
Restricting a resource to specific users takes a bit of custom code on your part. Let's say only the user who created a dog should be allowed to view that dog. The first step is to edit the resources.yml file to show that we are restricting by owner with a simple boolean value.

```
// app/config/resources.yml
fortune_resource:
    resources:
        dogs:
            ...
            access_control:
                default: { owner: true }
```

Now, since the dogs owner can be determined programmatically in a number of different ways, its up to you to determine its owner. For simplicity lets assume `Dog` is a many-to-one relation to a `User` *(that is, there is a user_id field on the `Dog` table)*. Here is how we would restrict a dog to its owner:

```
// src/Acme/Bundle/DogsBundle/Entity/Dog.php
<?php

namespace Acme\Bundle\DogsBundle\Entity;

use Fortune\ResourceBundle\Voter\ResourceOwnerCheckerInterface;

class Dog implements ResourceOwnerCheckerInterface
{
    protected $user;

    ...

    public function getAllOwnerUserIds()
    {
        return array($this->user->getId());
    }
}
```

**Basically, whatever id's are returned from the `getAllOwnerUserIds` method are the dogs owners.** In this case it is simply a single owner but there could be cases where the dog has multiple owners, so you would need to figure out a way to return all of those owner's id's as an array.

## More On Controllers

In the basic DogsController example above we only showed one method/route: `getDogsAction`. With this bundle more basic RESTful methods are available (GET, POST, PUT, DELETE). In the example below, all of the available methods are shown.

```
// src/Acme/Bundle/DogsBundle/Controller/DogsController.php
<?php

namespace Acme\Bundle\DogsBundle\Controller;

use Fortune\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;

// make sure to change "Controller" to "ResourceController"
class DogsController extends ResourceController
{
    /**
     * GET all dogs
     */
    public function getDogsAction()
    {
        return $this->index();
    }

    /**
     * GET a single dog
     */
    public function getDogAction($id)
    {
        return $this->show($id);
    }

    /**
     * POST a new dog
     */
    public function postDogAction(Request $request)
    {
        return $this->create($request);
    }

    /**
     * PUT an existing dog
     */
    public function putDogAction(Request $request, $id)
    {
        return $this->update($request, $id);
    }

    /**
     * DELETE an existing dog
     */
    public function deleteDogAction($id)
    {
        return $this->delete($id);
    }
}
```

#### Parent Resources
> TODO

#### Free Documentation
> Be sure to check out [nelmio/api-doc-bundle](https://github.com/nelmio/NelmioApiDocBundle/blob/master/Resources/doc/index.md) for automatic documentation of your API. Simple comments to the DogsController methods are all that are needed for fully browser navigatable documentation.
