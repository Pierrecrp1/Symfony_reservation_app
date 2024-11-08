<?php

namespace App\Controller;

use App\Entity\Service;
use App\Form\ServiceType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ServiceController extends AbstractController
{

    #[Route('/service/{id}', name: 'service_detail', requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $service = $entityManager->getRepository(Service::class)->find($id);

        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé.');
        }

        return $this->render('service/index.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/services', name: 'service_list')]
    public function list(ServiceRepository $serviceRepository): Response
    {
        $services = $serviceRepository->findAll();

        return $this->render('service/list.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/service/new', name: 'service_new')]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $service->setUser($this->getUser());
            $em->persist($service);
            $em->flush();

            return $this->redirectToRoute('user_services');
        }

        return $this->render('service/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/services', name: 'user_services')]
    #[IsGranted('ROLE_USER')]
    public function userServices(EntityManagerInterface $em): Response
    {
        $services = $em->getRepository(Service::class)->findBy(['user' => $this->getUser()]);

        return $this->render('service/user_services.html.twig', [
            'services' => $services,
        ]);
    }
}