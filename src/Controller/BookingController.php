<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Service;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BookingController extends AbstractController
{
    #[Route('/booking', name: 'user_bookings')]
    #[IsGranted('ROLE_USER')]
    public function index(BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();

        $bookings = $bookingRepository->findBy(['user' => $user]);

        return $this->render('booking/index.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/booking/{id}', name: 'booking_detail', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function show(Booking $booking): Response
    {
        if ($booking->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette réservation.');
        }

        return $this->render('booking/detail.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/booking/{id}/manage', name: 'booking_manage')]
    #[IsGranted('ROLE_USER')]
    public function manageBooking(Booking $booking): Response
    {
        if ($booking->getService()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas l\'autorisation d\'accéder à cette réservation.');
        }

        return $this->render('booking/manage.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/booking/{id}/cancel', name: 'booking_cancel', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(Booking $booking, EntityManagerInterface $entityManager): Response
    {
        if ($booking->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à annuler cette réservation.');
        }

        $booking->setStatus('cancelled');
        $entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été annulée.');

        return $this->redirectToRoute('user_bookings');
    }

    #[Route('/booking/choose-date', name: 'booking_choose_date')]
    #[IsGranted('ROLE_USER')]
    public function chooseDate(Request $request, EntityManagerInterface $em): Response
    {
        $serviceId = $request->query->get('serviceId');
        $service = $em->getRepository(Service::class)->find($serviceId);

        if (!$service) {
            throw $this->createNotFoundException('Le service spécifié est introuvable.');
        }

        return $this->render('booking/choose_date.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/booking/select-time', name: 'booking_select_time')]
    #[IsGranted('ROLE_USER')]
    public function selectTime(Request $request, EntityManagerInterface $em): Response
    {
        $serviceId = $request->query->get('serviceId');
        $date = $request->query->get('date');

        if (!$serviceId || !$date) {
            throw $this->createNotFoundException('Service ou date non spécifiée.');
        }

        $service = $em->getRepository(Service::class)->find($serviceId);

        if (!$service) {
            throw $this->createNotFoundException('Le service spécifié est introuvable.');
        }

        $selectedDate = new \DateTime($date);
        $start = (clone $selectedDate)->setTime(8, 30);
        $end = (clone $selectedDate)->setTime(18, 30);

        $timeSlots = [];
        while ($start <= $end) {
            $timeSlots[] = clone $start;
            $start->modify('+30 minutes');
        }

        // $bookedSlots = $em->getRepository(Booking::class)->createQueryBuilder('b')
        //     ->select('b.date')
        //     ->where('b.service = :service')
        //     ->andWhere('DATE(b.date) = :date')
        //     ->andWhere('b.status IN (:statuses)')
        //     ->setParameters([
        //         'service' => $service,
        //         'date' => $selectedDate->format('Y-m-d'),
        //         'statuses' => ['pending', 'confirmed'],
        //     ])
        //     ->getQuery()
        //     ->getResult();

        $startOfDay = (clone $selectedDate)->setTime(0, 0, 0);
        $endOfDay = (clone $selectedDate)->setTime(23, 59, 59);
        
        $bookedSlots = $em->getRepository(Booking::class)->createQueryBuilder('b')
            ->select('b.date')
            ->where('b.service = :service')
            ->andWhere('b.date BETWEEN :startOfDay AND :endOfDay')
            ->andWhere('b.status IN (:statuses)')
            ->setParameter('service', $service)
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->getQuery()
            ->getResult();
        


        $unavailableSlots = array_map(fn($b) => $b['date']->format('H:i'), $bookedSlots);

        return $this->render('booking/select_time.html.twig', [
            'service' => $service,
            'date' => $selectedDate,
            'timeSlots' => $timeSlots,
            'unavailableSlots' => $unavailableSlots,
        ]);
    }

    #[Route('/booking/confirm', name: 'booking_confirm')]
    #[IsGranted('ROLE_USER')]
    public function confirmBooking(Request $request, EntityManagerInterface $em): Response
    {
        $serviceId = $request->query->get('serviceId');
        $dateTime = $request->query->get('date');

        if (!$serviceId || !$dateTime) {
            return $this->redirectToRoute('booking_choose_date');
        }

        $service = $em->getRepository(Service::class)->find($serviceId);

        if (!$service) {
            throw $this->createNotFoundException('Le service spécifié est introuvable.');
        }

        $bookingDateTime = new \DateTime($dateTime);

        return $this->render('booking/confirm.html.twig', [
            'service' => $service,
            'dateTime' => $bookingDateTime,
        ]);
    }

    #[Route('/booking/new', name: 'booking_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $serviceId = $request->request->get('serviceId');
        $dateTime = $request->request->get('dateTime');

        if (!$serviceId || !$dateTime) {
            return $this->redirectToRoute('booking_choose_date');
        }

        $service = $em->getRepository(Service::class)->find($serviceId);

        if (!$service) {
            throw $this->createNotFoundException('Le service spécifié est introuvable.');
        }

        // Convertit la date et l'heure en objet DateTime
        $bookingDateTime = new \DateTime($dateTime);

        // Vérifie si le créneau est déjà réservé pour ce service
        $existingBooking = $em->getRepository(Booking::class)->findOneBy([
            'service' => $service,
            'date' => $bookingDateTime,
            'status' => ['pending', 'confirmed']
        ]);

        if ($existingBooking) {
            $this->addFlash('error', 'Le créneau sélectionné est déjà réservé.');
            return $this->redirectToRoute('booking_select_time', [
                'serviceId' => $serviceId,
                'date' => $bookingDateTime->format('Y-m-d'),
            ]);
        }

        // Création de la réservation
        $booking = new Booking();
        $booking->setService($service);
        $booking->setUser($this->getUser());
        $booking->setDate($bookingDateTime);
        $booking->setStatus('pending');

        $em->persist($booking);
        $em->flush();

        return $this->redirectToRoute('booking_success');
    }

    #[Route('/booking/success', name: 'booking_success')]
    public function confirm(): Response
    {
        return $this->render('booking/success.html.twig');
    }

    #[Route('/booking/{id}/accept', name: 'booking_accept', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function acceptBooking(Booking $booking, EntityManagerInterface $em): Response
    {
        if ($booking->getService()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $booking->setStatus('confirmed');
        $em->flush();

        return $this->redirectToRoute('user_services');
    }

    #[Route('/booking/{id}/cancel', name: 'booking_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancelBooking(Booking $booking, EntityManagerInterface $em): Response
    {
        if ($booking->getService()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $booking->setStatus('cancelled');
        $em->flush();

        return $this->redirectToRoute('user_services');
    }
}