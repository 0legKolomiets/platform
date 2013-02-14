<?php

namespace Oro\Bundle\UserBundle\Controller\Api\Soap;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\UserBundle\Entity\User;

class ProfileController extends BaseController
{
    /**
     * @Soap\Method("getUsers")
     * @Soap\Param("page", phpType = "int")
     * @Soap\Param("limit", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\UserBundle\Entity\User[]")
     */
    public function сgetAction($page = 1, $limit = 10)
    {
        return $this->container->get('besimple.soap.response')->setReturnValue(
            $this->container->get('knp_paginator')->paginate(
                $this->container->get('doctrine.orm.entity_manager')
                    ->createQuery('SELECT u FROM OroUserBundle:User u ORDER BY u.id'),
                (int) $page,
                (int) $limit
            )
        );
    }

    /**
     * @Soap\Method("getUser")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\UserBundle\Entity\User")
     */
    public function getAction($id)
    {
        return $this->container->get('besimple.soap.response')->setReturnValue(
            $this->getEntity('OroUserBundle:User', $id)
        );
    }

    /**
     * @Soap\Method("createUser")
     * @Soap\Param("profile", phpType = "\Oro\Bundle\UserBundle\Entity\User")
     * @Soap\Result(phpType = "boolean")
     */
    public function createAction($profile)
    {
        $entity = $this->getUserManager()->createFlexible();
        $form   = $this->container->get('oro_user.form.profile.api')->getName();

        $this->fixRequest($form);
        $this->fixFlexRequest($entity, $form);

        return $this->container->get('besimple.soap.response')->setReturnValue(
            $this->container->get('oro_user.form.handler.profile.api')->process($entity)
        );
    }

    /**
     * @Soap\Method("updateUser")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Param("profile", phpType = "\Oro\Bundle\UserBundle\Entity\User")
     * @Soap\Result(phpType = "boolean")
     */
    public function updateAction($id, $profile)
    {
        $entity = $this->getEntity('OroUserBundle:User', $id);
        $form   = $this->container->get('oro_user.form.profile.api')->getName();

        $this->fixRequest($form);
        $this->fixFlexRequest($entity, $form);

        return $this->container->get('besimple.soap.response')->setReturnValue(
            $this->container->get('oro_user.form.handler.profile.api')->process($entity)
        );
    }

    /**
     * @Soap\Method("deleteUser")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "boolean")
     */
    public function deleteAction($id)
    {
        $entity = $this->getEntity('OroUserBundle:User', $id);

        $this->getUserManager()->deleteUser($entity);

        return $this->container->get('besimple.soap.response')->setReturnValue(true);
    }

    /**
     * @Soap\Method("getUserRoles")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\UserBundle\Entity\Role[]")
     */
    public function getRolesAction($id)
    {
        return $this->container->get('besimple.soap.response')->setReturnValue(
            $this->getEntity('OroUserBundle:User', $id)->getRoles()
        );
    }

    /**
     * @Soap\Method("getUserGroups")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\UserBundle\Entity\Group[]")
     */
    public function getGroupsAction($id)
    {
        return $this->container->get('besimple.soap.response')->setReturnValue(
            $this->getEntity('OroUserBundle:User', $id)->getGroups()
        );
    }

    /**
     * @return \Oro\Bundle\UserBundle\Entity\UserManager
     */
    protected function getUserManager()
    {
        return $this->container->get('oro_user.manager');
    }

    /**
     * This is temporary fix for flexible entity values processing.
     *
     * @param User   $user
     * @param string $name Form name
     */
    protected function fixFlexRequest(User $entity, $name)
    {
        $request = $this->container->get('request')->request;
        $data    = $request->get($name, array());
        $values  = array();

        if (array_key_exists('roles', $data)) {
            $data['rolesCollection'] = $data['roles'];

            unset($data['roles']);
        }

        if (array_key_exists('attributes', $data)) {
            $attrs = $this->getUserManager()->getAttributeRepository()->findBy(array('entityType' => get_class($entity)));
            $i     = 0;

            // transform simple notation into FlexibleType format
            foreach ($data['attributes'] as $field => $value) {
                foreach ($attrs as $attr) {
                    if ($attr->getCode() == $field) {
                        $values[$i]['id']   = $attr->getId();
                        $values[$i]['data'] = $value;

                        $i++;
                    }
                }
            }
        }

        $data['attributes'] = $values;

        $request->set($name, $data);
    }
}
