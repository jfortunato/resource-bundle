<?php

namespace JFortunato\ResourceBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

class ResourceManager
{
    protected $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    public function save($resource)
    {
        $this->manager->persist($resource);
        $this->manager->flush();

        return $resource;
    }

    public function delete($resource)
    {
        $this->manager->remove($resource);
        $this->manager->flush();
    }

    public function createNew($resourceClass)
    {
        $className = $this->getRepository($resourceClass)->getClassName();

        return new $className;
    }

    public function getRepository($resourceClass)
    {
        return $this->manager->getRepository($resourceClass);
    }
}
