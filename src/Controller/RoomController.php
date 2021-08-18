<?php

namespace App\Controller;

use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/room")
 */
class RoomController extends AbstractController
{
    /**
     * List room
     * @param RoomRepository $roomRepository
     * @param UserRepository $userRepository
     * @return jsonArray[]
     * @Route("/list", name="room_list", methods={"GET"})
     */
    public function roomList(RoomRepository $roomRepository, UserRepository $userRepository)
    {
        $responseArray = $roomRepository->transformAll($userRepository);

        return new JsonResponse($responseArray);
    }
    
    /**
     * Get room by QR code
     * @param String $qr_code
     * @param RoomRepository $roomRepository
     * @return jsonArray[]
     * @Route("/view/{qr_code}", name="room_view", methods={"GET"})
     */
    public function roomViewByQrCode(String $qr_code, RoomRepository $roomRepository)
    {
        $responseArray = $roomRepository->transformByQrCode($qr_code);

        return new JsonResponse($responseArray);
    }

    /**
     * Create room
     * Only user who has ROLE_GUIDE as role can access.
     * @param Request
     * @param ValidatorInterface
     * @return jsonArray[]
     * @Route("/create", name="room_create", methods={"POST"})
     */
    public function roomCreate(Request $request, ValidatorInterface $validator)
    {
        $em = $this->getDoctrine()->getManager();

        $owner = $this->getUser();

        $data = json_decode($request->getContent(), true);

        $name = $data['name'];
        $description = $data['description'];
        $qr_code = $data['qr_code'];
        
        $start_time = new \DateTime();          // this file can get from Front-End.

        $room = new Room($owner, $name, $description, $qr_code, $start_time);
        
        $this->denyAccessUnlessGranted('CREATE', $room);
        
        $errors = $validator->validate($room);
        if(count($errors) > 0)
        {
            foreach($errors as $error)
            {
                $key = $error->getPropertyPath();
                $responseArray['code'] = $error->getCode();
                $responseArray[$key] = $error->getMessage();
            }
            return new JsonResponse([
                'success' => false,
                'message' => 'Form validate error',
                'data' => $responseArray
            ]);
        }

        $em->persist($room);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => '',
            'data' => null
        ]);
    }

    /**
     * Edit room
     * Only owner can access to edit room
     * @param int $room_id
     * @param Request $request
     * @param RoomRepository $roomRepository
     * @param ValidatorInterface $validator
     * @Route("/{room_id}/edit", name="room_edit", methods={"POST"})
     */
    public function reoomEdit(int $room_id, Request $request, RoomRepository $roomRepository, ValidatorInterface $validator)
    {
        $em = $this->getDoctrine()->getManager();

        $room = $roomRepository->find($room_id);
        if (!$room) {
            return new JsonResponse([
                'success' => false,
                'message' => "Room record with ID: $room_id not found.",
                'data' => null
            ]);
        }
        $this->denyAccessUnlessGranted('EDIT', $room);

        $data = json_decode($request->getContent(), true);

        $name = $data['name'];
        $description = $data['description'];
        $qr_code = $data['qr_code'];

        $start_time = new \DateTime();          // this file can get from Front-End.

        $room->setName($name);
        $room->setDescription($description);
        $room->setQrCode($qr_code);
        $room->setStartTime($start_time);

        $errors = $validator->validate($room);
        if(count($errors) > 0)
        {
            foreach($errors as $error)
            {
                $key = $error->getPropertyPath();
                $responseArray['code'] = $error->getCode();
                $responseArray[$key] = $error->getMessage();
            }
            return new JsonResponse([
                'success' => false,
                'message' => 'Form validate error',
                'data' => $responseArray
            ]);
        }

        $em->persist($room);
        $em->flush();
        $data = $roomRepository->transform($room);

        return new JsonResponse([
            'success' => true,
            'message' => '',
            'data' => $data,
        ]);
    }

    /**
     * Delete room
     * Only owner can access to delete room.
     * @param int $room_id
     * @param RoomRepository
     * @return jsonArray[]
     * @Route("/{room_id}/delete", name="room_delete", methods={"DELETE"})
     */
    public function roomDelete(int $room_id, RoomRepository $roomRepository)
    {
        $em = $this->getDoctrine()->getManager();

        $room = $roomRepository->find($room_id);
        if (!$room) {
            return new JsonResponse([
                'success' => false,
                'message' => "Room record with ID: $room_id not found.",
                'data' => null
            ]);
        }
        
        $this->denyAccessUnlessGranted('DELETE', $room);

        $em->remove($room);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => '',
            'data' => null
        ]);
    }
}
