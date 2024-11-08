<?php

namespace App\DataFixtures;

use App\Entity\Service;
use App\Entity\Booking;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('testuser@example.com');
        $user->setName('Test User');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $manager->persist($user);

        $servicesData = [
            ['name' => 'Coiffure', 'description' => 'Coupe et coiffure', 'price' => 25.00, 'duration' => 30],
            ['name' => 'Massage', 'description' => 'Massage relaxant', 'price' => 60.00, 'duration' => 60],
            ['name' => 'Manucure', 'description' => 'Manucure avec vernis', 'price' => 20.00, 'duration' => 45],
        ];

        $services = [];
        foreach ($servicesData as $data) {
            $service = new Service();
            $service->setName($data['name']);
            $service->setDescription($data['description']);
            $service->setPrice($data['price']);
            $service->setDuration($data['duration']);
            $service->setUser($user);
            $manager->persist($service);
            $services[] = $service;
        }

        $bookingData = [
            ['service' => $services[0], 'date' => new \DateTime('2024-11-10 10:00:00'), 'user' => $user],
            ['service' => $services[0], 'date' => new \DateTime('2024-11-10 11:00:00'), 'user' => $user],
            ['service' => $services[1], 'date' => new \DateTime('2024-11-11 14:00:00'), 'user' => $user],
        ];

        foreach ($bookingData as $data) {
            $booking = new Booking();
            $booking->setService($data['service']);
            $booking->setDate($data['date']);
            $booking->setUser($data['user']);
            $booking->setStatus('confirmed');
            $manager->persist($booking);
        }

        $manager->flush();
    }
}