<?php

namespace Fortune\Tests\Voter;

use Fortune\Tests\TestCase;
use Mockery as m;
use Fortune\ResourceBundle\Voter\ResourceVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ResourceVoterTest extends TestCase
{
    public function setUp()
    {
        $this->config = array(
            array(
                'entity' => 'StdClass',
                'access_control' => array(
                    'default' => array('role' => 'ROLE_ADMIN', 'owner' => false),
                ),
            ),
        );

        $this->voter = new ResourceVoter($this->config);
    }

    public function testSupportsCrudAttributes()
    {
        $this->assertTrue($this->voter->supportsAttribute('index'));
        $this->assertTrue($this->voter->supportsAttribute('show'));
        $this->assertTrue($this->voter->supportsAttribute('create'));
        $this->assertTrue($this->voter->supportsAttribute('update'));
        $this->assertTrue($this->voter->supportsAttribute('delete'));
        $this->assertFalse($this->voter->supportsAttribute('foo'));
    }

    public function testOnlySupportsClassesInConfig()
    {
        $this->assertTrue($this->voter->supportsClass('StdClass'));
        $this->assertFalse($this->voter->supportsClass('Bar\Entity'));
    }

    public function testAccessIsDeniedWhenRoleIsntAllowed()
    {
        $token = m::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->shouldReceive('getRoles')->andReturn(array($role = m::mock('Symfony\Component\Security\Core\Role\RoleInterface')));
        $role->shouldReceive('getRole')->andReturn('ROLE_USER');
        $resource = m::mock('StdClass');

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $resource, array('index')));
    }

    public function testAccessIsGrantedWhenRoleIsAllowed()
    {
        $token = m::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->shouldReceive('getRoles')->andReturn(array($role = m::mock('Symfony\Component\Security\Core\Role\RoleInterface')));
        $role->shouldReceive('getRole')->andReturn('ROLE_ADMIN');
        $resource = m::mock('StdClass');

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $resource, array('index')));
    }

    public function testAccessIsDeniedWhenUserIsntAllowed()
    {
        $this->config[0]['entity'] = 'Fortune\ResourceBundle\Voter\ResourceOwnerCheckerInterface';
        $this->config[0]['access_control']['default']['owner'] = true;
        $this->voter = new ResourceVoter($this->config);

        $token = m::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->shouldReceive('getUser')->andReturn($user = m::mock('StdClass'));
        $user->shouldReceive('getId')->andReturn('1');
        $token->shouldReceive('getRoles')->andReturn(array());
        $resource = m::mock('Fortune\ResourceBundle\Voter\ResourceOwnerCheckerInterface');
        $resource->shouldReceive('getAllOwnerUserIds')->once()->andReturn(array(2));

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $resource, array('index')));
    }

    public function testAccessIsGrantedWhenUserIsAllowed()
    {
        $this->config[0]['entity'] = 'Fortune\ResourceBundle\Voter\ResourceOwnerCheckerInterface';
        $this->config[0]['access_control']['default']['owner'] = true;
        $this->voter = new ResourceVoter($this->config);

        $token = m::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->shouldReceive('getUser')->andReturn($user = m::mock('StdClass'));
        $user->shouldReceive('getId')->andReturn('1');
        $token->shouldReceive('getRoles')->andReturn(array());
        $resource = m::mock('Fortune\ResourceBundle\Voter\ResourceOwnerCheckerInterface');
        $resource->shouldReceive('getAllOwnerUserIds')->once()->andReturn(array(1));

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $resource, array('index')));

    }
}
