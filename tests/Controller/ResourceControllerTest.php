<?php

namespace Fortune\Tests\Controller;

use Fortune\Tests\TestCase;
use Mockery as m;
use Fortune\ResourceBundle\Controller\ResourceController;

class ResourceControllerTest extends TestCase
{
    public function setUp()
    {
        $this->entity = 'Entity\Foo';
        $this->formType = m::mock('Symfony\Component\Form\FormTypeInterface');
        $this->manager = m::mock('Fortune\ResourceBundle\Manager\ResourceManager');
        $this->formFactory = m::mock('Symfony\Component\Form\FormFactoryInterface');
        $this->securityContext = m::mock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->viewHandler = m::mock('FOS\RestBundle\View\ViewHandler');

        $this->controller = m::mock('Fortune\ResourceBundle\Controller\ResourceController', array(
            $this->entity,
            $this->formType,
            $this->manager,
            $this->formFactory,
            $this->securityContext,
            $this->viewHandler
        ))->makePartial();

        $this->request = m::mock('Symfony\Component\HttpFoundation\Request', array('getPathInfo' => 'http://localhost'));
        $this->request->request = m::mock(array('all' => array()));
    }

    public function testIndexCanFindAllResources()
    {
        $this->manager->shouldReceive('getRepository')->once()->andReturn($repository = m::mock('StdClass'));
        $repository->shouldReceive('findBy')->once()->andReturn($resource = array('foo', 'bar'));
        $this->securityContext->shouldReceive('isGranted')->andReturn(true);

        $this->mockHandle($resource);
        $this->assertSame('["foo","bar"]', $this->controller->index());
    }

    public function testIndexReturnsEmptyJSONWhenNoResourceExists()
    {
        $this->manager->shouldReceive('getRepository')->once()->andReturn($repository = m::mock('StdClass'));
        $repository->shouldReceive('findBy')->once()->andReturn($resource = array());

        $this->mockHandle($resource);
        $this->assertSame('[]', $this->controller->index());
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testIndexThrowsAccessDeniedWhenNotGranted()
    {
        $this->manager->shouldReceive('getRepository')->once()->andReturn($repository = m::mock('StdClass'));
        $repository->shouldReceive('findBy')->once()->andReturn($resource = array('foo'));
        $this->securityContext->shouldReceive('isGranted')->andReturn(false);

        $this->controller->index();
    }

    public function testShowCanFindSingleResource()
    {
        $this->manager->shouldReceive('getRepository')->once()->andReturn($repository = m::mock('StdClass'));
        $repository->shouldReceive('find')->once()->with(1)->andReturn($resource = array('id' => 1));
        $this->securityContext->shouldReceive('isGranted')->andReturn(true);

        $this->mockHandle($resource);
        $this->assertSame('{"id":1}', $this->controller->show(1));
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testShowThrowsNotFoundExceptionWhenNoResourceExists()
    {
        $this->manager->shouldReceive('getRepository')->once()->andReturn($repository = m::mock('StdClass'));
        $repository->shouldReceive('find')->once()->with(1)->andReturn($resource = array());

        $this->controller->show(1);
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testShowThrowsAccessDeniedWhenNotGranted()
    {
        $this->manager->shouldReceive('getRepository')->once()->andReturn($repository = m::mock('StdClass'));
        $repository->shouldReceive('find')->once()->with(1)->andReturn($resource = array('foo'));
        $this->securityContext->shouldReceive('isGranted')->andReturn(false);

        $this->controller->show(1);
    }

    public function testCreateCanCreateANewResource()
    {
        $this->manager->shouldReceive('createNew')->with($this->entity)->andReturn($resource = m::mock('StdClass', array('getId' => 1)));
        $this->formFactory->shouldReceive('create')->with($this->formType, $resource, array('csrf_protection' => false))->andReturn($form = m::mock('StdClass'));
        $form->shouldReceive('submit')->andReturn($form);
        $form->shouldReceive('isValid')->andReturn(true);
        $this->securityContext->shouldReceive('isGranted')->andReturn(true);
        $this->manager->shouldReceive('save')->once()->with($resource)->andReturn($resource);

        $this->mockHandle($resource);
        $this->controller->create($this->request);
    }

    public function testCreateRendersErrorWithBadInput()
    {
        $this->manager->shouldReceive('createNew')->with($this->entity)->andReturn($resource = m::mock('StdClass', array('getId' => 1)));
        $this->formFactory->shouldReceive('create')->with($this->formType, $resource, array('csrf_protection' => false))->andReturn($form = m::mock('StdClass'));
        $form->shouldReceive('submit')->andReturn($form);
        $form->shouldReceive('isValid')->andReturn(false);

        $this->mockHandle($resource);
        $this->controller->create($this->request);
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testCreateThrowsAccessDeniedWhenNotGranted()
    {
        $this->manager->shouldReceive('createNew')->with($this->entity)->andReturn($resource = m::mock('StdClass', array('getId' => 1)));
        $this->formFactory->shouldReceive('create')->with($this->formType, $resource, array('csrf_protection' => false))->andReturn($form = m::mock('StdClass'));
        $form->shouldReceive('submit')->andReturn($form);
        $form->shouldReceive('isValid')->andReturn(true);
        $this->securityContext->shouldReceive('isGranted')->andReturn(false);

        $this->controller->create($this->request);
    }

    public function testUpdateCanUpdateExistingResource()
    {
        $this->manager->shouldReceive('getRepository')->once()->andReturn($repository = m::mock('StdClass'));
        $repository->shouldReceive('find')->once()->with(1)->andReturn($resource = array('id' => 1));
        $this->securityContext->shouldReceive('isGranted')->andReturn(true);

        $this->formFactory->shouldReceive('create')->with($this->formType, $resource, array('csrf_protection' => false))->andReturn($form = m::mock('StdClass'));
        $form->shouldReceive('submit')->andReturn($form);
        $form->shouldReceive('isValid')->andReturn(true);
        $this->securityContext->shouldReceive('isGranted')->andReturn(true);
        $this->manager->shouldReceive('save')->once()->with($resource)->andReturn($resource);

        $this->mockHandle($resource);
        $this->controller->update($this->request, 1);
    }

    public function testDeleteCanDeleteResource()
    {
        $this->manager->shouldReceive('getRepository')->once()->andReturn($repository = m::mock('StdClass'));
        $repository->shouldReceive('find')->once()->with(1)->andReturn($resource = array('id' => 1));
        $this->securityContext->shouldReceive('isGranted')->andReturn(true);
        $this->manager->shouldReceive('delete')->once()->with($resource);

        $this->mockHandle($resource);
        $this->controller->delete(1);
    }

    protected function mockHandle($resource)
    {
        $this->viewHandler->shouldReceive('handle')->once()->with(m::type('FOS\RestBundle\View\View'))->andReturn(json_encode($resource, true));
    }
}
