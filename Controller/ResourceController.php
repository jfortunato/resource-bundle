<?php

namespace JFortunato\ResourceBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\Util\Codes;
use JFortunato\ResourceBundle\Voter\ResourceVoter;
use JFortunato\ResourceBundle\Manager\ResourceManager;
use JFortunato\ResourceBundle\Exception\InvalidFormException;

abstract class ResourceController extends FOSRestController
{
    protected $resourceClass;

    protected $formType;

    protected $manager;

    protected $formFactory;

    protected $securityContext;

    protected $handler;

    public function __construct(
        $resourceClass,
        FormTypeInterface $formType,
        ResourceManager $manager,
        FormFactoryInterface $formFactory,
        SecurityContextInterface $securityContext,
        ViewHandler $handler
    )
    {
        $this->resourceClass = $resourceClass;
        $this->formType = $formType;
        $this->manager = $manager;
        $this->formFactory = $formFactory;
        $this->securityContext = $securityContext;
        $this->handler = $handler;
    }

    public function index(array $parent = array())
    {
        $resources = $this->findAllOrFail($parent);

        return $this->handleView($this->view($resources, 200));
    }

    public function show($id)
    {
        $resource = $this->findOr404($id);

        return $this->handleView($this->view($resource, 200));
    }

    public function create(Request $request)
    {
        try {
            $resource = $this->createOrFail($request);
        } catch (InvalidFormException $exception) {
            return $this->handleView($this->view($exception->getForm(), 400));
        }

        $location = $request->getPathInfo() . '/' . $resource->getId();

        return $this->handleView($this->redirectView($location, Codes::HTTP_CREATED)
            ->setData($resource));
    }

    public function update(Request $request, $id)
    {
        try {
            $resource = $this->updateOrFail($request, $id);
        } catch (InvalidFormException $exception) {
            return $this->handleView($this->view($exception->getForm(), 400));
        }

        return $this->handleView($this->view(null, 204));
    }

    public function delete($id)
    {
        $this->deleteOrFail($id);

        return $this->handleView($this->view(null, 204));
    }

    protected function handleView(View $view)
    {
        return $this->handler->handle($view);
    }

    protected function findAllOrFail(array $parent = array())
    {
        $resources = $this->manager->getRepository($this->resourceClass)->findBy($parent);

        if (count($resources) === 0) {
            return $resources;
        }

        if (!$this->securityContext->isGranted(ResourceVoter::VIEW_ALL, $resources[0])) {
            throw new AccessDeniedException;
        }

        return $resources;
    }

    protected function findOr404($id)
    {
        $resource = $this->manager->getRepository($this->resourceClass)->find($id);

        if (!$resource) {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $id));
        }

        if (!$this->securityContext->isGranted(ResourceVoter::VIEW_SINGLE, $resource)) {
            throw new AccessDeniedException;
        }

        return $resource;
    }

    protected function createOrFail(Request $request)
    {
        $resource = $this->manager->createNew($this->resourceClass);

        $form = $this->getForm($resource);
        if (!$form->submit($request->request->all(), true)->isValid()) {
            throw new InvalidFormException('Invalid submitted data.', $form);
        }

        if (!$this->securityContext->isGranted(ResourceVoter::CREATE, $resource)) {
            throw new AccessDeniedException;
        }

        return $this->manager->save($resource);
    }

    protected function updateOrFail(Request $request, $id)
    {
        $resource = $this->findOr404($id);

        $form = $this->getForm($resource);
        if (!$form->submit($request->request->all(), true)->isValid()) {
            throw new InvalidFormException('Invalid submitted data.', $form);
        }

        if (!$this->securityContext->isGranted(ResourceVoter::EDIT, $resource)) {
            throw new AccessDeniedException;
        }

        return $this->manager->save($resource);
    }

    protected function deleteOrFail($id)
    {
        $resource = $this->findOr404($id);

        if (!$this->securityContext->isGranted(ResourceVoter::DELETE, $resource)) {
            throw new AccessDeniedException;
        }

        return $this->manager->delete($resource);
    }

    protected function getForm($resource)
    {
        return $this->formFactory->create($this->formType, $resource, array('csrf_protection' => false));
    }

    public function setResourceClass($resourceClass)
    {
        $this->resourceClass = $resourceClass;
    }

    public function setFormType(FormTypeInterface $formType)
    {
        $this->formType = $formType;
    }
}
