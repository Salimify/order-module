<?php

namespace App\Controller;

use App\Entity\Order;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyBundles\KafkaBundle\DependencyInjection\Traits\ProducerTrait;

class OrderController extends AbstractController
{
    use ProducerTrait;

    /**
     * @Route("/order/{id}", name="order")
     */
    public function index(Request $request, string $id)
    {
        $param = $request->query->get('sent');
        $sent = $param === 'true';
        $entityManager = $this->getDoctrine()->getManager();
        $order = $entityManager->find(Order::class, $id);
        if ($order !== null) {
            if ($sent === true) {
                $entityManager->getConnection()->beginTransaction();
                try {
                    $order->setSent($sent);
                    $entityManager->merge($order);
                    $entityManager->flush();
                    $entityManager->getConnection()->commit();
                    $this->send([
                        'COMMAND', 'GENERATE_VOUCHER_COMMAND'
                    ]);
                    return $this->json([
                        'Message' => 'Order sent successfully',
                    ]);
                } catch (Exception $e) {
                    $entityManager->getConnection()->rollBack();
                    return $this->json([
                        'Message' => 'Order sent status failed to persist',
                    ]);
                }
            } else {
                return $this->json([
                    'Message' => 'Order sent status did not update: false',
                ]);
            }
        } else {
            return $this->json([
                'Message' => 'Order does not exist',
            ]);
        }
    }

    private function send(array $data): void
    {
        $this->producer->send("voucher_queue", $data);
    }
}