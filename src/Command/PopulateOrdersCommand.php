<?php


namespace App\Command;


use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PopulateOrdersCommand extends Command
{
    protected static $defaultName = 'app:populate-orders';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->entityManager;

        for ($x = 1; $x <= 10; $x++) {
            $savedOrder = $entityManager->find(Order::class, $x);
            if ($savedOrder === null) {
                $order = new Order();
                $order->setId($x);
                $order->setCustomerId($x * 10);
                $order->setTotalPrice($x * 30);
                $entityManager->persist($order);
                $entityManager->flush();
            } else {
                $savedOrder->setSent(false);
                $entityManager->merge($savedOrder);
                $entityManager->flush();
            }
        }

        return 0;
    }
}