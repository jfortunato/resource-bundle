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

        public function postDogAction(Request $request)
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
