<?php

namespace JFortunato\ResourceBundle\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ResourceVoter implements VoterInterface
{
    const VIEW_ALL    = 'index';
    const VIEW_SINGLE = 'show';
    const CREATE      = 'create';
    const EDIT        = 'update';
    const DELETE      = 'delete';

    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }


    public function supportsAttribute($attribute)
    {
        return in_array($attribute, array(
            self::VIEW_ALL,
            self::VIEW_SINGLE,
            self::CREATE,
            self::EDIT,
            self::DELETE,
        ));
    }

    public function supportsClass($class)
    {
        foreach ($this->config as $value) {
            if ($class === $value['entity'] || is_subclass_of($class, $value['entity'])) {
                return true;
            }
        }

        return false;
    }

    public function vote(TokenInterface $token, $resource, array $attributes)
    {
        // make sure we can cast a vote for this resource
        if (!$this->supportsClass(get_class($resource)))
        {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // we only allow one required attribute at a time
        if (count($attributes) !== 1 || !$this->supportsAttribute($attributes[0]))
        {
            throw new \InvalidArgumentException(sprintf('Resource voter must provide exactly one of: index|show|create|update|delete'));
        }

        $aclRestrictOwner = $this->getResourceConfigAclValue($resource, $attributes[0], 'owner');
        $aclRestrictRole = $this->getResourceConfigAclValue($resource, $attributes[0], 'role');

        // check if this resource is being restricted to certain users
        if ($aclRestrictOwner)
        {
            if (!($resource instanceof ResourceOwnerCheckerInterface))
            {
                throw new \RuntimeException(sprintf('The resource %s must implement the interface %s', get_class($resource), __NAMESPACE__ . 'ResourceOwnerCheckerInterface'));
            }

            if (in_array($token->getUser()->getId(), $resource->getAllOwnerUserIds()))
            {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        // check if this resource is restricted by current users role
        foreach ($token->getRoles() as $role) {
            if ($aclRestrictRole === $role->getRole())
            {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return VoterInterface::ACCESS_DENIED;
    }

    protected function getResourceConfigAclValue($resource, $method, $attribute)
    {
        $class = get_class($resource);

        foreach ($this->config as $value) {
            if ($value['entity'] === $class || is_subclass_of($class, $value['entity'])) {
                if (isset($value['access_control'][$method]) && isset($value['access_control'][$method][$attribute])) {
                    return $value['access_control'][$method][$attribute];
                }

                return $value['access_control']['default'][$attribute];
            }
        }
    }
}
