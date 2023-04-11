<?php

namespace Sherlockode\SonataSortableBundle\Controller;

use Sherlockode\SonataSortableBundle\Manager\SortableManager;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class SortableController extends CRUDController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SortableManager $sortableManager
    ) {
    }

    /**
     * @param string $direction
     *
     * @return Response
     */
    public function moveAction(string $direction): Response
    {
        if (!$this->admin->isGranted('EDIT')) {
            $this->addFlash('danger', $this->translator->trans(
                'You are not authorized to perform this action',
                [],
                'sherlockode_sonata_sortable'
            ));

            return new RedirectResponse($this->admin->generateUrl(
                'list',
                ['filter' => $this->admin->getFilterParameters()]
            ));
        }

        $object = $this->admin->getSubject();
        $this->sortableManager->setPosition($object, 'position', $direction);
        $this->admin->update($object);

        return new RedirectResponse($this->admin->generateUrl(
            'list',
            ['filter' => $this->admin->getFilterParameters()]
        ));
    }
}
